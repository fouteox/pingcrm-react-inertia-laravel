<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\SearchIndexUnavailable;
use App\Models\Account;
use App\Services\SearchIndex;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

#[Signature('search:rebuild {account? : Account ID; omitted to rebuild all accounts} {--sync : Wait for indexing and fail unless the account is ready}')]
#[Description('Rebuild Typesense through durable, versioned account projections')]
final class SearchRebuildCommand extends Command
{
    public function handle(SearchIndex $search): int
    {
        if (config('scout.driver') !== 'typesense') {
            $this->error('This command requires the Typesense search driver.');

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
        Config::set('search.synchronous', (bool) $this->option('sync'));

        try {
            foreach ($accounts->lazyById() as $account) {
                $search->rebuild($account->id);

                if ($this->option('sync')) {
                    try {
                        $search->assertReady($account->id);
                    } catch (SearchIndexUnavailable) {
                        $this->error("Account [{$account->id}] is pending; its durable projection remains queued.");

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
