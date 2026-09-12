<?php

declare(strict_types=1);

use App\Jobs\SearchIndexProjection;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Services\SearchIndex;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Scout\EngineManager;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;

beforeEach(function () {
    if (! filter_var(env('RUN_DEMO_RESET_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
        $this->markTestSkipped('PostgreSQL and Typesense integration services are not enabled.');
    }

    expect(DB::getDriverName())->toBe('pgsql');
    config()->set([
        'cache.default' => 'array',
        'queue.default' => 'sync',
        'scout.driver' => 'typesense',
        'scout.prefix' => 'pingcrm_review_snapshot_',
        'scout.queue' => false,
        'scout.typesense.client-settings.num_retries' => 0,
        'search.synchronous' => true,
    ]);
    app(EngineManager::class)->forgetEngines();
    Artisan::call('migrate:fresh', ['--force' => true]);

    foreach ([new User, new Organization, new Contact] as $model) {
        try {
            app(Client::class)->getCollections()->{$model->indexableAs()}->delete();
        } catch (ObjectNotFound) {
            // The first execution has no test collections yet.
        }
    }

    Artisan::call('search:sync-schema');
});

function interceptSnapshotSearch(Closure $barrier, bool $afterResponse = true): void
{
    $handler = HandlerStack::create();
    $handler->push(fn (callable $next): Closure => function (RequestInterface $request, array $options) use ($next, $barrier, $afterResponse): PromiseInterface {
        if (! str_ends_with($request->getUri()->getPath(), '/documents/search')) {
            return $next($request, $options);
        }

        if (! $afterResponse) {
            $barrier();

            return $next($request, $options);
        }

        return $next($request, $options)->then(function (ResponseInterface $response) use ($barrier): ResponseInterface {
            $barrier();

            return $response;
        });
    });
    app()->instance(Client::class, new Client([
        ...config('scout.typesense.client-settings'),
        'client' => new HttpClient(['handler' => $handler, 'connect_timeout' => 0.5, 'timeout' => 1]),
    ]));
    app(EngineManager::class)->forgetEngines();
}

it('returns 503 on a real transport failure without holding a SQL write lock', function () {
    $account = Account::factory()->create();
    $search = app(SearchIndex::class);
    $actor = $search->mutate($account->id, fn () => User::factory()->for($account)->create(['first_name' => 'Administrator', 'owner' => true]));
    $search->mutate($account->id, fn () => User::factory()->for($account)->create(['first_name' => 'Ada', 'owner' => false]));
    $writer = DB::build(config('database.connections.pgsql'));
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage, STREAM_SERVER_BIND);
    expect($socket)->not->toBeFalse();
    [$host, $port] = explode(':', stream_socket_get_name($socket, false));
    $settings = config('scout.typesense.client-settings');
    $settings['nodes'] = [['host' => $host, 'port' => $port, 'protocol' => 'http']];
    unset($settings['nearest_node']);
    config()->set('scout.typesense.client-settings', $settings);
    $lockAcquired = false;
    interceptSnapshotSearch(function () use ($writer, $account, &$lockAcquired): void {
        expect(DB::transactionLevel())->toBe(0);
        $writer->transaction(function () use ($writer, $account, &$lockAcquired): void {
            $writer->table('accounts')->where('id', $account->id)->lock('for update nowait')->firstOrFail();
            $lockAcquired = true;
        });
    }, afterResponse: false);

    try {
        $this->actingAs($actor)->get('/users?search=Ada&role=user')->assertServiceUnavailable();

        expect($lockAcquired)->toBeTrue()
            ->and(DB::transactionLevel())->toBe(0)
            ->and($search->assertReady($account->id))->toBe(2);
    } finally {
        fclose($socket);
        $writer->disconnect();
    }
});

it('serves an HTTP search when its account revision changes during the engine response', function (bool $changeMatch, bool $acknowledged) {
    $account = Account::factory()->create();
    $search = app(SearchIndex::class);
    $actor = $search->mutate($account->id, fn () => User::factory()->for($account)->create(['first_name' => 'Administrator', 'owner' => true]));
    $match = $search->mutate($account->id, fn () => User::factory()->for($account)->create(['first_name' => 'Ada', 'owner' => false]));
    $outside = $search->mutate($account->id, fn () => User::factory()->for($account)->create(['first_name' => 'Grace', 'owner' => false]));
    $revision = $search->assertReady($account->id);
    $writer = DB::build(config('database.connections.pgsql'));
    $changedId = $changeMatch ? $match->id : $outside->id;
    interceptSnapshotSearch(function () use ($writer, $account, $changedId, $revision, $acknowledged): void {
        $writer->transaction(function () use ($writer, $account, $changedId, $revision, $acknowledged): void {
            $writer->table('accounts')->where('id', $account->id)->lock('for update nowait')->firstOrFail();
            $writer->table('users')->where('id', $changedId)->update(['last_name' => 'Concurrent']);
            $writer->table('accounts')->where('id', $account->id)->update([
                'search_revision' => $revision + 1,
                'indexed_revision' => $acknowledged ? $revision + 1 : $revision,
            ]);
        });
    });

    try {
        $this->actingAs($actor)->get('/users?search=Ada&role=user')->assertOk()
            ->assertInertia(fn (Assert $assert) => $assert
                ->where('users.meta.total', 1)
                ->where('users.data.0.id', $match->id)
                ->where('users.data.0.name', 'Ada '.($changeMatch ? 'Concurrent' : $match->last_name)));

        expect(User::findOrFail($changedId)->last_name)->toBe('Concurrent')
            ->and($search->readState($account->id)['revision'])->toBe($revision + 1)
            ->and(DB::transactionLevel())->toBe(0);
    } finally {
        $writer->disconnect();
    }
})->with([
    'matching document changed' => [true, false],
    'unrelated document changed' => [false, false],
    'new revision already acknowledged' => [false, true],
]);

it('reads the committed revision while another connection holds an uncommitted mutation', function () {
    $account = Account::factory()->create();
    $search = app(SearchIndex::class);
    $actor = $search->mutate($account->id, fn () => User::factory()->for($account)->create(['first_name' => 'Administrator', 'owner' => true]));
    $match = $search->mutate($account->id, fn () => User::factory()->for($account)->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'owner' => false]));
    $revision = $search->assertReady($account->id);
    $writer = DB::build(config('database.connections.pgsql'));
    $writer->beginTransaction();
    $writer->table('accounts')->where('id', $account->id)->update(['search_revision' => $revision + 1]);
    $writer->table('users')->where('id', $match->id)->update(['last_name' => 'Uncommitted']);

    try {
        DB::transaction(function () use ($actor, $match): void {
            DB::statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            DB::statement("SET LOCAL statement_timeout = '500ms'");
            $this->actingAs($actor)->get('/users?search=Ada&role=user')
                ->assertOk()
                ->assertInertia(fn (Assert $assert) => $assert
                    ->where('users.meta.total', 1)
                    ->where('users.data.0.id', $match->id)
                    ->where('users.data.0.name', 'Ada Lovelace'));
        });
        $writer->commit();
        $this->get('/users?search=Ada&role=user')->assertOk()
            ->assertInertia(fn (Assert $assert) => $assert
                ->where('users.meta.total', 1)
                ->where('users.data.0.id', $match->id)
                ->where('users.data.0.name', 'Ada Uncommitted'));

        expect($search->readState($account->id))->toBe(['revision' => $revision + 1, 'indexedRevision' => $revision, 'rebuildRevision' => 0]);
    } finally {
        if ($writer->transactionLevel() > 0) {
            $writer->rollBack();
        }
        $writer->disconnect();
    }
});

