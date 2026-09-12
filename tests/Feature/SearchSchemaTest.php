<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Typesense\ApiCall;
use Typesense\Client;
use Typesense\Collections;
use Typesense\Exceptions\ObjectNotFound;

it('updates existing sort indexes once without accessing SQL or importing documents', function () {
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
        ->and(DB::getQueryLog())->toBe([]);
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
