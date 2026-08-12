<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (! Schema::hasColumn('accounts', 'demo_key')) {
            Schema::table('accounts', function (Blueprint $table): void {
                $table->string('demo_key', 50)->nullable()->after('name');
            });
        }

        $demoAccountId = DB::table('users')
            ->where('email', 'johndoe@example.com')
            ->value('account_id');

        if ($demoAccountId !== null) {
            DB::table('accounts')
                ->where('id', $demoAccountId)
                ->update(['demo_key' => 'primary']);
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS accounts_demo_key_unique');
            DB::statement(
                'CREATE UNIQUE INDEX CONCURRENTLY accounts_demo_key_unique ON accounts (demo_key)'
            );

            return;
        }

        Schema::table('accounts', function (Blueprint $table): void {
            $table->unique('demo_key', 'accounts_demo_key_unique');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS accounts_demo_key_unique');
        } else {
            Schema::table('accounts', function (Blueprint $table): void {
                $table->dropUnique('accounts_demo_key_unique');
            });
        }

        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn('demo_key');
        });
    }
};
