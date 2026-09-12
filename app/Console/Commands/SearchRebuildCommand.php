<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\SearchIndexUnavailable;
use App\Jobs\SearchIndexProjection;
use App\Models\Account;
use App\Services\SearchIndex;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Throwable;

#[Signature('search:rebuild {account? : Account ID; omitted to rebuild all accounts} {--sync : Wait for indexing and fail unless the account is ready} {--resume : Resume pending full rebuilds and skip ready accounts; requires --sync}')]
#[Description('Rebuild Typesense through durable, versioned account projections')]
final class SearchRebuildCommand extends Command
{
    public function handle(SearchIndex $search): int
    {
        if (config('scout.driver') !== 'typesense') {
            $this->error('This command requires the Typesense search driver.');

            return self::FAILURE;
        }

        if ($this->option('resume') && ! $this->option('sync')) {
            $this->error('Resuming a rebuild requires --sync.');

            return self::FAILURE;
        }

        $accountId = $this->argument('account');
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
