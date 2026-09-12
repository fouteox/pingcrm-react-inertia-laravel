<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\TypesenseEngine;
use Typesense\ApiCall;
use Typesense\Client;
use Typesense\Collections;
use Typesense\Exceptions\TypesenseClientError;
use Typesense\MultiSearch;

beforeEach(function () {
    if (! filter_var(env('RUN_DEMO_RESET_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
        $this->markTestSkipped('PostgreSQL integration services are not enabled.');
    }

    expect(DB::getDriverName())->toBe('pgsql');
    config()->set(['scout.driver' => 'collection', 'scout.prefix' => '']);
    Artisan::call('migrate:fresh', ['--force' => true]);
});

it('hydrates the same eligible SQL snapshot despite concurrent changes during search', function () {
    $account = Account::factory()->create();
    $otherAccount = Account::factory()->create();
    $changed = User::factory()->for($account)->create(['owner' => false]);
    $removed = User::factory()->for($account)->create(['owner' => false]);
    $writer = DB::build(config('database.connections.pgsql'));
    $api = Mockery::mock(ApiCall::class);
    $api->shouldReceive('post')->once()->andReturnUsing(function () use ($writer, $changed, $removed, $otherAccount): array {
        $writer->table('users')->where('id', $changed->id)->update([
            'account_id' => $otherAccount->id,
            'owner' => true,
            'deleted_at' => now(),
        ]);
        $writer->table('users')->where('id', $removed->id)->delete();

        return ['results' => [[
            'found' => 2,
            'hits' => [
                ['document' => ['id' => (string) $changed->id]],
                ['document' => ['id' => (string) $removed->id]],
            ],
        ]]];
    });
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->once()->andReturn(new Collections($api));
    $client->shouldReceive('getMultiSearch')->once()->andReturn(new MultiSearch($api));
    app()->instance(Client::class, $client);
    app(EngineManager::class)->extend('typesense', fn () => new TypesenseEngine($client, 1000));
    config()->set('scout.driver', 'typesense');

    try {
        $page = User::paginateFiltered(['search' => 'person', 'role' => 'user'], $account->id);

        expect($page->total())->toBe(2)
            ->and($page->items())->toHaveCount(2);

        foreach ($page->items() as $user) {
            expect($user->account_id)->toBe($account->id)
                ->and((bool) $user->owner)->toBeFalse()
                ->and($user->deleted_at)->toBeNull();
        }

        $current = $changed->fresh();
        expect($current->account_id)->toBe($otherAccount->id)
            ->and((bool) $current->owner)->toBeTrue()
            ->and($current->deleted_at)->not->toBeNull()
            ->and(User::withTrashed()->find($removed->id))->toBeNull()
            ->and(DB::transactionLevel())->toBe(0)
            ->and(DB::selectOne('SHOW transaction_isolation')->transaction_isolation)->toBe('read committed');
    } finally {
        $writer->disconnect();
    }
});

it('releases the SQL snapshot when Typesense fails', function () {
    $user = User::factory()->create();
    $api = Mockery::mock(ApiCall::class);
    $api->shouldReceive('post')->once()->andReturn(['results' => [['code' => 503, 'error' => 'Search unavailable']]]);
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->once()->andReturn(new Collections($api));
    $client->shouldReceive('getMultiSearch')->once()->andReturn(new MultiSearch($api));
    app()->instance(Client::class, $client);
    app(EngineManager::class)->extend('typesense', fn () => new TypesenseEngine($client, 1000));
    config()->set('scout.driver', 'typesense');

    expect(fn () => User::paginateFiltered(['search' => 'person'], $user->account_id))
        ->toThrow(TypesenseClientError::class, 'Search unavailable');

    expect(DB::transactionLevel())->toBe(0)
        ->and(DB::selectOne('SHOW transaction_isolation')->transaction_isolation)->toBe('read committed');
});
