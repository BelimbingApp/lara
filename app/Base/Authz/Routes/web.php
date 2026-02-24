<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use App\Base\Authz\Services\ImpersonationManager;
use App\Modules\Core\User\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('auth')->group(function () {
    Route::post('admin/impersonate/leave', function (ImpersonationManager $manager) {
        $manager->stop();

        return redirect()->route('dashboard');
    })->name('admin.impersonate.stop');

    Route::post('admin/impersonate/{user}', function (User $user, ImpersonationManager $manager) {
        $manager->start(auth()->user(), $user);

        return redirect()->route('dashboard');
    })
        ->middleware('authz:admin.user.impersonate')
        ->name('admin.impersonate.start');

    // Role management
    Volt::route('admin/roles', 'admin.roles.index')
        ->middleware('authz:admin.role.list')
        ->name('admin.roles.index');
    Volt::route('admin/roles/create', 'admin.roles.create')
        ->middleware('authz:admin.role.create')
        ->name('admin.roles.create');
    Volt::route('admin/roles/{role}', 'admin.roles.show')
        ->middleware('authz:admin.role.view')
        ->name('admin.roles.show');
});
