<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\OrganizationsFilters;
use App\Http\Requests\OrganizationsRequest;
use App\Http\Resources\OrganizationCollection;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

final class OrganizationsController extends Controller
{
    #[Authorize('viewAny', Organization::class)]
    public function index(Request $request): Response
    {
        $filters = OrganizationsFilters::fromRequest($request);

        return Inertia::render('organizations/index', [
            'filters' => $filters->toArray(),
            'organizations' => new OrganizationCollection(
                Auth::user()->account->organizations()
                    ->orderBy('name')
                    ->filter($filters->toArray(), Auth::user()->account_id)
                    ->paginate()
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
    public function store(OrganizationsRequest $request): RedirectResponse
    {
        Auth::user()->account->organizations()->create($request->validated());

        return Redirect::route('organizations.index')->with('success', translate_with_gender('created', 'Organization'));
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
        $organization->update($request->validated());

        return Redirect::back()->with('success', translate_with_gender('updated', 'Organization'));
    }

    #[Authorize('delete', 'organization')]
    public function destroy(Organization $organization): RedirectResponse
    {
        $organization->delete();

        return Redirect::back()->with('success', translate_with_gender('deleted', 'Organization'));
    }

    #[Authorize('restore', 'organization')]
    public function restore(Organization $organization): RedirectResponse
    {
        $organization->restore();

        return Redirect::back()->with('success', translate_with_gender('restored', 'Organization'));
    }
}
