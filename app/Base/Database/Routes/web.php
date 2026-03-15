<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use App\Base\Database\Livewire\DbViews\Index as DbViewsIndex;
use App\Base\Database\Livewire\DbViews\Show as DbViewsShow;
use App\Base\Database\Livewire\Tables\Index as TablesIndex;
use App\Base\Database\Livewire\Tables\Show as TablesShow;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('admin/system/tables', TablesIndex::class)
        ->name('admin.system.tables.index');
    Route::get('admin/system/tables/{tableName}', TablesShow::class)
        ->name('admin.system.tables.show');

    Route::get('admin/system/db-views', DbViewsIndex::class)
        ->name('admin.system.db-views.index');
    Route::get('admin/system/db-views/{slug}', DbViewsShow::class)
        ->name('admin.system.db-views.show');
});
