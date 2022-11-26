<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ImagesController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrganizationsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Auth
Route::controller(AuthenticatedSessionController::class)->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', 'create')->name('login');    
        Route::post('login', 'store')->name('login.store');
    });
    Route::delete('logout', 'destroy')->name('logout');
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
    
    // Reports
    Route::get('reports', [ReportsController::class, 'index'])->name('reports');
    
});

// Images
Route::get('/img/{path}', [ImagesController::class, 'show'])
    ->where('path', '.*')
    ->name('image');
