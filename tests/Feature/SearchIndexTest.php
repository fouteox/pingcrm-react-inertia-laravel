<?php

declare(strict_types=1);

use App\Exceptions\SearchIndexUnavailable;
use App\Jobs\SearchIndexProjection;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Services\SearchIndex;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\TypesenseEngine;
use Typesense\Client;
use Typesense\Exceptions\RequestUnauthorized;

beforeEach(function () {
    config()->set(['scout.driver' => 'typesense', 'search.synchronous' => false]);
    $engine = Mockery::mock(TypesenseEngine::class);
    $engine->shouldNotReceive('update', 'delete');
    app(EngineManager::class)->extend('typesense', fn () => $engine);
});

it('records the mutation and its scalar projection in the same database transaction', function () {
    $user = User::withoutSyncingToSearch(fn () => User::factory()->create(['last_name' => 'Before']));

    $result = app(SearchIndex::class)->mutate($user->account_id, fn () => tap($user)->update(['last_name' => 'After']));

    expect($result)->toBe($user)
        ->and($user->fresh()->last_name)->toBe('After')
        ->and(DB::table('accounts')->where('id', $user->account_id)->value('search_revision'))->toBe(1)
        ->and(DB::table('accounts')->where('id', $user->account_id)->value('indexed_revision'))->toBe(0);
    $queued = DB::table('jobs')->sole();
    $payload = json_decode($queued->payload, true, flags: JSON_THROW_ON_ERROR);
    $job = unserialize($payload['data']['command']);

    expect($queued->queue)->toBe('search-index')
        ->and($job)->toBeInstanceOf(SearchIndexProjection::class)
        ->and($job->accountId)->toBe($user->account_id)
        ->and($job->modelClass)->toBe(User::class)
        ->and($job->modelId)->toBe($user->id)
        ->and($job->revision)->toBe(1);
});

it('rolls back the requested revision and job with an outer transaction', function () {
    $user = User::withoutSyncingToSearch(fn () => User::factory()->create(['last_name' => 'Before']));

    expect(fn () => DB::transaction(function () use ($user): void {
        app(SearchIndex::class)->mutate($user->account_id, fn () => tap($user)->update(['last_name' => 'After']));
        throw new RuntimeException('Abort outer transaction');
    }))->toThrow(RuntimeException::class, 'Abort outer transaction');

    expect($user->fresh()->last_name)->toBe('Before')
        ->and(DB::table('accounts')->where('id', $user->account_id)->value('search_revision'))->toBe(0)
        ->and(DB::table('jobs')->count())->toBe(0);
});

it('advances the search revision floor only with an atomic complete rebuild', function () {
    $user = User::withoutSyncingToSearch(fn () => User::factory()->create());
    $search = app(SearchIndex::class);
    $search->rebuild($user->account_id);

    expect($user->account->fresh()->search_rebuild_revision)->toBe(1);

    $search->mutate($user->account_id, fn () => tap($user)->update(['last_name' => 'After']));

    expect($user->account->fresh()->search_revision)->toBe(2)
        ->and($user->account->fresh()->search_rebuild_revision)->toBe(1);

    expect(fn () => DB::transaction(function () use ($search, $user): void {
        $search->rebuild($user->account_id);
        throw new RuntimeException('Abort complete rebuild');
    }))->toThrow(RuntimeException::class, 'Abort complete rebuild');

    expect($user->account->fresh()->search_revision)->toBe(2)
        ->and($user->account->fresh()->search_rebuild_revision)->toBe(1)
        ->and(DB::table('jobs')->count())->toBe(2);
});

it('rejects a mutation that moves a model to another account', function () {
    $user = User::withoutSyncingToSearch(fn () => User::factory()->create());
    $accountId = $user->account_id;
    $other = Account::factory()->create();

    expect(fn () => app(SearchIndex::class)->mutate($accountId, fn () => tap($user)->update(['account_id' => $other->id])))
        ->toThrow(InvalidArgumentException::class);

    expect($user->fresh()->account_id)->toBe($accountId)
        ->and(DB::table('accounts')->where('id', $accountId)->value('search_revision'))->toBe(0)
        ->and(DB::table('jobs')->count())->toBe(0);
});

it('keeps database-backed Scout mutations transactional without projection jobs', function () {
    config()->set('scout.driver', 'collection');
    $user = User::factory()->create(['last_name' => 'Before']);
    $result = app(SearchIndex::class)->mutate($user->account_id, fn () => tap($user)->update(['last_name' => 'After']));

    expect($result)->toBe($user)
        ->and($user->fresh()->last_name)->toBe('After')
        ->and(DB::table('accounts')->where('id', $user->account_id)->value('search_revision'))->toBe(0)
        ->and(DB::table('jobs')->count())->toBe(0);
});

it('refuses pending or changed account revisions instead of returning stale readiness', function () {
    $account = Account::factory()->create();
    $search = app(SearchIndex::class);

    expect($search->assertReady($account->id))->toBe(0);
    DB::table('accounts')->where('id', $account->id)->update(['search_revision' => 1]);
    expect(fn () => $search->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);
    DB::table('accounts')->where('id', $account->id)->update(['indexed_revision' => 1]);

    expect($search->readState($account->id))->toBe(['revision' => 1, 'indexedRevision' => 1, 'rebuildRevision' => 0]);
    expect(fn () => $search->assertUnchanged($account->id, 0))->toThrow(SearchIndexUnavailable::class);
    $search->assertUnchanged($account->id, 1);
});