it('does not let an in-flight rebuild acknowledge a collection recreated after its writes', function () {
    $account = Account::factory()->create();
    $search = app(SearchIndex::class);
    $user = $search->mutate($account->id, fn () => User::factory()->for($account)->create(['first_name' => 'Ada']));
    config()->set('search.synchronous', false);
    $search->rebuild($account->id);
    $revision = $search->readState($account->id)['revision'];
    $collection = app(Client::class)->getCollections()->{$user->indexableAs()};
    $schemaRecreated = false;
    DB::connection()->beforeExecuting(function (string $query) use ($collection, &$schemaRecreated): void {
        if ($schemaRecreated || ! str_starts_with($query, 'update "accounts" set "indexed_revision"')) {
            return;
        }

        $schemaRecreated = true;
        $collection->delete();
        expect(Artisan::call('search:sync-schema'))->toBe(0);
    });

    $search->project(new SearchIndexProjection($account->id, $revision));

    expect($schemaRecreated)->toBeTrue()
        ->and($collection->getDocuments()->search(['q' => '*', 'query_by' => 'first_name'])['found'])->toBe(0)
        ->and(User::where('account_id', $account->id)->count())->toBe(1);
    expect(fn () => $search->assertReady($account->id))->toThrow(App\Exceptions\SearchIndexUnavailable::class);
    expect($search->readState($account->id)['revision'])->toBeGreaterThan($revision);
});
