<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Services\SearchIndex;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Scout\EngineManager;
use Symfony\Component\Process\Process;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;
use Typesense\Exceptions\RequestUnauthorized;

beforeEach(function () {
    if (! filter_var(env('RUN_DEMO_RESET_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
        $this->markTestSkipped('PostgreSQL 18 and Typesense 30.2 integration services are not enabled.');
    }

    expect(DB::getDriverName())->toBe('pgsql');

    config()->set([
        'cache.default' => 'array',
        'queue.default' => 'sync',
        'scout.driver' => 'typesense',
        'scout.prefix' => 'pingcrm_demo_reset_integration_',
        'scout.queue' => false,
    ]);
    app(EngineManager::class)->forgetEngines();

    Artisan::call('migrate:fresh', ['--force' => true]);

    $typesense = new Client(config('scout.typesense.client-settings'));

    foreach ([new User, new Organization, new Contact] as $model) {
        try {
            $typesense->getCollections()->{$model->indexableAs()}->delete();
        } catch (ObjectNotFound) {
            // The collection does not exist before its first import.
        }
    }

    Artisan::call('search:sync-schema');
});

it('upgrades the old Typesense schema from stored documents while legacy writers remain compatible', function () {
    $account = Account::factory()->create();
    $contact = Contact::withoutSyncingToSearch(fn () => Contact::factory()->for($account)->create([
        'first_name' => 'Database',
        'last_name' => 'Version',
        'email' => null,
    ]));
    $schema = config('scout.typesense.model-settings.'.Contact::class.'.collection-schema');
    $schema['fields'] = collect($schema['fields'])
        ->reject(fn (array $field) => in_array($field['name'], ['search_revision', 'search_deleted'], true))
        ->map(fn (array $field) => isset($field['sort']) ? [...$field, 'sort' => false] : $field)
        ->push(['name' => 'custom_field', 'type' => 'string', 'optional' => true, 'facet' => true])
        ->values()->all();
    $typesense = new Client(config('scout.typesense.client-settings'));
    $typesense->getCollections()->{$contact->indexableAs()}->delete();
    $typesense->getCollections()->create(['name' => $contact->indexableAs(), ...$schema]);
    $collection = $typesense->getCollections()->{$contact->indexableAs()};
    $document = [
        'id' => (string) $contact->id,
        'account_id' => $account->id,
        'first_name' => 'Stored',
        'last_name' => 'Version',
        'email' => '',
        'organization_name' => '',
        'created_at' => 1,
        '__soft_deleted' => 0,
        'custom_field' => 'preserved',
    ];
    $collection->getDocuments()->upsert($document);
    config()->set('scout.typesense.model-settings', [
        Contact::class => config('scout.typesense.model-settings.'.Contact::class),
    ]);

    DB::enableQueryLog();
    $this->artisan('search:sync-schema')->assertSuccessful();
    $this->artisan('search:sync-schema')->assertSuccessful();
    expect(DB::getQueryLog())->not->toBeEmpty();

    foreach (DB::getQueryLog() as $query) {
        expect($query['query'])->toStartWith('select ')->toContain('search_index_manifest');
    }
    DB::disableQueryLog();

    $fields = collect($collection->retrieve()['fields'])->keyBy('name');
    expect($fields['first_name']['sort'])->toBeTrue()
        ->and($fields['last_name']['sort'])->toBeTrue()
        ->and($fields['custom_field']['facet'])->toBeTrue()
        ->and($fields->has('sort_id'))->toBeFalse()
        ->and($collection->getDocuments()[(string) $contact->id]->retrieve())->toEqual($document);

    $legacyWrite = [...$document, 'id' => '999', 'first_name' => 'Aardvark'];
    $collection->getDocuments()->upsert($legacyWrite);
    $results = $collection->getDocuments()->search([
        'q' => '*',
        'query_by' => 'first_name,last_name',
        'sort_by' => 'first_name:asc,last_name:asc',
    ]);
    expect($results['found'])->toBe(2)
        ->and(array_column(array_column($results['hits'], 'document'), 'id'))->toBe(['999', (string) $contact->id])
        ->and($contact->fresh()->first_name)->toBe('Database');
});

it('keeps typo tolerant search and tenant filtering through HTTP', function (string $resource, string $query) {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create([
        'first_name' => 'Test', 'last_name' => 'Administrator', 'email' => 'actor@example.test',
    ]);
    $organization = Organization::factory()->for($account)->create(['name' => 'Analytical Engines']);
    $contact = Contact::factory()->for($account)->for($organization)->create([
        'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => null,
    ]);
    $user = User::factory()->for($account)->create([
        'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.test',
    ]);
    $otherAccount = Account::factory()->create();
    $otherOrganization = Organization::factory()->for($otherAccount)->create(['name' => 'Analytical Engines']);
    Contact::factory()->for($otherAccount)->for($otherOrganization)->create([
        'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => null,
    ]);
    User::factory()->for($otherAccount)->create([
        'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'other-ada@example.test',
    ]);
    $expected = match ($resource) {
        'contacts' => $contact,
        'organizations' => $organization,
        'users' => $user,
    };

    app(SearchIndex::class)->rebuild($account->id);
    app(SearchIndex::class)->rebuild($otherAccount->id);

    $this->actingAs($actor)->get('/'.$resource.'?search='.rawurlencode($query))
        ->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert
            ->where($resource.'.meta.total', 1)
            ->has($resource.'.data', 1)
            ->where($resource.'.data.0.id', $expected->id));
})->with([
    'contact typo' => ['contacts', 'Lovelcae'],
    'user typo' => ['users', 'Lovelcae'],
    'organization typo' => ['organizations', 'Analyticl'],
    'related organization typo' => ['contacts', 'Analyticl'],
    'contact prefix' => ['contacts', 'Love'],
]);

it('serves stale search and refreshes native pagination beyond twelve hundred results', function () {
    $account = Account::factory()->create();
    $otherAccount = Account::factory()->create();
    $actor = User::factory()->for($account)->create([
        'first_name' => '0',
        'last_name' => 'Member',
        'email' => 'owner@example.test',
        'owner' => true,
    ]);
    $members = User::withoutSyncingToSearch(fn () => User::factory(1220)->for($account)
        ->sequence(fn (Sequence $sequence) => ['email' => 'member-'.$sequence->index.'@example.test'])
        ->create(['first_name' => '0', 'last_name' => 'Member', 'owner' => false]));
    $search = app(SearchIndex::class);
    $this->artisan('search:rebuild', ['account' => $account->id, '--sync' => true])->assertSuccessful();
    config()->set('search.synchronous', false);
    $search->mutate($account->id, fn () => tap($members[0])->forceDelete());
    $search->mutate($account->id, fn () => tap($members[1])->delete());
    $search->mutate($account->id, fn () => tap($members[2])->update(['owner' => true]));

    $typesense = new Client(config('scout.typesense.client-settings'));
    $indexed = $typesense->getCollections()->{$actor->indexableAs()}->getDocuments()->search([
        'q' => '0',
        'query_by' => 'first_name,last_name,email',
        'filter_by' => 'account_id:='.$account->id.' && owner:=false && __soft_deleted:=0',
        'sort_by' => 'last_name:asc,first_name:asc',
        'page' => 82,
        'per_page' => 15,
    ]);
    expect($indexed['found'])->toBe(1220);
    $stalePageIds = collect($indexed['hits'])->pluck('document.id')->map(fn (string $id): int => (int) $id)
        ->diff($members->take(3)->modelKeys())->values()->all();

    $this->actingAs($actor)->get('/users?search=0&role=user&page=82')->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert
            ->where('users.meta.total', 1220)
            ->where('users.meta.current_page', 82)
            ->where('users.data', function ($data) use ($stalePageIds): bool {
                expect(collect($data)->pluck('id')->all())->toBe($stalePageIds);

                return true;
            }));
    $this->artisan('queue:work', ['connection' => 'search-index', '--queue' => 'search-index', '--stop-when-empty' => true, '--sleep' => 0])->assertSuccessful();

    $eligibleIds = $members->slice(3)->modelKeys();
    $this->actingAs($actor)->get('/users?search=0&role=user&page=82')
        ->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert
            ->where('filters.search', '0')
            ->where('filters.role', 'user')
            ->where('users.meta.total', 1217)
            ->where('users.meta.current_page', 82)
            ->where('users.meta.last_page', 82)
            ->has('users.data', 2)
            ->where('users.data', fn ($data): bool => collect($data)->pluck('id')->diff($eligibleIds)->isEmpty()));
});

