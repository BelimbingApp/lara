<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth'])->group(function () {
    Volt::route('admin/addresses', 'addresses.index')->name('admin.addresses.index');
    Volt::route('admin/addresses/create', 'addresses.create')->name('admin.addresses.create');
    Volt::route('admin/addresses/{address}', 'addresses.show')->name('admin.addresses.show');
});
