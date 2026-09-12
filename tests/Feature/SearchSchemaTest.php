<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Contact;
use App\Services\SearchGenerations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Typesense\ApiCall;
use Typesense\Client;
use Typesense\Collections;
use Typesense\Exceptions\ObjectAlreadyExists;
use Typesense\Exceptions\ObjectNotFound;

it('updates existing sort indexes once without reading business records or importing documents', function () {
    config()->set('scout.prefix', '');
    Queue::fake();
    config()->set([
        'scout.queue' => true,
        'scout.typesense.model-settings' => [Contact::class => config('scout.typesense.model-settings.'.Contact::class)],
    ]);
    $schema = ['fields' => collect(config('scout.typesense.model-settings.'.Contact::class.'.collection-schema.fields'))
        ->reject(fn (array $field) => in_array($field['name'], ['id', 'search_revision', 'search_deleted'], true))
        ->map(fn (array $field) => match ($field['name']) {
            'first_name' => [...$field, 'sort' => false, 'locale' => 'fr', 'optional' => true],
            'last_name' => [...$field, 'sort' => false],
            default => $field,
        })
        ->push(['name' => 'custom_field', 'type' => 'string', 'optional' => true])->values()->all()];
    $api = Mockery::mock(ApiCall::class);
    $api->shouldReceive('get')->twice()->with('/collections/contacts', [])->andReturnUsing(function () use (&$schema): array {
        return $schema;
    });
    $api->shouldReceive('patch')->once()->with('/collections/contacts', Mockery::type('array'))
        ->andReturnUsing(function (string $path, array $patch) use (&$schema): array {
            expect($patch)->toBe(['fields' => [
                ['name' => 'first_name', 'drop' => true],
                ['name' => 'first_name', 'type' => 'string', 'sort' => true, 'locale' => 'fr', 'optional' => true],
                ['name' => 'last_name', 'drop' => true],
                ['name' => 'last_name', 'type' => 'string', 'sort' => true],
                ['name' => 'search_revision', 'type' => 'int64', 'optional' => true],
                ['name' => 'search_deleted', 'type' => 'bool', 'optional' => true],
            ]]);
            $schema['fields'] = array_values(array_replace(
                array_column($schema['fields'], null, 'name'),
                array_column(array_filter($patch['fields'], fn (array $field) => ! ($field['drop'] ?? false)), null, 'name'),
            ));

            return $schema;
        });
    $api->shouldNotReceive('post');
    $api->shouldNotReceive('delete');
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->andReturn(new Collections($api));
    app()->instance(Client::class, $client);
    DB::enableQueryLog();

    $this->artisan('search:sync-schema')->assertSuccessful();
    $this->artisan('search:sync-schema')->assertSuccessful();

    expect(config('scout.driver'))->toBe('collection')
        ->and(config('scout.queue'))->toBeTrue()
        ->and(DB::getQueryLog())->not->toBeEmpty();

    foreach (DB::getQueryLog() as $query) {
        expect($query['query'])->toStartWith('select ')->toContain('search_index_manifest');
    }
    DB::disableQueryLog();
    Queue::assertNothingPushed();
});

it('invalidates readiness before recreating a missing collection from the configured schema', function () {
    $account = Account::factory()->create();
    config()->set('scout.prefix', '');
    $schema = config('scout.typesense.model-settings.'.Contact::class.'.collection-schema');
    config()->set('scout.typesense.model-settings', [Contact::class => ['collection-schema' => $schema]]);
    $api = Mockery::mock(ApiCall::class);
    $api->shouldReceive('get')->once()->with('/collections/contacts', [])->andThrow(new ObjectNotFound('Missing'));
    $api->shouldReceive('post')->once()->with('/collections', ['name' => 'contacts', ...$schema], true, [])
        ->andReturnUsing(function () use ($account, $schema): array {
            expect($account->fresh()->indexed_revision)->toBeNull();

            return ['name' => 'contacts', ...$schema];
        });
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->andReturn(new Collections($api));
    app()->instance(Client::class, $client);

    $this->artisan('search:sync-schema')->assertSuccessful();
});

