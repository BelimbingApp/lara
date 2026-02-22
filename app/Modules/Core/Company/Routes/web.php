<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth'])->group(function () {
    // Setup
    Volt::route('admin/setup/licensee', 'admin.setup.licensee')->name('admin.setup.licensee');

    Volt::route('admin/companies', 'companies.index')->name('admin.companies.index');
    Volt::route('admin/companies/create', 'companies.create')->name('admin.companies.create');
    Volt::route('admin/companies/legal-entity-types', 'companies.legal-entity-types')->name('admin.companies.legal-entity-types');
    Volt::route('admin/companies/department-types', 'companies.department-types')->name('admin.companies.department-types');
    Volt::route('admin/companies/{company}', 'companies.show')->name('admin.companies.show');
    Volt::route('admin/companies/{company}/relationships', 'companies.relationships')->name('admin.companies.relationships');
    Volt::route('admin/companies/{company}/departments', 'companies.departments')->name('admin.companies.departments');
});
