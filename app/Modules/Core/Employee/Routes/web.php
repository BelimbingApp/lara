<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth'])->group(function () {
    Volt::route('admin/employees', 'employees.index')->name('admin.employees.index');
    Volt::route('admin/employees/create', 'employees.create')->name('admin.employees.create');
    Volt::route('admin/employees/{employee}', 'employees.show')->name('admin.employees.show');
});