it('synchronizes an existing candidate as well as the active schema before it can be selected', function (string $phase) {
    config()->set('scout.prefix', '');
    $schema = config('scout.typesense.model-settings.'.Contact::class.'.collection-schema');
    config()->set('scout.typesense.model-settings', [Contact::class => ['collection-schema' => $schema]]);
    $generation = (string) Str::uuid();
    $index = SearchGenerations::collection(new Contact, $generation);
    DB::table('search_index_manifest')->update(['building_generation' => $generation, 'phase' => $phase, 'created_collections' => 3]);
    $existing = ['fields' => array_values(array_filter($schema['fields'], fn (array $field): bool => $field['name'] !== 'organization_name'))];
    $transactionLevel = DB::transactionLevel();
    $api = Mockery::mock(ApiCall::class);
    $api->shouldReceive('get')->once()->with('/collections/contacts', [])->andReturn($schema);
    $api->shouldReceive('get')->once()->with('/collections/'.$index, [])->andReturn($existing);
    $api->shouldReceive('patch')->once()->with('/collections/'.$index, ['fields' => [['name' => 'organization_name', 'type' => 'string']]])
        ->andReturnUsing(function () use ($transactionLevel, $schema): array {
            expect(DB::transactionLevel())->toBe($transactionLevel);

            return $schema;
        });
    $api->shouldNotReceive('post');
    $api->shouldNotReceive('delete');
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->andReturn(new Collections($api));
    app()->instance(Client::class, $client);

    $this->artisan('search:sync-schema')->assertSuccessful();
    expect(DB::table('search_index_manifest')->value('building_generation'))->toBe($generation);
})->with(['backfill', 'catchup', 'preparing']);

it('handles a missing candidate according to its current manifest role without resuming an empty partial backfill', function (string $event) {
    $account = Account::factory()->create();
    $contact = Contact::factory()->for($account)->create();
    $before = $account->fresh()->getAttributes();
    config()->set('scout.prefix', '');
    $schema = config('scout.typesense.model-settings.'.Contact::class.'.collection-schema');
    config()->set('scout.typesense.model-settings', [Contact::class => ['collection-schema' => $schema]]);
    $generation = (string) Str::uuid();
    $index = SearchGenerations::collection(new Contact, $generation);
    $phase = in_array($event, ['backfill', 'preparing'], true) ? $event : 'catchup';
    DB::table('search_index_generations')->insert(['generation' => $generation]);
    DB::table('search_index_manifest')->update([
        'building_generation' => $generation,
        'phase' => $phase,
        'created_collections' => $phase === 'preparing' ? 0 : 3,
        'account_id' => $phase === 'preparing' ? null : $account->id,
        'last_id' => $phase === 'preparing' ? 0 : $contact->id,
    ]);
    $manifest = DB::table('search_index_manifest')->first();
    DB::table('search_generation_changes')->insert([
        'generation' => $generation, 'account_id' => $account->id,
        'model_class' => Contact::class, 'model_id' => $contact->id, 'revision' => 0,
    ]);
    $transactionLevel = DB::transactionLevel();
    $api = Mockery::mock(ApiCall::class);
    $api->shouldReceive('get')->once()->with('/collections/contacts', [])->andReturn($schema);
    $api->shouldReceive('get')->once()->with('/collections/'.$index, [])->andReturnUsing(function () use ($event, $generation, $transactionLevel): never {
        expect(DB::transactionLevel())->toBe($transactionLevel);
        if ($event === 'activated') {
            DB::table('search_index_manifest')->update(['active_generation' => $generation, 'building_generation' => null, 'phase' => 'idle']);
        } elseif ($event === 'retired') {
            DB::table('search_index_manifest')->update(['building_generation' => null, 'phase' => 'idle']);
        }

        throw new ObjectNotFound('Missing candidate collection');
    });
    if (in_array($event, ['activated', 'preparing'], true)) {
        $api->shouldReceive('post')->once()->with('/collections', ['name' => $index, ...$schema], true, [])
            ->andReturnUsing(function () use ($account, $schema, $transactionLevel, $event): array {
                expect(DB::transactionLevel())->toBe($transactionLevel);
                if ($event === 'activated') {
                    expect($account->fresh()->indexed_revision)->toBeNull()
                        ->and($account->fresh()->search_revision)->toBe(1);
                } else {
                    expect($account->fresh()->indexed_revision)->toBe(0)
                        ->and($account->fresh()->search_revision)->toBe(0);
                }

                return $schema;
            });
    } else {
        $api->shouldNotReceive('post');
    }
    $api->shouldNotReceive('patch');
    $api->shouldNotReceive('delete');
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->andReturn(new Collections($api));
    app()->instance(Client::class, $client);

    $command = $this->artisan('search:sync-schema');
    if (in_array($event, ['activated', 'retired', 'preparing'], true)) {
        $command->assertSuccessful();
    } else {
        $command->assertFailed();
    }
    $command->run();
    if ($event !== 'activated') {
        expect($account->fresh()->getAttributes())->toBe($before);
    }
    if ($event === 'preparing') {
        expect(DB::table('search_index_manifest')->first())->toEqual($manifest)
            ->and(DB::table('search_generation_changes')->count())->toBe(1);
    } elseif (in_array($event, ['backfill', 'catchup'], true)) {
        expect(DB::table('search_index_manifest')->value('building_generation'))->toBeNull()
            ->and(DB::table('search_index_generations')->where('generation', $generation)->value('retired_at'))->not->toBeNull()
            ->and(DB::table('search_generation_changes')->count())->toBe(0)
            ->and(app(SearchGenerations::class)->project($generation))->toBeTrue();
        DB::transaction(fn () => app(SearchGenerations::class)->recordChanges(['active' => null, 'building' => $generation], [$contact], $account->id, 0));
        expect(DB::table('search_generation_changes')->count())->toBe(0);
    }
})->with(['backfill', 'catchup', 'preparing', 'activated', 'retired']);

