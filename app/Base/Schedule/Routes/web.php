<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use App\Base\Schedule\Livewire\ScheduledTasks\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('admin/system/scheduled-tasks', Index::class)
        ->name('admin.system.scheduled-tasks.index');
});
