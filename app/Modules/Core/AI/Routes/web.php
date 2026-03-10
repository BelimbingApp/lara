<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use App\Modules\Core\AI\Livewire\Playground;
use App\Modules\Core\AI\Livewire\Providers;
use App\Modules\Core\AI\Livewire\Setup\Lara;
use App\Modules\Core\AI\Livewire\Tools;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Lara setup
    Route::get('admin/setup/lara', Lara::class)
        ->name('admin.setup.lara');

    Route::get('admin/ai/playground', Playground::class)
        ->name('admin.ai.playground');
    Route::get('admin/ai/providers', Providers::class)
        ->name('admin.ai.providers');
    Route::get('admin/ai/tools/{toolName?}', Tools::class)
        ->name('admin.ai.tools');
});