it('fences existing accounts during migration and starts new empty accounts ready', function () {
    $existing = Account::factory()->create();
    $migration = require database_path('migrations/2026_09_12_101331_add_search_revisions_to_accounts_table.php');
    $migration->down();
    $migration->up();
    $new = Account::factory()->create();
    $search = app(SearchIndex::class);

    expect($search->readState($existing->id))->toBe(['revision' => 0, 'indexedRevision' => null, 'rebuildRevision' => 0]);
    expect(fn () => $search->assertReady($existing->id))->toThrow(SearchIndexUnavailable::class);
    expect($search->assertReady($new->id))->toBe(0);
});

it('records a full projection when hard deletion detaches organization contacts', function () {
    $organization = Organization::withoutSyncingToSearch(fn () => Organization::factory()->create());
    $contact = Contact::withoutSyncingToSearch(fn () => Contact::factory()->create([
        'account_id' => $organization->account_id,
        'organization_id' => $organization->id,
    ]));
    app(SearchIndex::class)->mutate($organization->account_id, fn () => tap($organization)->forceDelete());

    $payload = json_decode(DB::table('jobs')->sole()->payload, true, flags: JSON_THROW_ON_ERROR);
    $job = unserialize($payload['data']['command']);

    expect($contact->fresh()->organization_id)->toBeNull()
        ->and($contact->account->fresh()->search_rebuild_revision)->toBe(1)
        ->and($job->modelClass)->toBeNull()
        ->and($job->modelId)->toBeNull()
        ->and($job->revision)->toBe(1);
});

it('keeps a projection pending while another worker holds the account lock', function () {
    $user = User::withoutSyncingToSearch(fn () => User::factory()->create());
    $search = app(SearchIndex::class);
    $search->mutate($user->account_id, fn () => tap($user)->update(['last_name' => 'After']));
    $lock = Cache::lock('search-index:'.$user->account_id, 30);
    $lock->get();

    try {
        expect(fn () => $search->project(new SearchIndexProjection($user->account_id, 1, User::class, $user->id)))
            ->toThrow(SearchIndexUnavailable::class);
        expect($search->readState($user->account_id))->toBe(['revision' => 1, 'indexedRevision' => 0, 'rebuildRevision' => 0]);
    } finally {
        $lock->release();
    }
});

it('does not exhaust job attempts while an account projection is waiting for its lock', function () {
    $user = User::withoutSyncingToSearch(fn () => User::factory()->create());
    app(SearchIndex::class)->mutate($user->account_id, fn () => tap($user)->update(['last_name' => 'After']));
    DB::table('jobs')->update(['attempts' => 25]);
    $lock = Cache::lock('search-index:'.$user->account_id, 30);
    $lock->get();

    try {
        $job = Queue::connection('search-index')->pop('search-index');
        app('queue.worker')->process('search-index', $job, new WorkerOptions);

        expect($job->hasFailed())->toBeFalse()
            ->and($job->isDeleted())->toBeTrue()
            ->and(DB::table('jobs')->sole()->attempts)->toBe(0)
            ->and(DB::table('jobs')->sole()->available_at)->toBeGreaterThanOrEqual(now()->addSeconds(config('search.retry_delay'))->timestamp);
    } finally {
        $lock->release();
    }
});

it('bounds actual projection failures separately from normal queue releases', function () {
    $user = User::withoutSyncingToSearch(fn () => User::factory()->create());
    app()->instance(Client::class, new Client([
        'api_key' => 'test-key',
        'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
        'num_retries' => 0,
        'client' => new HttpClient(['handler' => HandlerStack::create(new MockHandler(
            array_fill(0, 5, new Response(401, body: '{"message":"Unauthorized"}'))
        ))]),
    ]));
    $search = app(SearchIndex::class);
    $search->mutate($user->account_id, fn () => tap($user)->update(['last_name' => 'After']));
    $worker = app('queue.worker')->setCache(Cache::store());

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $job = Queue::connection('search-index')->pop('search-index');

        expect(fn () => $worker->process('search-index', $job, new WorkerOptions))
            ->toThrow(RequestUnauthorized::class);
        expect($job->hasFailed())->toBe($attempt === 5);
        $this->travel(121)->seconds();
    }

    expect(fn () => $search->assertReady($user->account_id))->toThrow(SearchIndexUnavailable::class);
});

it('rejects invalid projection budgets without leaving an account locked', function (string $setting, int $value) {
    config()->set('search.'.$setting, $value);
    $account = Account::factory()->create();

    expect(fn () => app(SearchIndex::class)->project(new SearchIndexProjection($account->id, 0)))
        ->toThrow(InvalidArgumentException::class);
    $lock = Cache::lock('search-index:'.$account->id, 30);
    expect($lock->get())->toBeTrue();
    $lock->release();
})->with([
    ['projection_batch_size', 0],
    ['projection_batch_size', 251],
    ['projection_seconds', 0],
    ['projection_seconds', 31],
]);
