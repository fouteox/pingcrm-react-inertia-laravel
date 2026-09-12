<?php

declare(strict_types=1);

use App\Jobs\RebuildSearchGeneration;
use App\Models\Account;
use App\Models\Contact;
use App\Services\SearchGenerations;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\DB;
use Typesense\Client;
use Typesense\Exceptions\RequestUnauthorized;

beforeEach(function () {
    config()->set(['scout.driver' => 'typesense', 'search.synchronous' => false]);
});

function generationTransport(array $responses): void
{
    app()->instance(Client::class, new Client([
        'api_key' => 'test-key',
        'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
        'num_retries' => 0,
        'client' => new HttpClient(['handler' => HandlerStack::create(new MockHandler($responses))]),
    ]));
}

it('queues a global rebuild without invalidating business revisions or switching live collections', function () {
    $account = Account::factory()->create();
    $before = $account->fresh()->getAttributes();
    $generations = app(SearchGenerations::class);
    $generation = $generations->start();
    $job = unserialize(json_decode(DB::table('jobs')->sole()->payload, true, flags: JSON_THROW_ON_ERROR)['data']['command']);

    expect($generations->activeGeneration())->toBeNull()
        ->and($account->fresh()->getAttributes())->toBe($before)
        ->and($job)->toBeInstanceOf(RebuildSearchGeneration::class)
        ->and($job->generation)->toBe($generation)
        ->and(SearchGenerations::collection(new Contact, $generation))->toContain($generation);
});

it('rolls back the new generation and durable job with its surrounding transaction', function () {
    $generations = app(SearchGenerations::class);
    expect(fn () => DB::transaction(function () use ($generations): void {
        $generations->start();
        throw new RuntimeException('Abort');
    }))->toThrow(RuntimeException::class, 'Abort');

    expect(DB::table('search_index_manifest')->value('building_generation'))->toBeNull()
        ->and(DB::table('jobs')->count())->toBe(0);
});

it('keeps live search readiness when preparing the candidate fails', function () {
    $account = Account::factory()->create();
    generationTransport([new Response(401, body: '{"message":"Unauthorized"}')]);
    $generations = app(SearchGenerations::class);
    $generation = $generations->start();

    expect(fn () => $generations->project($generation))->toThrow(RequestUnauthorized::class);
    expect($generations->activeGeneration())->toBeNull()
        ->and($account->fresh()->indexed_revision)->toBe(0)
        ->and($account->fresh()->search_revision)->toBe(0)
        ->and(DB::table('jobs')->count())->toBe(1);
});

it('switches a complete empty generation without changing account readiness and ignores its replay', function () {
    $account = Account::factory()->create();
    generationTransport([new Response(201, body: '{}'), new Response(201, body: '{}'), new Response(201, body: '{}')]);
    $generations = app(SearchGenerations::class);
    $generation = $generations->start();

    expect($generations->project($generation))->toBeTrue()
        ->and($generations->activeGeneration())->toBe($generation)
        ->and($generations->project($generation))->toBeTrue()
        ->and($account->fresh()->search_revision)->toBe(0)
        ->and($account->fresh()->indexed_revision)->toBe(0);
});

it('registers candidate changes transactionally and deduplicates retried live snapshots', function () {
    $contacts = Contact::withoutSyncingToSearch(fn () => Contact::factory()->count(2)->for(Account::factory())->create());
    config()->set('search.projection_batch_size', 1);
    generationTransport(array_map(fn () => new Response(201, body: '{}'), range(1, 4)));
    $generations = app(SearchGenerations::class);
    $generation = $generations->start();
    expect($generations->project($generation))->toBeFalse();
    $model = $contacts->first();
    DB::transaction(function () use ($generations, $model, $generation): void {
        $targets = $generations->captureTargets();
        expect($targets)->toBe(['active' => null, 'building' => $generation]);
        $generations->recordChanges($targets, [$model], $model->account_id, 1);
        $generations->recordChanges($targets, [$model], $model->account_id, 1);
    });
    expect(DB::table('search_generation_changes')->count())->toBe(1);

    expect(fn () => DB::transaction(function () use ($generations, $model): void {
        $generations->recordChanges($generations->captureTargets(), [$model], $model->account_id, 2);
        throw new RuntimeException('Rollback journal');
    }))->toThrow(RuntimeException::class, 'Rollback journal');
    expect(DB::table('search_generation_changes')->count())->toBe(1);
});

it('offers an explicit global background rebuild without changing existing account command behavior', function () {
    Account::factory()->create();
    $this->artisan('search:rebuild', ['--background' => true])->assertSuccessful();
    expect(DB::table('search_index_manifest')->value('building_generation'))->not->toBeNull()
        ->and(DB::table('accounts')->value('search_revision'))->toBe(0);
});

it('waits for a background generation through the existing synchronous option', function () {
    Account::factory()->create();
    generationTransport(array_map(fn () => new Response(201, body: '{}'), range(1, 3)));
    $this->artisan('search:rebuild', ['--background' => true, '--sync' => true])->assertSuccessful();
    expect(DB::table('search_index_manifest')->value('active_generation'))->not->toBeNull()
        ->and(DB::table('search_index_manifest')->value('building_generation'))->toBeNull();
});
