<?php

declare(strict_types=1);

use App\Exceptions\SearchIndexUnavailable;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Services\SearchIndex;
use Illuminate\Support\Facades\DB;

it('keeps unrelated resource searches ready while a mutation is waiting for indexing', function (string $changed, string $available) {
    $model = $changed::factory()->create();
    config()->set(['scout.driver' => 'typesense', 'search.synchronous' => false]);
    $search = app(SearchIndex::class);
    $state = $search->readyState($model->account_id, $available);

    $search->mutate($model->account_id, fn () => tap($model)->update([
        $changed === Organization::class ? 'name' : 'last_name' => 'Changed',
    ]));

    expect($search->readyState($model->account_id, $available)['revision'])->toBe($state['revision']);
    $search->assertUnchanged($model->account_id, $state['revision'], $available);
    expect(fn () => $search->readyState($model->account_id, $changed))->toThrow(SearchIndexUnavailable::class);
})->with([
    'contact does not block users' => [Contact::class, User::class],
    'contact does not block organizations' => [Contact::class, Organization::class],
    'user does not block contacts' => [User::class, Contact::class],
    'user does not block organizations' => [User::class, Organization::class],
    'organization does not block users' => [Organization::class, User::class],
]);

it('keeps contacts unavailable until their organization change has been indexed', function () {
    $organization = Organization::factory()->create();
    Contact::factory()->for($organization->account)->for($organization)->create();
    config()->set(['scout.driver' => 'typesense', 'search.synchronous' => false]);
    $search = app(SearchIndex::class);
    $search->mutate($organization->account_id, fn () => tap($organization)->update(['name' => 'Changed']));

    expect(fn () => $search->readyState($organization->account_id, Contact::class))->toThrow(SearchIndexUnavailable::class);
    expect(fn () => $search->readyState($organization->account_id, Organization::class))->toThrow(SearchIndexUnavailable::class);
});

it('detects a relevant concurrent change even after its indexing has completed', function () {
    $contact = Contact::factory()->create();
    config()->set(['scout.driver' => 'typesense', 'search.synchronous' => false]);
    $search = app(SearchIndex::class);
    $state = $search->readyState($contact->account_id, Contact::class);
    $search->mutate($contact->account_id, fn () => tap($contact)->update(['last_name' => 'Changed']));
    DB::table('accounts')->where('id', $contact->account_id)->update(['indexed_revision' => 1]);

    expect(fn () => $search->assertUnchanged($contact->account_id, $state['revision'], Contact::class))
        ->toThrow(SearchIndexUnavailable::class);
});

it('retains the conservative guard for revisions written by an older application version', function () {
    $contact = Contact::factory()->create();
    DB::table('accounts')->where('id', $contact->account_id)->update(['search_revision' => 1]);

    expect(fn () => app(SearchIndex::class)->readyState($contact->account_id, User::class))
        ->toThrow(SearchIndexUnavailable::class);
});

it('keeps an older untracked mutation fenced after a newer resource-specific mutation', function () {
    $contact = Contact::factory()->create();
    DB::table('accounts')->where('id', $contact->account_id)->update(['search_revision' => 1]);
    config()->set(['scout.driver' => 'typesense', 'search.synchronous' => false]);
    $search = app(SearchIndex::class);
    $search->mutate($contact->account_id, fn () => tap($contact)->update(['last_name' => 'Changed']));

    expect(fn () => $search->readyState($contact->account_id, User::class))->toThrow(SearchIndexUnavailable::class);
});

it('preserves pending changes when resource revision tracking is introduced', function () {
    $contact = Contact::factory()->create();
    DB::table('accounts')->where('id', $contact->account_id)->update(['search_revision' => 4, 'indexed_revision' => 3]);
    $migration = require database_path('migrations/2026_09_12_152626_add_resource_search_revisions_to_accounts_table.php');
    $migration->down();
    $migration->up();
    $search = app(SearchIndex::class);

    foreach ([Contact::class, Organization::class, User::class] as $model) {
        expect(fn () => $search->readyState($contact->account_id, $model))->toThrow(SearchIndexUnavailable::class);
    }

    DB::table('accounts')->where('id', $contact->account_id)->update(['indexed_revision' => 4]);

    foreach ([Contact::class, Organization::class, User::class] as $model) {
        expect($search->readyState($contact->account_id, $model)['revision'])->toBe(4);
    }
});

it('keeps user search ready after permanently deleting an organization and detaching its contacts', function () {
    $organization = Organization::factory()->create();
    $contact = Contact::factory()->for($organization->account)->for($organization)->create();
    config()->set(['scout.driver' => 'typesense', 'search.synchronous' => false]);
    $search = app(SearchIndex::class);
    $search->mutate($organization->account_id, fn () => tap($organization)->forceDelete());

    expect($contact->fresh()->organization_id)->toBeNull();
    expect($search->readyState($organization->account_id, User::class)['revision'])->toBe(0);
    expect(fn () => $search->readyState($organization->account_id, Contact::class))->toThrow(SearchIndexUnavailable::class);
});
