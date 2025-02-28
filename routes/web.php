<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FetchTranslationsController;
use App\Http\Controllers\OrganizationsController;
use App\Http\Controllers\ReverbExampleController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/locales/{locale}/translation.json', FetchTranslationsController::class)->name('i18next.fetch');

// Auth
Route::controller(AuthenticatedSessionController::class)->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', 'create')->name('login');
        Route::post('login', 'store')->name('login.store');
    });
    Route::post('logout', 'destroy')->name('logout');
});

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::resource('users', UsersController::class)->except(['show']);
    Route::put('users/{user}/restore', [UsersController::class, 'restore'])->name('users.restore');

    // Organizations
    Route::resource('organizations', OrganizationsController::class)->except(['show']);
    Route::put('organizations/{organization}/restore', [OrganizationsController::class, 'restore'])->name('organizations.restore');

    // Contacts
    Route::resource('contacts', ContactsController::class)->except(['show']);
    Route::put('contacts/{contact}/restore', [ContactsController::class, 'restore'])->name('contacts.restore');

    // Reverb example
    Route::get('/reverb', [ReverbExampleController::class, 'index'])->name('reverb.index');
    Route::post('/reverb', [ReverbExampleController::class, 'store'])->name('reverb.store')->middleware('throttle:5,1');

    // Fadogen
    Route::get('/fadogen', function () {
        return Inertia::render('fadogen');
    })->name('fadogen');
});
