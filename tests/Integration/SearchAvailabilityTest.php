<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Services\SearchIndex;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Scout\EngineManager;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Process\Process;
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
        'scout.prefix' => 'pingcrm_resource_availability_',
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
            // Each test owns these collections, which are absent on its first run.
        }
    }

    Artisan::call('search:sync-schema');
});

/** @return array{Account, User, Organization, Collection<int, User>, Collection<int, Contact>} */
function resourceAvailabilityFixtures(): array
{
    $account = Account::factory()->create();
    $actor = User::withoutSyncingToSearch(fn () => User::factory()->for($account)->create([
        'first_name' => 'Viewer', 'last_name' => 'Administrator', 'owner' => true,
        'email' => 'viewer@availability.test',
    ]));
    $organization = Organization::withoutSyncingToSearch(fn () => Organization::factory()->for($account)->create([
        'name' => 'Analytical Laboratory',
    ]));
    $users = User::withoutSyncingToSearch(fn () => User::factory()->count(18)->for($account)
        ->sequence(fn (Sequence $sequence): array => [
            'first_name' => 'Ada', 'last_name' => sprintf('Candidate %02d', $sequence->index),
            'email' => "ada-{$sequence->index}@availability.test", 'owner' => false, 'deleted_at' => now(),
        ])->create());
    $contacts = Contact::withoutSyncingToSearch(fn () => Contact::factory()->count(18)->for($account)->for($organization)
        ->sequence(fn (Sequence $sequence): array => [
            'first_name' => 'Grace', 'last_name' => sprintf('Candidate %02d', $sequence->index),
            'email' => "grace-{$sequence->index}@availability.test", 'deleted_at' => $sequence->index < 4 ? now() : null,
        ])->create());
    User::withoutSyncingToSearch(function () use ($account): void {
        User::factory()->for($account)->create(['first_name' => 'Ada', 'last_name' => 'Active', 'owner' => false]);
        User::factory()->for($account)->create(['first_name' => 'Ada', 'last_name' => 'Owner', 'owner' => true, 'deleted_at' => now()]);
    });
    $otherAccount = Account::factory()->create();
    User::withoutSyncingToSearch(fn () => User::factory()->for($otherAccount)->create([
        'first_name' => 'Ada', 'last_name' => 'Another tenant', 'owner' => false, 'deleted_at' => now(),
    ]));
    Contact::withoutSyncingToSearch(fn () => Contact::factory()->for($otherAccount)->create(['first_name' => 'Grace']));
    app(SearchIndex::class)->rebuild($account->id);
    app(SearchIndex::class)->rebuild($otherAccount->id);
    config()->set('search.synchronous', false);

    return [$account, $actor, $organization, $users, $contacts];
}

/**
 * @param  list<int>  $ids
 * @param  array<string, string>  $filters
 */
function assertAvailableSecondPage(TestResponse $response, string $resource, array $ids, array $filters): void
{
    $response->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->component("{$resource}/index")
        ->where('filters', $filters)
        ->where("{$resource}.meta.total", count($ids))
        ->where("{$resource}.meta.current_page", 2)
        ->where("{$resource}.meta.last_page", 2)
        ->where("{$resource}.data", fn ($data): bool => collect($data)->pluck('id')->all() === array_slice($ids, 15))
        ->where("{$resource}.links.first", function (string $url) use ($filters): bool {
            parse_str(parse_url($url, PHP_URL_QUERY), $query);

            return $query === [...$filters, 'page' => '1'];
        }));
}

function mutateAvailabilityContactInAnotherProcess(Contact $contact): void
{
    $process = new Process([PHP_BINARY, '-r', <<<'PHP'
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $contact = App\Models\Contact::withTrashed()->findOrFail((int) $argv[1]);
        $app->make(App\Services\SearchIndex::class)->mutate($contact->account_id,
            fn () => tap($contact)->update(['last_name' => 'ZZZ Updated'])
        );
        PHP,
        (string) $contact->id,
    ], base_path(), ['SEARCH_SYNCHRONOUS' => 'false', 'SCOUT_PREFIX' => config('scout.prefix')]);
    $process->setTimeout(5)->mustRun();
}

