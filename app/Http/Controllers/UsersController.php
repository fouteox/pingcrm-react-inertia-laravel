<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;

final class UsersController extends Controller
{
    public function index()
    {
        return Inertia::render('Users/Index', [
            'filters' => Request::all(['search', 'role', 'trashed']),
            'users' => new UserCollection(
                Auth::user()->account->users()
                    ->orderByName()
                    ->filter(Request::only(['search', 'role', 'trashed']))
                    ->paginate()
                    ->withQueryString()
            ),
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create');
    }

    public function store(UserRequest $request): RedirectResponse
    {
        Auth::user()->account->users()->create($request->validated());

        return Redirect::route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return Inertia::render('Users/Edit', [
            'user' => new UserResource($user),
        ]);
    }

    public function update(User $user, UserRequest $request): RedirectResponse
    {
        if (App::environment('production') && $user->isDemoUser()) {
            return Redirect::back();
        }

        $data = $request->validated();

        $user->update($data);

        return Redirect::back()->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (App::environment('production') && $user->isDemoUser()) {
            return Redirect::back()->with('error', 'La suppression de l\'utilisateur de démonstration n\'est pas autorisée.');
        }

        $user->delete();

        return Redirect::back()->with('success', 'Utilisateur supprimé.');
    }

    public function restore(User $user): RedirectResponse
    {
        $user->restore();

        return Redirect::back()->with('success', 'User restored.');
    }
}