it('rechecks generations introduced while schema synchronization is in flight', function () {
    config()->set('scout.prefix', '');
    $schema = config('scout.typesense.model-settings.'.Contact::class.'.collection-schema');
    config()->set('scout.typesense.model-settings', [Contact::class => ['collection-schema' => $schema]]);
    $generation = (string) Str::uuid();
    $index = SearchGenerations::collection(new Contact, $generation);
    $api = Mockery::mock(ApiCall::class);
    $api->shouldReceive('get')->once()->with('/collections/contacts', [])->andReturnUsing(function () use ($generation, $schema): array {
        DB::table('search_index_manifest')->update(['building_generation' => $generation, 'phase' => 'backfill']);

        return $schema;
    });
    $api->shouldReceive('get')->once()->with('/collections/'.$index, [])->andReturn($schema);
    $api->shouldNotReceive('patch');
    $api->shouldNotReceive('post');
    $api->shouldNotReceive('delete');
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->andReturn(new Collections($api));
    app()->instance(Client::class, $client);

    $this->artisan('search:sync-schema')->assertSuccessful();
});

it('synchronizes a missing collection created concurrently by a worker or another schema command', function (bool $candidate) {
    $account = Account::factory()->create();
    config()->set('scout.prefix', '');
    $schema = config('scout.typesense.model-settings.'.Contact::class.'.collection-schema');
    config()->set('scout.typesense.model-settings', [Contact::class => ['collection-schema' => $schema]]);
    $generation = $candidate ? (string) Str::uuid() : null;
    $index = SearchGenerations::collection(new Contact, $generation);
    if ($candidate) {
        DB::table('search_index_manifest')->update(['building_generation' => $generation, 'phase' => 'preparing']);
    }
    $existing = ['fields' => array_values(array_filter($schema['fields'], fn (array $field): bool => $field['name'] !== 'organization_name'))];
    $transactionLevel = DB::transactionLevel();
    $api = Mockery::mock(ApiCall::class);
    if ($candidate) {
        $api->shouldReceive('get')->once()->with('/collections/contacts', [])->andReturn($schema);
    }
    $api->shouldReceive('get')->once()->with('/collections/'.$index, [])->andThrow(new ObjectNotFound('Not created yet'));
    $api->shouldReceive('post')->once()->with('/collections', ['name' => $index, ...$schema], true, [])
        ->andThrow(new ObjectAlreadyExists('Created concurrently'));
    $api->shouldReceive('get')->once()->with('/collections/'.$index, [])->andReturn($existing);
    $api->shouldReceive('patch')->once()->with('/collections/'.$index, ['fields' => [['name' => 'organization_name', 'type' => 'string']]])
        ->andReturnUsing(function () use ($schema, $transactionLevel): array {
            expect(DB::transactionLevel())->toBe($transactionLevel);

            return $schema;
        });
    $api->shouldNotReceive('delete');
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->andReturn(new Collections($api));
    app()->instance(Client::class, $client);

    $this->artisan('search:sync-schema')->assertSuccessful();
    expect($account->fresh()->indexed_revision)->toBe($candidate ? 0 : null)
        ->and($account->fresh()->search_revision)->toBe($candidate ? 0 : 1);
})->with(['preparing candidate' => true, 'active recreation' => false]);
