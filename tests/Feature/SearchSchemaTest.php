<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Contact;
use Illuminate\Support\Facades\Queue;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\TypesenseEngine;
use Typesense\ApiCall;
use Typesense\Client;
use Typesense\Collections;
use Typesense\Exceptions\ObjectNotFound;

it('updates existing search schemas once and synchronously backfills active and trashed records', function () {
    config()->set('scout.prefix', '');
    $account = Account::factory()->create();
    $contacts = Contact::factory(2)->for($account)->create();
    $contacts->last()->delete();
    Queue::fake();
    config()->set([
        'scout.queue' => true,
        'scout.typesense.model-settings' => [Contact::class => config('scout.typesense.model-settings.'.Contact::class)],
    ]);
    $schema = ['fields' => [
        ['name' => 'first_name', 'type' => 'string', 'sort' => false],
        ['name' => 'last_name', 'type' => 'string', 'sort' => false],
        ['name' => 'custom_field', 'type' => 'string', 'optional' => true],
    ]];
    $api = Mockery::mock(ApiCall::class);
    $api->shouldReceive('get')->with('/collections/contacts', [])->andReturnUsing(function () use (&$schema): array {
        return $schema;
    });
    $api->shouldReceive('patch')->once()->with('/collections/contacts', Mockery::type('array'))
        ->andReturnUsing(function (string $path, array $patch) use (&$schema): array {
            expect($patch)->toBe(['fields' => [
                ['name' => 'sort_id', 'type' => 'int64', 'optional' => true],
                ['name' => 'first_name', 'drop' => true],
                ['name' => 'first_name', 'type' => 'string', 'sort' => true],
                ['name' => 'last_name', 'drop' => true],
                ['name' => 'last_name', 'type' => 'string', 'sort' => true],
            ]]);
            $schema['fields'] = [
                ...array_filter($patch['fields'], fn (array $field) => ! ($field['drop'] ?? false)),
                ['name' => 'custom_field', 'type' => 'string', 'optional' => true],
            ];

            return $schema;
        });
    $api->shouldReceive('post')->twice()
        ->with('/collections/contacts/documents/import', Mockery::type('string'), false, ['action' => 'upsert'])
        ->andReturnUsing(function (string $path, string $body) use ($contacts): string {
            $documents = collect(explode("\n", $body))->map(fn (string $line) => json_decode($line, true, flags: JSON_THROW_ON_ERROR));
            expect($documents->pluck('sort_id')->all())->toBe($contacts->modelKeys())
                ->and($documents->pluck('__soft_deleted')->all())->toBe([0, 1]);

            return implode("\n", $documents->map(fn () => json_encode(['success' => true]))->all());
        });
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->andReturn(new Collections($api));
    app()->instance(Client::class, $client);
    app(EngineManager::class)->extend('typesense', fn () => new TypesenseEngine($client, 1000));

    $this->artisan('search:sync-schema')->assertSuccessful();
    $this->artisan('search:sync-schema')->assertSuccessful();

    expect(config('scout.driver'))->toBe('collection')
        ->and(config('scout.queue'))->toBeTrue();
    Queue::assertNothingPushed();
});

it('creates a missing search collection from the configured schema', function () {
    config()->set('scout.prefix', '');
    $schema = config('scout.typesense.model-settings.'.Contact::class.'.collection-schema');
    config()->set('scout.typesense.model-settings', [Contact::class => ['collection-schema' => $schema]]);
    $api = Mockery::mock(ApiCall::class);
    $api->shouldReceive('get')->once()->with('/collections/contacts', [])->andThrow(new ObjectNotFound('Missing'));
    $api->shouldReceive('post')->once()->with('/collections', ['name' => 'contacts', ...$schema], true, [])
        ->andReturn(['name' => 'contacts', ...$schema]);
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->andReturn(new Collections($api));
    app()->instance(Client::class, $client);

    $this->artisan('search:sync-schema')->assertSuccessful();
});
