<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ReconcileDemoSearchIndex;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Attribute\AsCommand;
use UnexpectedValueException;

#[AsCommand(name: 'demo:reset', description: 'Reset the demo database and reindex search data')]
final class ResetDemoCommand extends Command
{
    public const string LOCK_KEY = 'pingcrm:demo-reset';

    public const string LOCK_NAME = 'laravel-queue-overlap:'.self::LOCK_KEY;

    public function handle(DatabaseSeeder $seeder): int
    {
        $lock = Cache::lock(
            self::LOCK_NAME,
            (int) config('demo.reset.lock_seconds')
        );

        if (! $lock->get()) {
            $this->error('Another demo reset is already running.');

            return self::FAILURE;
        }

        try {
            $this->info('Resetting demo data in a transaction...');

            $accountId = $this->withoutSearchSyncing(
                fn (): int => $this->resetDemoData($seeder)
            );
        } finally {
            $lock->release();
        }

        ReconcileDemoSearchIndex::dispatch($accountId)->afterCommit();

        $this->info('Demo data reset complete; search reconciliation queued.');

        return self::SUCCESS;
    }

    private function resetDemoData(DatabaseSeeder $seeder): int
    {
        return DB::transaction(function () use ($seeder): int {
            $this->lockDemoTablesForWrites();

            $demoAccount = Account::where('demo_key', DatabaseSeeder::DEMO_ACCOUNT_KEY)->first();
            $demoUser = $demoAccount?->users()
                ->withTrashed()
                ->where('email', DatabaseSeeder::DEMO_USER_EMAIL)
                ->first();

            $this->ensureTenantReferencesAreConsistent($demoAccount?->getKey());

            if ($demoAccount !== null) {
                DB::table('contacts')->where('account_id', $demoAccount->id)->delete();
                DB::table('organizations')->where('account_id', $demoAccount->id)->delete();
                DB::table('users')
                    ->where('account_id', $demoAccount->id)
                    ->when($demoUser, fn ($query) => $query->where('id', '!=', $demoUser->getKey()))
                    ->delete();
            }

            $seeder->run();

            return Account::where('demo_key', DatabaseSeeder::DEMO_ACCOUNT_KEY)
                ->sole()
                ->getKey();
        }, attempts: (int) config('demo.reset.transaction_attempts'));
    }

    private function ensureTenantReferencesAreConsistent(?int $demoAccountId): void
    {
        if ($demoAccountId === null) {
            return;
        }

        $hasCrossTenantReference = DB::table('contacts as contacts')
            ->join('organizations as organizations', 'organizations.id', '=', 'contacts.organization_id')
            ->where('organizations.account_id', $demoAccountId)
            ->where('contacts.account_id', '!=', $demoAccountId)
            ->exists();

        if ($hasCrossTenantReference) {
            throw new UnexpectedValueException(
                'Demo reset aborted: a contact references an organization from another account.'
            );
        }
    }

    private function lockDemoTablesForWrites(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("select set_config('lock_timeout', ?, true)", [
            config('demo.reset.database_lock_timeout_seconds').'s',
        ]);
        DB::statement("select set_config('transaction_timeout', ?, true)", [
            config('demo.reset.database_transaction_timeout_seconds').'s',
        ]);

        DB::statement(
            'LOCK TABLE accounts, users, organizations, contacts IN SHARE ROW EXCLUSIVE MODE'
        );
    }

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    private function withoutSearchSyncing(Closure $callback): mixed
    {
        return Contact::withoutSyncingToSearch(
            fn () => Organization::withoutSyncingToSearch(
                fn () => User::withoutSyncingToSearch($callback)
            )
        );
    }
}
