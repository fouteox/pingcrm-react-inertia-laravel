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
        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('search_revision')->default(0);
            $table->unsignedBigInteger('indexed_revision')->nullable()->default(0);
            $table->unsignedBigInteger('search_rebuild_revision')->default(0);
            $table->unsignedBigInteger('search_projection_revision')->nullable();
            $table->unsignedTinyInteger('search_projection_stage')->default(0);
            $table->unsignedBigInteger('search_projection_id')->default(0);
            $table->unsignedBigInteger('search_projection_upper_id')->nullable();
        });

        DB::table('accounts')->update(['indexed_revision' => null]);

        foreach (['contacts', 'organizations', 'users'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->index(['account_id', 'id']);
            });
        }

        Schema::table('contacts', function (Blueprint $table) {
            $table->index(['organization_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['contacts', 'organizations', 'users'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['account_id', 'id']);
            });
        }

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'id']);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['search_revision', 'indexed_revision', 'search_rebuild_revision', 'search_projection_revision', 'search_projection_stage', 'search_projection_id', 'search_projection_upper_id']);
        });
    }
};
