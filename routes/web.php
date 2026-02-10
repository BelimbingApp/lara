<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    // User Management
    Volt::route('users', 'users.index')->name('users.index');
    Volt::route('users/create', 'users.create')->name('users.create');
    Volt::route('users/{user}/edit', 'users.edit')->name('users.edit');

    // Admin: Geonames (placeholder routes for menu testing)
    Route::get('admin/geonames/countries', fn() => view('placeholder', ['title' => 'Geonames Countries']))->name('admin.geonames.countries.index');
    Route::get('admin/geonames/postcodes', fn() => view('placeholder', ['title' => 'Geonames Postcodes']))->name('admin.geonames.postcodes.index');
});

require __DIR__.'/auth.php';
