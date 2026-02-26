<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('auth')->group(function () {
    Volt::route('admin/system/migrations', 'admin.system.migrations.index')
        ->name('admin.system.migrations.index');
    Volt::route('admin/system/seeders', 'admin.system.seeders.index')
        ->name('admin.system.seeders.index');
});
