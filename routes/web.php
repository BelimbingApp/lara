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

    // Admin: Geonames
    Volt::route('admin/geonames/countries', 'admin.geonames.countries.index')->name('admin.geonames.countries.index');
    Volt::route('admin/geonames/admin1', 'admin.geonames.admin1.index')->name('admin.geonames.admin1.index');
    Volt::route('admin/geonames/postcodes', 'admin.geonames.postcodes.index')->name('admin.geonames.postcodes.index');
});

require __DIR__.'/auth.php';
