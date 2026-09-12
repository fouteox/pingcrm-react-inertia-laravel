<?php

declare(strict_types=1);

use App\Console\Commands\ResetDemoCommand;
use App\Jobs\ReconcileDemoSearchIndex;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\NullEngine;
use Laravel\Scout\Engines\TypesenseEngine;
use Typesense\ApiCall;
use Typesense\Client;
use Typesense\Collections;

it('rejects invalid transaction attempts before changing demo data', function (int $attempts) {
    $account = Account::factory()->create([
        'name' => 'Preserved',
        'demo_key' => DatabaseSeeder::DEMO_ACCOUNT_KEY,
    ]);
    config()->set('demo.reset.transaction_attempts', $attempts);
    Queue::fake();

    expect(fn () => $this->artisan('demo:reset')->run())
        ->toThrow(UnexpectedValueException::class, 'Demo reset transaction attempts must be at least 1.');

    expect($account->fresh()->name)->toBe('Preserved');
    Queue::assertNothingPushed();
})->with([0, -1]);

it('releases the reconciliation lock after a search failure and retries the current database state synchronously', function () {
    $account = Account::factory()->create();
    $contact = Contact::factory()->for($account)->create(['first_name' => 'Before']);
    $engine = new class extends NullEngine
    {
        public bool $unavailable = true;

        public array $indexed = [];

        public function update($models): void
        {
            if ($this->unavailable) {
                throw new RuntimeException('Search index unavailable.');
            }

            foreach ($models as $model) {
                $this->indexed[$model::class][$model->getKey()] = $model->toSearchableArray();
            }
        }
    };
    app(EngineManager::class)->extend('retry-test', fn () => $engine);
    config()->set(['scout.driver' => 'retry-test', 'scout.queue' => true]);
    Queue::fake();
    $job = new ReconcileDemoSearchIndex($account->id);
    $middleware = $job->middleware()[0];

    expect(fn () => $middleware->handle($job, fn (ReconcileDemoSearchIndex $job) => $job->handle()))
        ->toThrow(RuntimeException::class, 'Search index unavailable.');

    $this->assertModelExists($contact);
    $releasedLock = Cache::lock(ResetDemoCommand::LOCK_NAME, 60);
    expect($releasedLock->get())->toBeTrue();
    $releasedLock->release();

    Contact::whereKey($contact->id)->update(['first_name' => 'After']);
    $engine->unavailable = false;
    $middleware->handle($job, fn (ReconcileDemoSearchIndex $job) => $job->handle());

    expect($engine->indexed[Contact::class][$contact->id]['first_name'])->toBe('After');
    Queue::assertNothingPushed();
});

it('fails reconciliation for retry when the database never stabilizes', function () {
    $account = Account::factory()->create();
    $contact = Contact::factory()->for($account)->create(['first_name' => 'Version 0']);
    $updates = 0;
    $engine = Mockery::mock(NullEngine::class)->makePartial();
    $engine->shouldReceive('update')->andReturnUsing(function (Collection $models) use ($contact, &$updates): void {
        $updates++;
        Contact::whereKey($contact->id)->update(['first_name' => 'Version '.$updates]);
    });
    app(EngineManager::class)->extend('unstable-test', fn () => $engine);
    config()->set('scout.driver', 'unstable-test');

    expect(fn () => (new ReconcileDemoSearchIndex($account->id))->handle())
        ->toThrow(RuntimeException::class, 'Search data for account '.$account->id.' kept changing during reconciliation.')
        ->and($updates)->toBe(3);
});

it('keeps soft-deleted search documents during demo reconciliation while removing obsolete IDs', function (string $modelClass, string $index) {
    $account = Account::factory()->create();
    $records = $modelClass::factory(2)->for($account)->create();
    $records->last()->delete();
    $indexed = [];
    $deletions = [];
    $api = Mockery::mock(ApiCall::class);
    $api->shouldReceive('get')->once()->with('/collections/'.$index, [])->andReturn(['name' => $index]);
    $api->shouldReceive('post')->once()
        ->with('/collections/'.$index.'/documents/import', Mockery::type('string'), false, ['action' => 'upsert'])
        ->andReturnUsing(function (string $path, string $body) use (&$indexed): string {
            $indexed = array_map(fn (string $line) => json_decode($line, true, flags: JSON_THROW_ON_ERROR), explode("\n", $body));

            return implode("\n", array_fill(0, count($indexed), '{"success":true}'));
        });

    foreach (['contacts', 'organizations', 'users'] as $collection) {
        $documents = $collection === $index
            ? $records->map(fn ($record) => json_encode(['id' => (string) $record->id]))->push('{"id":"999999"}')->implode("\n")
            : '';
        $api->shouldReceive('get')->once()
            ->with('/collections/'.$collection.'/documents/export', ['filter_by' => 'account_id:='.$account->id], false)
            ->andReturn($documents);
    }

    $api->shouldReceive('delete')->once()
        ->with('/collections/'.$index.'/documents/', true, Mockery::type('array'))
        ->andReturnUsing(function (string $path, bool $returnJson, array $parameters) use (&$deletions): array {
            $deletions[] = $parameters;

            return ['num_deleted' => 1];
        });
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->andReturn(new Collections($api));
    app()->instance(Client::class, $client);
    app(EngineManager::class)->extend('typesense', fn () => new TypesenseEngine($client, 1000));
    config()->set(['scout.driver' => 'typesense', 'scout.prefix' => '', 'scout.soft_delete' => true]);

    (new ReconcileDemoSearchIndex($account->id))->handle();

    expect(array_column($indexed, 'id'))->toBe($records->map(fn ($record) => (string) $record->id)->all())
        ->and(array_column($indexed, '__soft_deleted'))->toBe([0, 1])
        ->and($deletions)->toBe([['filter_by' => 'account_id:='.$account->id.' && id:[999999]']]);
})->with([
    'contacts' => [Contact::class, 'contacts'],
    'organizations' => [Organization::class, 'organizations'],
    'users' => [User::class, 'users'],
]);

it('reindexes a contact soft-deleted after the first reconciliation snapshot', function () {
    $account = Account::factory()->create();
    $contact = Contact::factory()->for($account)->create();
    $deletedStates = [];
    $engine = Mockery::mock(NullEngine::class)->makePartial();
    $engine->shouldReceive('update')->andReturnUsing(function (Collection $models) use ($contact, &$deletedStates): void {
        $deletedStates[] = $models->first()->trashed();

        if (count($deletedStates) === 1) {
            Contact::whereKey($contact->id)->update(['deleted_at' => now()]);
        }
    });
    app(EngineManager::class)->extend('soft-delete-test', fn () => $engine);
    config()->set(['scout.driver' => 'soft-delete-test', 'scout.soft_delete' => true]);

    (new ReconcileDemoSearchIndex($account->id))->handle();

    expect($deletedStates)->toBe([false, true]);
});
