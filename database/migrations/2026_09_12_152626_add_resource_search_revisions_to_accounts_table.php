<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->unsignedBigInteger('contacts_search_revision')->default(0);
            $table->unsignedBigInteger('organizations_search_revision')->default(0);
            $table->unsignedBigInteger('users_search_revision')->default(0);
        });

        DB::table('accounts')->update([
            'contacts_search_revision' => DB::raw('search_revision'),
            'organizations_search_revision' => DB::raw('search_revision'),
            'users_search_revision' => DB::raw('search_revision'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn(['contacts_search_revision', 'organizations_search_revision', 'users_search_revision']);
        });
    }
};