it('keeps searched and unrelated pages available when contacts change before or during the engine response', function (bool $duringResponse) {
    [, $actor, , $users, $contacts] = resourceAvailabilityFixtures();
    $contact = $contacts->last();
    $changedDuringSearch = false;

    if ($duringResponse) {
        $handler = HandlerStack::create();
        $handler->push(function (callable $next) use ($contact, &$changedDuringSearch): Closure {
            return function (RequestInterface $request, array $options) use ($next, $contact, &$changedDuringSearch): PromiseInterface {
                if (! str_ends_with($request->getUri()->getPath(), '/documents/search') || $changedDuringSearch) {
                    return $next($request, $options);
                }

                return $next($request, $options)->then(function (ResponseInterface $response) use ($contact, &$changedDuringSearch): ResponseInterface {
                    mutateAvailabilityContactInAnotherProcess($contact);
                    $changedDuringSearch = true;

                    return $response;
                });
            };
        });
        app()->instance(Client::class, new Client([
            ...config('scout.typesense.client-settings'),
            'client' => new HttpClient(['handler' => $handler, 'connect_timeout' => 0.5, 'timeout' => 2]),
        ]));
        app(EngineManager::class)->forgetEngines();
    } else {
        mutateAvailabilityContactInAnotherProcess($contact);
    }

    $this->actingAs($actor);
    $usersUrl = '/users?search=Ada&role=user&trashed=only&page=2';
    $filters = ['search' => 'Ada', 'role' => 'user', 'trashed' => 'only'];
    $contactsUrl = '/contacts?search=Grase&trashed=with&page=2';
    $contactFilters = ['search' => 'Grase', 'trashed' => 'with'];
    assertAvailableSecondPage($this->get($contactsUrl), 'contacts', $contacts->modelKeys(), $contactFilters);
    assertAvailableSecondPage($this->get($usersUrl), 'users', $users->modelKeys(), $filters);
    expect($contact->fresh()->last_name)->toBe('ZZZ Updated')
        ->and($changedDuringSearch)->toBe($duringResponse);

    $this->artisan('queue:work', ['connection' => 'search-index', '--queue' => 'search-index', '--stop-when-empty' => true, '--sleep' => 0])->assertSuccessful();

    assertAvailableSecondPage($this->get($usersUrl), 'users', $users->modelKeys(), $filters);
    $response = $this->get($contactsUrl);
    assertAvailableSecondPage($response, 'contacts', $contacts->modelKeys(), $contactFilters);
    $response->assertInertia(fn (Assert $page) => $page->where('contacts.data.2.name', 'Grace ZZZ Updated'));
    expect(DB::table('failed_jobs')->count())->toBe(0);
})->with(['pending contact mutation' => false, 'concurrent contact mutation' => true]);