it('resets atomically on PostgreSQL 18 and converges Typesense 30.2', function () {
    $postgresVersion = DB::selectOne('show server_version')->server_version;
    $typesense = new Client(config('scout.typesense.client-settings'));

    expect($postgresVersion)->toStartWith('18.')
        ->and($typesense->getDebug()->retrieve()['version'])->toBe('30.2');

    $demoAccount = Account::factory()->create([
        'demo_key' => DatabaseSeeder::DEMO_ACCOUNT_KEY,
    ]);
    $demoUser = User::factory()->create([
        'account_id' => $demoAccount->id,
        'email' => DatabaseSeeder::DEMO_USER_EMAIL,
    ]);
    $obsoleteUser = User::factory()->create(['account_id' => $demoAccount->id]);
    $obsoleteOrganization = Organization::factory()->create(['account_id' => $demoAccount->id]);
    $obsoleteContact = Contact::factory()->create([
        'account_id' => $demoAccount->id,
        'organization_id' => $obsoleteOrganization->id,
    ]);

    $otherAccount = Account::factory()->create();
    $otherUser = User::factory()->create(['account_id' => $otherAccount->id]);
    $otherOrganization = Organization::factory()->create(['account_id' => $otherAccount->id]);
    $otherContact = Contact::factory()->create([
        'account_id' => $otherAccount->id,
        'organization_id' => $otherOrganization->id,
    ]);

    app(SearchIndex::class)->rebuild($demoAccount->id);
    app(SearchIndex::class)->rebuild($otherAccount->id);

    $this->artisan('demo:reset')->assertSuccessful();

    expect(User::findOrFail($demoUser->id)->account_id)->toBe($demoAccount->id)
        ->and(User::find($obsoleteUser->id))->toBeNull()
        ->and(Organization::find($obsoleteOrganization->id))->toBeNull()
        ->and(Contact::find($obsoleteContact->id))->toBeNull()
        ->and(User::whereBelongsTo($demoAccount)->count())->toBe(6)
        ->and(Organization::whereBelongsTo($demoAccount)->count())->toBe(100)
        ->and(Contact::whereBelongsTo($demoAccount)->count())->toBe(100);

    expect(fn () => app(SearchIndex::class)->assertReady($demoAccount->id))->toThrow(App\Exceptions\SearchIndexUnavailable::class);
    $this->artisan('queue:work', ['connection' => 'search-index', '--queue' => 'search-index', '--stop-when-empty' => true, '--sleep' => 0])->assertSuccessful();

    assertTypesenseTenantCount($typesense, new User, $demoAccount->id, 6);
    assertTypesenseTenantCount($typesense, new Organization, $demoAccount->id, 100);
    assertTypesenseTenantCount($typesense, new Contact, $demoAccount->id, 100);
    assertTypesenseTenantCount($typesense, new User, $otherAccount->id, 1);
    assertTypesenseTenantCount($typesense, new Organization, $otherAccount->id, 1);
    assertTypesenseTenantCount($typesense, new Contact, $otherAccount->id, 1);

    assertTypesenseDocumentTombstone($typesense, new User, $obsoleteUser->id);
    assertTypesenseDocumentTombstone($typesense, new Organization, $obsoleteOrganization->id);
    assertTypesenseDocumentTombstone($typesense, new Contact, $obsoleteContact->id);

    expect(User::findOrFail($otherUser->id)->account_id)->toBe($otherAccount->id)
        ->and(Organization::findOrFail($otherOrganization->id)->account_id)->toBe($otherAccount->id)
        ->and(Contact::findOrFail($otherContact->id)->account_id)->toBe($otherAccount->id);
});

