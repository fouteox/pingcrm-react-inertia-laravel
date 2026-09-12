<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\ResourceFilters;
use App\Http\Requests\OrganizationsRequest;
use App\Http\Resources\OrganizationCollection;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\SearchIndex;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

final class OrganizationsController extends Controller
{
    public function __construct(private readonly SearchIndex $search) {}

    #[Authorize('viewAny', Organization::class)]
    public function index(Request $request, #[CurrentUser] User $authenticatedUser): Response
    {
        $filters = ResourceFilters::fromRequest($request);

        return Inertia::render('organizations/index', [
            'filters' => $filters->toArray(),
            'organizations' => new OrganizationCollection(
                Organization::paginateFiltered($filters->toArray(), $authenticatedUser->account_id)
                    ->withQueryString()
            ),
        ]);
    }

    #[Authorize('create', Organization::class)]
    public function create(): Response
    {
        return Inertia::render('organizations/create');
    }

    #[Authorize('create', Organization::class)]
    public function store(OrganizationsRequest $request, #[CurrentUser] User $authenticatedUser): RedirectResponse
    {
        $this->search->mutate($authenticatedUser->account_id,
            fn () => $authenticatedUser->account()->firstOrFail()->organizations()->create($request->validated())
        );

        Inertia::flash('success', translate_with_gender('created', 'Organization'));

        return Redirect::route('organizations.index');
    }

    #[Authorize('update', 'organization')]
    public function edit(Organization $organization): Response
    {
        $organization->load(['contacts' => fn ($query) => $query->orderByName()]);

        return Inertia::render('organizations/edit', [
            'organization' => new OrganizationResource($organization),
        ]);
    }

    #[Authorize('update', 'organization')]
    public function update(Organization $organization, OrganizationsRequest $request): RedirectResponse
    {
        $this->search->mutate($organization->account_id, fn () => tap($organization)->update($request->validated()));

        Inertia::flash('success', translate_with_gender('updated', 'Organization'));

        return Redirect::back();
    }

    #[Authorize('delete', 'organization')]
    public function destroy(Organization $organization): RedirectResponse
    {
        $this->search->mutate($organization->account_id, fn () => tap($organization)->delete());

        Inertia::flash('success', translate_with_gender('deleted', 'Organization'));

        return Redirect::back();
    }

    #[Authorize('restore', 'organization')]
    public function restore(Organization $organization): RedirectResponse
    {
        $this->search->mutate($organization->account_id, fn () => tap($organization)->restore());

        Inertia::flash('success', translate_with_gender('restored', 'Organization'));

        return Redirect::back();
    }
}