it('serves old organization search matches during a rename and refreshes them after projection', function () {
    [$account, $actor, $organization, $users, $contacts] = resourceAvailabilityFixtures();
    app(SearchIndex::class)->mutate($account->id, fn () => tap($organization)->update(['name' => 'Computing Laboratory']));
    $this->actingAs($actor);

    assertAvailableSecondPage($this->get('/users?search=Ada&role=user&trashed=only&page=2'), 'users', $users->modelKeys(), ['search' => 'Ada', 'role' => 'user', 'trashed' => 'only']);
    $this->get('/organizations?search=Analytical')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('organizations.meta.total', 1)
        ->where('organizations.data.0.id', $organization->id));
    assertAvailableSecondPage($this->get('/contacts?search=Analytical&trashed=with&page=2'), 'contacts', $contacts->modelKeys(), ['search' => 'Analytical', 'trashed' => 'with']);
    $this->get('/organizations?search=Computing')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('organizations.meta.total', 0)->has('organizations.data', 0));
    $this->get('/contacts?search=Computing&trashed=with')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('contacts.meta.total', 0)->has('contacts.data', 0));

    $this->artisan('queue:work', ['connection' => 'search-index', '--queue' => 'search-index', '--stop-when-empty' => true, '--sleep' => 0])->assertSuccessful();

    $response = $this->get('/contacts?search=Computing&trashed=with&page=2');
    assertAvailableSecondPage($response, 'contacts', $contacts->modelKeys(), ['search' => 'Computing', 'trashed' => 'with']);
    $response->assertInertia(fn (Assert $page) => $page->where('contacts.data.0.organization.name', 'Computing Laboratory'));
    $this->get('/organizations?search=Computing')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('organizations.meta.total', 1)
        ->where('organizations.data.0.id', $organization->id)
        ->where('organizations.data.0.name', 'Computing Laboratory'));
    $this->get('/contacts?search=Analytical&trashed=with')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('contacts.meta.total', 0)->has('contacts.data', 0));
});

it('keeps user and contact pagination available while a user mutation awaits projection', function () {
    [$account, $actor, , $users, $contacts] = resourceAvailabilityFixtures();
    app(SearchIndex::class)->mutate($account->id, fn () => tap($users->last())->update(['last_name' => 'ZZZ Updated']));
    $this->actingAs($actor);
    $contactsUrl = '/contacts?search=Grace&trashed=with&page=2';
    $filters = ['search' => 'Grace', 'trashed' => 'with'];

    assertAvailableSecondPage($this->get($contactsUrl), 'contacts', $contacts->modelKeys(), $filters);
    assertAvailableSecondPage($this->get('/users?search=Ada&role=user&trashed=only&page=2'), 'users', $users->modelKeys(), ['search' => 'Ada', 'role' => 'user', 'trashed' => 'only']);

    $this->artisan('queue:work', ['connection' => 'search-index', '--queue' => 'search-index', '--stop-when-empty' => true, '--sleep' => 0])->assertSuccessful();

    assertAvailableSecondPage($this->get($contactsUrl), 'contacts', $contacts->modelKeys(), $filters);
    $response = $this->get('/users?search=Ada&role=user&trashed=only&page=2');
    assertAvailableSecondPage($response, 'users', $users->modelKeys(), ['search' => 'Ada', 'role' => 'user', 'trashed' => 'only']);
    $response->assertInertia(fn (Assert $page) => $page->where('users.data.2.name', 'Ada ZZZ Updated'));
});

