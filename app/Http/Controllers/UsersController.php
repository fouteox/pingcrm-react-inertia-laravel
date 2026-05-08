<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\UsersFilters;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

final class UsersController extends Controller
{
    #[Authorize('viewAny', User::class)]
    public function index(Request $request): Response
    {
        $filters = UsersFilters::fromRequest($request);

        return Inertia::render('users/index', [
            'filters' => $filters->toArray(),
            'users' => new UserCollection(
                Auth::user()->account->users()
                    ->orderByName()
                    ->filter($filters->toArray(), Auth::user()->account_id)
                    ->paginate()
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
    public function store(UserRequest $request): RedirectResponse
    {
        Auth::user()->account->users()->create($request->validated());

        return Redirect::route('users.index')->with('success', translate_with_gender('created', 'User'));
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

        $user->update($request->validated());

        return Redirect::back()->with('success', translate_with_gender('updated', 'User'));
    }

    #[Authorize('delete', 'user')]
    public function destroy(User $user): RedirectResponse
    {
        if ($user->isProtectedDemoUser()) {
            return Redirect::back()->with('error', __('Deleting the demo user is not allowed.'));
        }

        $user->delete();

        return Redirect::back()->with('success', translate_with_gender('deleted', 'User'));
    }

    #[Authorize('restore', 'user')]
    public function restore(User $user): RedirectResponse
    {
        $user->restore();

        return Redirect::back()->with('success', translate_with_gender('restored', 'User'));
    }
}
