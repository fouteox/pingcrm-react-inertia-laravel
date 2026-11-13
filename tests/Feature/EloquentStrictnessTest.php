<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Support\Facades\DB;

it('rejects lazy loading and silently discarded attributes outside production', function () {
    expect(Model::preventsLazyLoading())->toBeTrue()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeTrue();

    $organization = Organization::factory()->create();
    Contact::factory()->count(2)->for($organization)->for($organization->account)->create();

    expect(fn () => Contact::query()->get()->first()->organization)
        ->toThrow(LazyLoadingViolationException::class)
        ->and(fn () => new Organization(['unknown_column' => 'value']))
        ->toThrow(MassAssignmentException::class);
});

it('loads the organizations of a batch of contacts in one query before indexing them', function () {
    $organization = Organization::factory()->create();
    Contact::factory()->count(3)->for($organization)->for($organization->account)->create();

    $contacts = Contact::query()->get();

    DB::enableQueryLog();
    $documents = (new Contact)->makeSearchableUsing($contacts)->map->toSearchableArray();

    expect(DB::getQueryLog())->toHaveCount(1)
        ->and($documents->pluck('organization_name')->unique()->all())->toBe([$organization->name]);
});
