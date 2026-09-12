<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\SearchIndexUnavailable;
use App\Jobs\SearchIndexProjection;
use App\Models\Account;
use App\Services\SearchGenerations;
use App\Services\SearchIndex;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Throwable;

#[Signature('search:rebuild {account? : Repair this account in place; omitted to repair all accounts} {--prune= : Remove the three collections of a recorded retired generation} {--background : Recommended for global reindexing: prepare an independent generation while serving live search} {--sync : Wait until the account or background generation is ready} {--resume : Resume pending account repairs and skip ready accounts; requires --sync}')]
#[Description('Reindex Typesense in the background or repair account projections in place')]
final class SearchRebuildCommand extends Command
{
    public function handle(SearchIndex $search, SearchGenerations $generations): int
    {
        if (config('scout.driver') !== 'typesense') {
            $this->error('This command requires the Typesense search driver.');

            return self::FAILURE;
        }

        if ($this->option('prune') !== null) {
            if ($this->argument('account') !== null || $this->option('background') || $this->option('sync') || $this->option('resume')) {
                $this->error('Pruning a retired generation must be requested on its own.');

                return self::FAILURE;
            }

            try {
                $generations->prune((string) $this->option('prune'));
            } catch (InvalidArgumentException $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            $this->info('Retired generation collections removed.');

            return self::SUCCESS;
        }

        if ($this->option('resume') && ! $this->option('sync')) {
            $this->error('Resuming a rebuild requires --sync.');

            return self::FAILURE;
        }

        $accountId = $this->argument('account');

        if ($this->option('background')) {
            if ($accountId !== null) {
                $this->error('A background generation rebuilds all accounts; omit the account argument.');

                return self::FAILURE;
            }

            $generation = $generations->start();

            if ($this->option('sync')) {
                try {
                    while (! $generations->project($generation)) {
                        // Every portion preserves its checkpoint for the durable worker.
                    }
                } catch (Throwable $exception) {
                    if (! $exception instanceof SearchIndexUnavailable) {
                        report($exception);
                    }

                    $this->error('The candidate generation is pending; live search remains on its active generation.');

                    return self::FAILURE;
                }
            }

            $this->info("Global generation [{$generation}]: ".($this->option('sync') ? 'ready.' : 'queued.'));

            return self::SUCCESS;
        }
        $accounts = Account::query();

        if ($accountId !== null) {
            if (! ctype_digit((string) $accountId) || ! $accounts->whereKey($accountId)->exists()) {
                $this->error('The requested account does not exist.');

                return self::FAILURE;
            }
        }

        $synchronous = Config::boolean('search.synchronous');
        Config::set('search.synchronous', false);

        try {
            foreach ($accounts->lazyById() as $account) {
                $state = $search->readState($account->id);

                if ($this->option('resume') && $state['revision'] === $state['indexedRevision']) {
                    $this->info("Account [{$account->id}]: ready.");

                    continue;
                }

                $projection = $this->option('resume') && $state['rebuildRevision'] > 0 && $state['revision'] === $state['rebuildRevision']
                    ? new SearchIndexProjection($account->id, $state['revision'])
                    : $search->rebuild($account->id);

                if ($this->option('sync')) {
                    try {
                        if ($projection === null) {
                            throw new SearchIndexUnavailable;
                        }

                        while (! $search->project($projection)) {
                            // Each portion persists its progress before the next one starts.
                        }

                        $search->assertReady($account->id);
                    } catch (Throwable $exception) {
                        if (! $exception instanceof SearchIndexUnavailable) {
                            report($exception);
                        }

                        $this->error("Account [{$account->id}] is pending; resume indexing or retry its projection job.");

                        return self::FAILURE;
                    }
                }

                $this->info("Account [{$account->id}]: ".($this->option('sync') ? 'ready.' : 'rebuild queued.'));
            }
        } finally {
            Config::set('search.synchronous', $synchronous);
        }

        return self::SUCCESS;
    }
}
