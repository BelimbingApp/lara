<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth'])->group(function () {
    Volt::route('admin/employees', 'employees.index')->name('admin.employees.index');
    Volt::route('admin/employees/create', 'employees.create')->name('admin.employees.create');
    Volt::route('admin/employees/{employee}', 'employees.show')->name('admin.employees.show');

    Volt::route('admin/employee-types', 'admin.employee-types.index')
        ->middleware('authz:core.employee_type.list')
        ->name('admin.employee-types.index');
    Volt::route('admin/employee-types/create', 'admin.employee-types.create')
        ->middleware('authz:core.employee_type.create')
        ->name('admin.employee-types.create');
    Volt::route('admin/employee-types/{employeeType}/edit', 'admin.employee-types.edit')
        ->middleware('authz:core.employee_type.update')
        ->name('admin.employee-types.edit');
});
