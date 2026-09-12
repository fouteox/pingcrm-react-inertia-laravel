<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\ResourceFilters;
use App\Http\Requests\ContactRequest;
use App\Http\Resources\ContactCollection;
use App\Http\Resources\ContactResource;
use App\Http\Resources\UserOrganizationCollection;
use App\Models\Contact;
use App\Models\User;
use App\Services\SearchIndex;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

final class ContactsController extends Controller
{
    public function __construct(private readonly SearchIndex $search) {}

    #[Authorize('viewAny', Contact::class)]
    public function index(Request $request, #[CurrentUser] User $authenticatedUser): Response
    {
        $filters = ResourceFilters::fromRequest($request);

        return Inertia::render('contacts/index', [
            'filters' => $filters->toArray(),
            'contacts' => new ContactCollection(
                Contact::paginateFiltered($filters->toArray(), $authenticatedUser->account_id)
                    ->withQueryString()
            ),
        ]);
    }

    #[Authorize('create', Contact::class)]
    public function create(#[CurrentUser] User $authenticatedUser): Response
    {
        return Inertia::render('contacts/create', [
            'organizations' => $authenticatedUser->account()->firstOrFail()
                ->organizations()
                ->orderBy('name')
                ->get()
                ->map
                ->only('id', 'name'),
        ]);
    }

    #[Authorize('create', Contact::class)]
    public function store(ContactRequest $request, #[CurrentUser] User $authenticatedUser): RedirectResponse
    {
        $this->search->mutate($authenticatedUser->account_id,
            fn () => $authenticatedUser->account()->firstOrFail()->contacts()->create($request->validated())
        );

        Inertia::flash('success', translate_with_gender('created', 'Contact'));

        return Redirect::route('contacts.index');
    }

    #[Authorize('update', 'contact')]
    public function edit(Contact $contact, #[CurrentUser] User $authenticatedUser): Response
    {
        return Inertia::render('contacts/edit', [
            'contact' => new ContactResource($contact),
            'organizations' => new UserOrganizationCollection(
                $authenticatedUser->account()->firstOrFail()->organizations()
                    ->orderBy('name')
                    ->get()
            ),
        ]);
    }

    #[Authorize('update', 'contact')]
    public function update(Contact $contact, ContactRequest $request): RedirectResponse
    {
        $this->search->mutate($contact->account_id, fn () => tap($contact)->update($request->validated()));

        Inertia::flash('success', translate_with_gender('updated', 'Contact'));

        return Redirect::back();
    }

    #[Authorize('delete', 'contact')]
    public function destroy(Contact $contact): RedirectResponse
    {
        $this->search->mutate($contact->account_id, fn () => tap($contact)->delete());

        Inertia::flash('success', translate_with_gender('deleted', 'Contact'));

        return Redirect::back();
    }

    #[Authorize('restore', 'contact')]
    public function restore(Contact $contact): RedirectResponse
    {
        $this->search->mutate($contact->account_id, fn () => tap($contact)->restore());

        Inertia::flash('success', translate_with_gender('restored', 'Contact'));

        return Redirect::back();
    }
}
