<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Http\Resources\ContactCollection;
use App\Http\Resources\ContactResource;
use App\Http\Resources\UserOrganizationCollection;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ContactsController extends Controller
{
    public function index()
    {
        return Inertia::render('Contacts/Index', [
            'filters' => Request::all(['search', 'trashed']),
            'contacts' => new ContactCollection(
                Auth::user()->account->contacts()
                    ->with('organization')
                    ->orderByName()
                    ->filter(Request::only(['search', 'trashed']))
                    ->paginate()
                    ->withQueryString()
            ),
        ]);
    }

    public function create()
    {
        return Inertia::render('Contacts/Create', [
            'organizations' => Auth::user()->account
                ->organizations()
                ->orderBy('name')
                ->get()
                ->map
                ->only('id', 'name'),
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        Auth::user()->account->contacts()->create($request->validated());

        return Redirect::route('contacts.index')->with('success', translate_with_gender('created', 'Contact'));
    }

    public function edit(Contact $contact): Response
    {
        return Inertia::render('Contacts/Edit', [
            'contact' => new ContactResource($contact),
            'organizations' => new UserOrganizationCollection(
                Auth::user()->account->organizations()
                    ->orderBy('name')
                    ->get()
            ),
        ]);
    }

    public function update(Contact $contact, ContactRequest $request): RedirectResponse
    {
        $contact->update($request->validated());

        return Redirect::back()->with('success', translate_with_gender('updated', 'Contact'));
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return Redirect::back()->with('success', translate_with_gender('deleted', 'Contact'));
    }

    public function restore(Contact $contact): RedirectResponse
    {
        $contact->restore();

        return Redirect::back()->with('success', translate_with_gender('restored', 'Contact'));
    }
}
