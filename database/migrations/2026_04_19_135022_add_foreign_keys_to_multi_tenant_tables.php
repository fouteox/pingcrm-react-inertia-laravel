<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $accountIds = DB::table('accounts')->pluck('id');

        if ($accountIds->isNotEmpty()) {
            DB::table('users')->whereNotIn('account_id', $accountIds)->delete();
            DB::table('organizations')->whereNotIn('account_id', $accountIds)->delete();
            DB::table('contacts')->whereNotIn('account_id', $accountIds)->delete();
        }

        $organizationIds = DB::table('organizations')->pluck('id');
        if ($organizationIds->isNotEmpty()) {
            DB::table('contacts')
                ->whereNotNull('organization_id')
                ->whereNotIn('organization_id', $organizationIds)
                ->update(['organization_id' => null]);
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });

        Schema::table('contacts', function (Blueprint $table): void {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('organization_id')->nullable()->change();
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['organization_id']);
            $table->integer('account_id')->change();
            $table->integer('organization_id')->nullable()->change();
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropForeign(['account_id']);
            $table->integer('account_id')->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['account_id']);
            $table->integer('account_id')->change();
        });
    }
};
