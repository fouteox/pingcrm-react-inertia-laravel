<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\UsersFilters;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\SearchIndex;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

final class UsersController extends Controller
{
    public function __construct(private readonly SearchIndex $search) {}

    #[Authorize('viewAny', User::class)]
    public function index(Request $request, #[CurrentUser] User $authenticatedUser): Response
    {
        $filters = UsersFilters::fromRequest($request);

        return Inertia::render('users/index', [
            'filters' => $filters->toArray(),
            'users' => new UserCollection(
                User::paginateFiltered($filters->toArray(), $authenticatedUser->account_id)
                    ->withQueryString()
            ),
        ]);
    }

    #[Authorize('create', User::class)]
    public function create(): Response
    {
        return Inertia::render('users/create');
    }

    #[Authorize('create', User::class)]
    public function store(UserRequest $request, #[CurrentUser] User $authenticatedUser): RedirectResponse
    {
        $this->search->mutate($authenticatedUser->account_id,
            fn () => $authenticatedUser->account()->firstOrFail()->users()->create($request->validated())
        );

        Inertia::flash('success', translate_with_gender('created', 'User'));

        return Redirect::route('users.index');
    }

    #[Authorize('update', 'user')]
    public function edit(User $user): Response
    {
        return Inertia::render('users/edit', [
            'user' => new UserResource($user),
        ]);
    }

    #[Authorize('update', 'user')]
    public function update(User $user, UserRequest $request): RedirectResponse
    {
        if ($user->isProtectedDemoUser()) {
            return Redirect::back();
        }

        $this->search->mutate($user->account_id, fn () => tap($user)->update($request->validated()));

        Inertia::flash('success', translate_with_gender('updated', 'User'));

        return Redirect::back();
    }

    #[Authorize('delete', 'user')]
    public function destroy(User $user): RedirectResponse
    {
        if ($user->isProtectedDemoUser()) {
            Inertia::flash('error', __('Deleting the demo user is not allowed.'));

            return Redirect::back();
        }

        $this->search->mutate($user->account_id, fn () => tap($user)->delete());

        Inertia::flash('success', translate_with_gender('deleted', 'User'));

        return Redirect::back();
    }

    #[Authorize('restore', 'user')]
    public function restore(User $user): RedirectResponse
    {
        $this->search->mutate($user->account_id, fn () => tap($user)->restore());

        Inertia::flash('success', translate_with_gender('restored', 'User'));

        return Redirect::back();
    }
}
