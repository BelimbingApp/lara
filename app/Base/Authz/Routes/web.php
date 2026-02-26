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

    // Authz administration
    Volt::route('admin/authz/capabilities', 'admin.authz.capabilities.index')
        ->middleware('authz:admin.capability.list')
        ->name('admin.authz.capabilities.index');
    Volt::route('admin/authz/principal-roles', 'admin.authz.principal-roles.index')
        ->middleware('authz:admin.principal_role.list')
        ->name('admin.authz.principal-roles.index');
    Volt::route('admin/authz/principal-capabilities', 'admin.authz.principal-capabilities.index')
        ->middleware('authz:admin.principal_capability.list')
        ->name('admin.authz.principal-capabilities.index');
    Volt::route('admin/authz/decision-logs', 'admin.authz.decision-logs.index')
        ->middleware('authz:admin.decision_log.list')
        ->name('admin.authz.decision-logs.index');
});
