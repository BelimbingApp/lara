<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use App\Modules\Core\Address\Http\Controllers\PostcodeSearchController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth'])->group(function () {
    Route::get('admin/addresses/postcodes/search', PostcodeSearchController::class)
        ->name('admin.addresses.postcodes.search');
    Volt::route('admin/addresses', 'addresses.index')->name('admin.addresses.index');
    Volt::route('admin/addresses/create', 'addresses.create')->name('admin.addresses.create');
    Volt::route('admin/addresses/{address}', 'addresses.show')->name('admin.addresses.show');
});