it('keeps old organization matches searchable without exposing the permanently deleted organization', function () {
    [$account, $actor, $organization, $users, $contacts] = resourceAvailabilityFixtures();
    $search = app(SearchIndex::class);
    config()->set('search.synchronous', true);
    $otherOrganization = $search->mutate($account->id, fn () => Organization::factory()->for($account)->create(['name' => 'Preserved Institute']));
    $otherContact = $search->mutate($account->id, fn () => Contact::factory()->for($account)->for($otherOrganization)->create([
        'first_name' => 'Katherine', 'deleted_at' => now(),
    ]));
    $otherAccount = Account::factory()->create();
    $foreignOrganization = $search->mutate($otherAccount->id, fn () => Organization::factory()->for($otherAccount)->create(['name' => 'Foreign Institute']));
    $foreignContact = $search->mutate($otherAccount->id, fn () => Contact::factory()->for($otherAccount)->for($foreignOrganization)->create([
        'first_name' => 'Margaret', 'deleted_at' => now(),
    ]));
    $documents = app(Client::class)->getCollections()->{(new Contact)->indexableAs()}->getDocuments();
    $otherDocument = $documents[(string) $otherContact->id]->retrieve();
    $foreignDocument = $documents[(string) $foreignContact->id]->retrieve();
    config()->set('search.synchronous', false);

    $search->mutate($account->id, fn () => tap($organization)->forceDelete());
    $this->actingAs($actor);
    $usersUrl = '/users?search=Ada&role=user&trashed=only&page=2';
    $userFilters = ['search' => 'Ada', 'role' => 'user', 'trashed' => 'only'];
    assertAvailableSecondPage($this->get($usersUrl), 'users', $users->modelKeys(), $userFilters);
    $pendingResponse = $this->get('/contacts?search=Analytical&trashed=with&page=2');
    assertAvailableSecondPage($pendingResponse, 'contacts', $contacts->modelKeys(), ['search' => 'Analytical', 'trashed' => 'with']);
    $pendingResponse->assertInertia(fn (Assert $page) => $page->where('contacts.data', fn ($data): bool => collect($data)->every(fn (array $contact): bool => $contact['organization'] === null)));
    $this->get('/organizations?search=Analytical')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('organizations.meta.total', 1)->has('organizations.data', 0));

    $this->artisan('queue:work', ['connection' => 'search-index', '--queue' => 'search-index', '--stop-when-empty' => true, '--sleep' => 0])->assertSuccessful();

    assertAvailableSecondPage($this->get($usersUrl), 'users', $users->modelKeys(), $userFilters);
    $response = $this->get('/contacts?search=Grace&trashed=with&page=2');
    assertAvailableSecondPage($response, 'contacts', $contacts->modelKeys(), ['search' => 'Grace', 'trashed' => 'with']);
    $response->assertInertia(fn (Assert $page) => $page->where('contacts.data', fn ($data): bool => collect($data)->every(fn (array $contact): bool => $contact['organization'] === null)));

    foreach ($contacts as $contact) {
        expect($documents[(string) $contact->id]->retrieve())->toMatchArray([
            'organization_name' => '', '__soft_deleted' => (int) $contact->trashed(), 'search_deleted' => false,
        ]);
    }

    expect($documents[(string) $otherContact->id]->retrieve())->toBe($otherDocument)
        ->and($documents[(string) $foreignContact->id]->retrieve())->toBe($foreignDocument);
    $this->get('/organizations?search=Analytical')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('organizations.meta.total', 0)->has('organizations.data', 0));
});

it('keeps existing search pages available while an account repair awaits projection', function () {
    [$account, $actor, , $users, $contacts] = resourceAvailabilityFixtures();
    app(SearchIndex::class)->rebuild($account->id);
    $this->actingAs($actor);
    $usersUrl = '/users?search=Ada&role=user&trashed=only&page=2';
    $userFilters = ['search' => 'Ada', 'role' => 'user', 'trashed' => 'only'];
    $contactsUrl = '/contacts?search=Grase&trashed=with&page=2';
    $contactFilters = ['search' => 'Grase', 'trashed' => 'with'];

    assertAvailableSecondPage($this->get($usersUrl), 'users', $users->modelKeys(), $userFilters);
    assertAvailableSecondPage($this->get($contactsUrl), 'contacts', $contacts->modelKeys(), $contactFilters);

    $this->artisan('queue:work', ['connection' => 'search-index', '--queue' => 'search-index', '--stop-when-empty' => true, '--sleep' => 0])->assertSuccessful();

    assertAvailableSecondPage($this->get($usersUrl), 'users', $users->modelKeys(), $userFilters);
    assertAvailableSecondPage($this->get($contactsUrl), 'contacts', $contacts->modelKeys(), $contactFilters);
    expect(DB::table('failed_jobs')->count())->toBe(0);
});

