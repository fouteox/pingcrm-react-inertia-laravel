<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationsRequest;
use App\Http\Resources\OrganizationCollection;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

final class OrganizationsController extends Controller
{
    public function index()
    {
        return Inertia::render('Organizations/Index', [
            'filters' => Request::all(['search', 'trashed']),
            'organizations' => new OrganizationCollection(
                Auth::user()->account->organizations()
                    ->orderBy('name')
                    ->filter(Request::only(['search', 'trashed']))
                    ->paginate()
                    ->withQueryString()
            ),
        ]);
    }

    public function create()
    {
        return Inertia::render('Organizations/Create');
    }

    public function store(OrganizationsRequest $request): RedirectResponse
    {
        Auth::user()->account->organizations()->create($request->validated());

        return Redirect::route('organizations.index')->with('success', 'Organization created.');
    }

    public function edit(Organization $organization)
    {
        return Inertia::render('Organizations/Edit', [
            'organization' => new OrganizationResource($organization),
        ]);
    }

    public function update(Organization $organization, OrganizationsRequest $request): RedirectResponse
    {
        $organization->update($request->validated());

        return Redirect::back()->with('success', 'Organization updated.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $organization->delete();

        return Redirect::back()->with('success', 'Organization deleted.');
    }

    public function restore(Organization $organization): RedirectResponse
    {
        $organization->restore();

        return Redirect::back()->with('success', 'Organization restored.');
    }
}
