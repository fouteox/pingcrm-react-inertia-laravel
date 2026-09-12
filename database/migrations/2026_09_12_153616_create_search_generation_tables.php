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
        Schema::create('search_index_generations', function (Blueprint $table): void {
            $table->uuid('generation')->primary();
            $table->timestamp('retired_at')->nullable();
        });

        Schema::create('search_index_manifest', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->uuid('active_generation')->nullable();
            $table->uuid('building_generation')->nullable();
            $table->string('phase')->default('idle');
            $table->unsignedTinyInteger('created_collections')->default(0);
            $table->unsignedBigInteger('account_cursor')->default(0);
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('revision')->default(0);
            $table->unsignedTinyInteger('stage')->default(0);
            $table->unsignedBigInteger('last_id')->default(0);
            $table->unsignedBigInteger('upper_id')->nullable();
        });

        DB::table('search_index_manifest')->insert(['id' => 1]);

        Schema::create('search_generation_changes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('generation');
            $table->unsignedBigInteger('account_id');
            $table->string('model_class', 100);
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('revision');
            $table->unique(['generation', 'model_class', 'model_id', 'revision'], 'search_generation_change_identity');
            $table->index(['generation', 'id']);
        });
    }

    public function down(): void
    {
        if (DB::table('search_index_manifest')->whereNotNull('active_generation')->exists()) {
            DB::table('accounts')->update(['indexed_revision' => null, 'search_revision' => DB::raw('search_revision + 1')]);
        }

        Schema::dropIfExists('search_generation_changes');
        Schema::dropIfExists('search_index_manifest');
        Schema::dropIfExists('search_index_generations');
    }
};
