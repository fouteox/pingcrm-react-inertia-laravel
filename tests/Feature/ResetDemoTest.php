<?php

declare(strict_types=1);

use App\Exceptions\SearchIndexUnavailable;
use App\Jobs\SearchIndexProjection;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Services\SearchIndex;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Typesense\ApiCall;
use Typesense\Client;
use Typesense\Collections;
use Typesense\Exceptions\TypesenseClientError;

function useVersionedResetTransport(int $accountId, Closure $write, array $remainingIds): void
{
    $api = Mockery::mock(ApiCall::class);
    $api->shouldReceive('post')->with(Mockery::type('string'), Mockery::type('array'), true, [])
        ->andReturnUsing(function (string $path, array $document) use ($accountId, $write, &$remainingIds): array {
            expect($path)->toMatch('#^/collections/(contacts|organizations|users)/documents/$#')
                ->and($document['account_id'])->toBe($accountId);
            $index = explode('/', $path)[2];
            $write($index, $document);
            $remainingIds[$index] = array_values(array_diff($remainingIds[$index] ?? [], [(int) $document['id']]));

            return $document;
        });
    $api->shouldReceive('get')->with(Mockery::type('string'), Mockery::type('array'))
        ->andReturnUsing(function (string $path, array $parameters) use ($accountId, &$remainingIds): array {
            expect($path)->toMatch('#^/collections/(contacts|organizations|users)/documents/search$#');
            expect($parameters)->toMatchArray([
                'q' => '*',
                'filter_by' => 'account_id:='.$accountId.' && search_deleted:!=true && (search_revision:<1 || search_revision:!=[0..'.PHP_INT_MAX.'])',
                'include_fields' => 'id',
                'page' => 1,
                'filter_curated_hits' => true,
                'enable_overrides' => false,
                'use_cache' => false,
            ])->toHaveCount(8);
            expect($parameters['per_page'])->toBeInt()->toBeGreaterThan(0)->toBeLessThanOrEqual(250);
            $ids = $remainingIds[explode('/', $path)[2]] ?? [];

            return [
                'found' => count($ids),
                'hits' => array_map(
                    fn (int $id): array => ['document' => ['id' => (string) $id]],
                    array_slice($ids, 0, $parameters['per_page'])
                ),
                'search_cutoff' => false,
            ];
        });
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->andReturn(new Collections($api));
    app()->instance(Client::class, $client);
    config()->set(['scout.driver' => 'typesense', 'scout.prefix' => '', 'search.synchronous' => false]);
}

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

it('releases a failed full projection and retries the current snapshot without losing newer pending work', function () {
    $account = Account::factory()->create(['search_revision' => 1]);
    $contact = Contact::factory()->for($account)->create(['first_name' => 'Before']);
    $unavailable = true;
    $indexed = [];

    useVersionedResetTransport($account->id, function (string $index, array $document) use (&$unavailable, &$indexed): void {
        if ($unavailable) {
            throw new TypesenseClientError('Search index unavailable.');
        }

        $indexed[$index][$document['id']] = $document;
    }, ['contacts' => [$contact->id]]);

    $search = app(SearchIndex::class);
    $fullProjection = new SearchIndexProjection($account->id, 1);

    expect(fn () => $search->project($fullProjection))->toThrow(TypesenseClientError::class, 'Search index unavailable.');
    expect($account->fresh()->indexed_revision)->toBe(0);
    $this->assertModelExists($contact);

    $releasedLock = Cache::lock('search-index:'.$account->id, 60);
    expect($releasedLock->get())->toBeTrue();
    $releasedLock->release();

    $search->mutate($account->id, function () use ($contact): Contact {
        $contact->update(['first_name' => 'After']);
        $contact->delete();

        return $contact;
    });
    $unavailable = false;
    $search->project($fullProjection);

    expect($indexed['contacts'][$contact->id])->toMatchArray([
        'first_name' => 'After',
        '__soft_deleted' => 1,
        'search_deleted' => false,
        'search_revision' => 1,
    ]);
    expect(fn () => $search->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);

    $payload = json_decode(DB::table('jobs')->sole()->payload, true, flags: JSON_THROW_ON_ERROR);
    $search->project(unserialize($payload['data']['command']));

    expect($search->assertReady($account->id))->toBe(2)
        ->and($indexed['contacts'][$contact->id]['search_revision'])->toBe(2);
});

it('keeps soft-deleted documents and writes tombstones for obsolete IDs during a full projection', function (string $modelClass, string $index) {
    $account = Account::factory()->create(['search_revision' => 1]);
    $records = $modelClass::factory(2)->for($account)->create();
    $records->last()->delete();
    $indexed = [];

    useVersionedResetTransport($account->id, function (string $collection, array $document) use (&$indexed): void {
        $indexed[$collection][] = $document;
    }, [$index => [...$records->modelKeys(), 999999]]);

    $search = app(SearchIndex::class);
    $search->project(new SearchIndexProjection($account->id, 1));

    expect($indexed)->toHaveCount(1)
        ->and(array_column($indexed[$index], 'id'))->toBe([...$records->map(fn ($record): string => (string) $record->id)->all(), '999999'])
        ->and(array_column($indexed[$index], '__soft_deleted'))->toBe([0, 1, 1])
        ->and(array_column($indexed[$index], 'search_deleted'))->toBe([false, false, true])
        ->and(array_column($indexed[$index], 'search_revision'))->toBe([1, 1, 1])
        ->and($search->assertReady($account->id))->toBe(1);
})->with([
    'contacts' => [Contact::class, 'contacts'],
    'organizations' => [Organization::class, 'organizations'],
    'users' => [User::class, 'users'],
]);

it('does not acknowledge a mutation that arrives while the full snapshot is being written', function () {
    $account = Account::factory()->create(['search_revision' => 1]);
    $contact = Contact::factory()->for($account)->create(['first_name' => 'Before']);
    $written = [];

    useVersionedResetTransport($account->id, function (string $index, array $document) use ($account, $contact, &$written): void {
        $written[] = $document;

        if (count($written) === 1) {
            app(SearchIndex::class)->mutate($account->id, fn () => tap($contact)->update(['first_name' => 'After']));
        }
    }, ['contacts' => [$contact->id]]);

    $search = app(SearchIndex::class);
    $search->project(new SearchIndexProjection($account->id, 1));

    expect($written)->toHaveCount(1)
        ->and($written[0]['first_name'])->toBe('Before')
        ->and($account->fresh()->indexed_revision)->toBe(1)
        ->and($account->fresh()->search_revision)->toBe(2);
    expect(fn () => $search->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);

    $payload = json_decode(DB::table('jobs')->sole()->payload, true, flags: JSON_THROW_ON_ERROR);
    $search->project(unserialize($payload['data']['command']));

    expect($written)->toHaveCount(2)
        ->and($written[1]['first_name'])->toBe('After')
        ->and($written[1]['search_revision'])->toBe(2)
        ->and($search->assertReady($account->id))->toBe(2);
});