it('keeps reads available and rejects a stale writer across the PostgreSQL cutover', function () {
    $demoAccount = Account::factory()->create([
        'demo_key' => DatabaseSeeder::DEMO_ACCOUNT_KEY,
    ]);
    User::factory()->create([
        'account_id' => $demoAccount->id,
        'email' => DatabaseSeeder::DEMO_USER_EMAIL,
    ]);
    $obsoleteOrganization = Organization::factory()->create(['account_id' => $demoAccount->id]);
    Contact::factory()->create([
        'account_id' => $demoAccount->id,
        'organization_id' => $obsoleteOrganization->id,
    ]);

    DB::unprepared(<<<'SQL'
        CREATE OR REPLACE FUNCTION hold_demo_reset_for_test() RETURNS trigger AS $$
        BEGIN
            PERFORM pg_advisory_xact_lock(20260812);
            RETURN NULL;
        END;
        $$ LANGUAGE plpgsql
        SQL);
    DB::unprepared(<<<'SQL'
        CREATE TRIGGER hold_demo_reset_for_test
        BEFORE DELETE ON contacts
        FOR EACH STATEMENT EXECUTE FUNCTION hold_demo_reset_for_test()
        SQL);
    DB::select('select pg_advisory_lock(?)', [20260812]);

    $reset = new Process([PHP_BINARY, 'artisan', 'demo:reset', '--no-interaction'], base_path());
    $reset->setTimeout(30);
    $writer = null;
    $advisoryLockHeld = true;

    try {
        $reset->start();

        expect(waitForPostgresLock('contacts', 'ShareRowExclusiveLock', true))->toBeTrue();

        $oldContactCount = DB::table('contacts')
            ->where('account_id', $demoAccount->id)
            ->count();

        $writerCode = <<<'PHP'
            require 'vendor/autoload.php';
            $app = require 'bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            Illuminate\Support\Facades\DB::table('contacts')->insert([
                'account_id' => (int) getenv('STALE_WRITER_ACCOUNT_ID'),
                'organization_id' => (int) getenv('STALE_WRITER_ORGANIZATION_ID'),
                'first_name' => 'Blocked',
                'last_name' => 'Writer',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            PHP;
        $writer = new Process([PHP_BINARY, '-r', $writerCode], base_path(), [
            'STALE_WRITER_ACCOUNT_ID' => (string) $demoAccount->id,
            'STALE_WRITER_ORGANIZATION_ID' => (string) $obsoleteOrganization->id,
        ]);
        $writer->setTimeout(30);
        $writer->start();

        expect(waitForPostgresLock('contacts', 'RowExclusiveLock', false))->toBeTrue()
            ->and($oldContactCount)->toBe(1);

        DB::select('select pg_advisory_unlock(?)', [20260812]);
        $advisoryLockHeld = false;

        $reset->wait();
        $writer->wait();

        expect($reset->getExitCode())->toBe(0)
            ->and($writer->getExitCode())->not->toBe(0)
            ->and($writer->getErrorOutput().$writer->getOutput())->toContain('SQLSTATE[23503]')
            ->and(Contact::where('account_id', $demoAccount->id)->count())->toBe(100)
            ->and(Organization::where('account_id', $demoAccount->id)->count())->toBe(100);
    } finally {
        if ($advisoryLockHeld) {
            DB::select('select pg_advisory_unlock(?)', [20260812]);
        }

        if ($reset->isRunning()) {
            $reset->stop(1);
        }

        if ($writer?->isRunning()) {
            $writer->stop(1);
        }

        DB::unprepared('DROP TRIGGER IF EXISTS hold_demo_reset_for_test ON contacts');
        DB::unprepared('DROP FUNCTION IF EXISTS hold_demo_reset_for_test()');
    }
});

