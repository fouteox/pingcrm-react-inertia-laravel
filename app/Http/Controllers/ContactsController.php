<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\ContactsFilters;
use App\Http\Requests\ContactRequest;
use App\Http\Resources\ContactCollection;
use App\Http\Resources\ContactResource;
use App\Http\Resources\UserOrganizationCollection;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

final class ContactsController extends Controller
{
    #[Authorize('viewAny', Contact::class)]
    public function index(Request $request): Response
    {
        $filters = ContactsFilters::fromRequest($request);

        return Inertia::render('contacts/index', [
            'filters' => $filters->toArray(),
            'contacts' => new ContactCollection(
                Auth::user()->account->contacts()
                    ->with('organization')
                    ->orderByName()
                    ->filter($filters->toArray(), Auth::user()->account_id)
                    ->paginate()
                    ->withQueryString()
            ),
        ]);
    }

    #[Authorize('create', Contact::class)]
    public function create(): Response
    {
        return Inertia::render('contacts/create', [
            'organizations' => Auth::user()->account
                ->organizations()
                ->orderBy('name')
                ->get()
                ->map
                ->only('id', 'name'),
        ]);
    }

    #[Authorize('create', Contact::class)]
    public function store(ContactRequest $request): RedirectResponse
    {
        Auth::user()->account->contacts()->create($request->validated());

        return Redirect::route('contacts.index')->with('success', translate_with_gender('created', 'Contact'));
    }

    #[Authorize('update', 'contact')]
    public function edit(Contact $contact): Response
    {
        return Inertia::render('contacts/edit', [
            'contact' => new ContactResource($contact),
            'organizations' => new UserOrganizationCollection(
                Auth::user()->account->organizations()
                    ->orderBy('name')
                    ->get()
            ),
        ]);
    }

    #[Authorize('update', 'contact')]
    public function update(Contact $contact, ContactRequest $request): RedirectResponse
    {
        $contact->update($request->validated());

        return Redirect::back()->with('success', translate_with_gender('updated', 'Contact'));
    }

    #[Authorize('delete', 'contact')]
    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return Redirect::back()->with('success', translate_with_gender('deleted', 'Contact'));
    }

    #[Authorize('restore', 'contact')]
    public function restore(Contact $contact): RedirectResponse
    {
        $contact->restore();

        return Redirect::back()->with('success', translate_with_gender('restored', 'Contact'));
    }
}