it('omits deleted contacts from an old search page while retaining the engine total until projection', function (bool $permanent) {
    [$account, $actor, , , $contacts] = resourceAvailabilityFixtures();
    $visibleIds = $contacts->reject(fn (Contact $contact): bool => $contact->trashed())->values()->modelKeys();
    $contact = $contacts->last();
    $search = app(SearchIndex::class);
    $search->mutate($account->id, fn () => $permanent ? tap($contact)->forceDelete() : tap($contact)->delete());
    $remainingIds = array_values(array_diff($visibleIds, [$contact->id]));
    $this->actingAs($actor);

    $this->get('/contacts?search=Grase')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('filters', ['search' => 'Grase'])
        ->where('contacts.meta.total', count($visibleIds))
        ->where('contacts.meta.current_page', 1)
        ->where('contacts.data', fn ($data): bool => collect($data)->pluck('id')->all() === $remainingIds));

    $this->artisan('queue:work', ['connection' => 'search-index', '--queue' => 'search-index', '--stop-when-empty' => true, '--sleep' => 0])->assertSuccessful();

    $this->get('/contacts?search=Grase')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('contacts.meta.total', count($remainingIds))
        ->where('contacts.data', fn ($data): bool => collect($data)->pluck('id')->all() === $remainingIds));
    expect(DB::table('failed_jobs')->count())->toBe(0);
})->with(['soft deletion' => false, 'permanent deletion' => true]);

it('rechecks current SQL roles while retaining the old engine pagination until projection', function () {
    [$account, $actor, , $users] = resourceAvailabilityFixtures();
    $user = $users->last();
    app(SearchIndex::class)->mutate($account->id, fn () => tap($user)->update(['owner' => true]));
    $remainingIds = array_slice($users->modelKeys(), 15, 2);
    $this->actingAs($actor);
    $url = '/users?search=Ada&role=user&trashed=only&page=2';

    $this->get($url)->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('filters', ['search' => 'Ada', 'role' => 'user', 'trashed' => 'only'])
        ->where('users.meta.total', 18)
        ->where('users.meta.current_page', 2)
        ->where('users.meta.last_page', 2)
        ->where('users.data', fn ($data): bool => collect($data)->pluck('id')->all() === $remainingIds));

    $this->artisan('queue:work', ['connection' => 'search-index', '--queue' => 'search-index', '--stop-when-empty' => true, '--sleep' => 0])->assertSuccessful();

    $this->get($url)->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('users.meta.total', 17)
        ->where('users.meta.current_page', 2)
        ->where('users.meta.last_page', 2)
        ->where('users.data', fn ($data): bool => collect($data)->pluck('id')->all() === $remainingIds));
});

it('does not expose another tenant when an indexed account identifier is stale', function () {
    [$account, $actor, , , $contacts] = resourceAvailabilityFixtures();
    $foreignContact = Contact::query()->where('account_id', '!=', $account->id)->firstOrFail();
    $documents = app(Client::class)->getCollections()->{(new Contact)->indexableAs()}->getDocuments();
    $documents[(string) $foreignContact->id]->update(['account_id' => $account->id]);
    $visibleIds = $contacts->reject(fn (Contact $contact): bool => $contact->trashed())->values()->modelKeys();
    $this->actingAs($actor);

    $this->get('/contacts?search=Grase')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('contacts.meta.total', count($visibleIds) + 1)
        ->where('contacts.data', fn ($data): bool => collect($data)->pluck('id')->all() === $visibleIds));

    app(SearchIndex::class)->rebuild($foreignContact->account_id);
    $this->artisan('queue:work', ['connection' => 'search-index', '--queue' => 'search-index', '--stop-when-empty' => true, '--sleep' => 0])->assertSuccessful();

    $this->get('/contacts?search=Grase')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
        ->where('contacts.meta.total', count($visibleIds))
        ->where('contacts.data', fn ($data): bool => collect($data)->pluck('id')->all() === $visibleIds));
    expect($documents[(string) $foreignContact->id]->retrieve()['account_id'])->toBe($foreignContact->account_id)
        ->and(DB::table('failed_jobs')->count())->toBe(0);
});