function assertTypesenseTenantCount(Client $typesense, object $model, int $accountId, int $expected): void
{
    $documents = $typesense
        ->getCollections()
        ->{$model->indexableAs()}
        ->getDocuments()
        ->export(['filter_by' => 'account_id:='.$accountId.' && search_deleted:=false']);
    $count = count(array_filter(explode("\n", mb_trim($documents))));

    expect($count)->toBe($expected);
}

function assertTypesenseDocumentTombstone(Client $typesense, object $model, int $id): void
{
    expect($typesense->getCollections()->{$model->indexableAs()}->getDocuments()[(string) $id]->retrieve())
        ->toMatchArray(['search_deleted' => true, '__soft_deleted' => 1]);
}

function waitForPostgresLock(string $table, string $mode, bool $granted): bool
{
    $deadline = microtime(true) + 5;

    do {
        $found = DB::table('pg_locks')
            ->whereRaw('relation = ?::regclass', [$table])
            ->where('mode', $mode)
            ->where('granted', $granted)
            ->exists();

        if ($found) {
            return true;
        }

        usleep(20_000);
    } while (microtime(true) < $deadline);

    return false;
}

it('bootstraps existing data synchronously and reports readiness only after indexing', function () {
    $account = Account::factory()->create();
    $user = User::withoutSyncingToSearch(fn () => User::factory()->for($account)->create(['last_name' => 'Lovelace']));
    DB::table('accounts')->where('id', $account->id)->update(['indexed_revision' => null]);

    $this->actingAs($user)->get('/users?search=Lovelcae')->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert->where('users.meta.total', 0)->has('users.data', 0));
    $this->artisan('search:rebuild', ['--sync' => true])->assertSuccessful();
    $this->actingAs($user)->get('/users?search=Lovelcae')->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert->where('users.meta.total', 1)->where('users.data.0.id', $user->id));
    expect(app(SearchIndex::class)->assertReady($account->id))->toBe(1);
});

it('fails synchronous bootstrap without losing the durable retry when Typesense rejects indexing', function () {
    $account = Account::factory()->create();
    User::withoutSyncingToSearch(fn () => User::factory()->for($account)->create());
    config()->set('scout.typesense.client-settings.api_key', 'invalid-bootstrap-test-key');
    Exceptions::fake();

    $this->artisan('search:rebuild', ['account' => $account->id, '--sync' => true])->assertFailed();

    expect(app(SearchIndex::class)->readState($account->id))->toBe(['revision' => 1, 'indexedRevision' => 0, 'rebuildRevision' => 1])
        ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(1);
    Exceptions::assertReported(RequestUnauthorized::class);
});
