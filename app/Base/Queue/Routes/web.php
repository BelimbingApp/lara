<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth'])->group(function () {
    Volt::route('admin/system/jobs', 'admin.system.jobs.index')
        ->name('admin.system.jobs.index');
    Volt::route('admin/system/failed-jobs', 'admin.system.failed-jobs.index')
        ->name('admin.system.failed-jobs.index');
    Volt::route('admin/system/job-batches', 'admin.system.job-batches.index')
        ->name('admin.system.job-batches.index');
});
