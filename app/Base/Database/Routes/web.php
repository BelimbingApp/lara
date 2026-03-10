<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use App\Base\Database\Livewire\Migrations\Index as MigrationsIndex;
use App\Base\Database\Livewire\Seeders\Index as SeedersIndex;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('admin/system/migrations', MigrationsIndex::class)
        ->name('admin.system.migrations.index');
    Route::get('admin/system/seeders', SeedersIndex::class)
        ->name('admin.system.seeders.index');
});
