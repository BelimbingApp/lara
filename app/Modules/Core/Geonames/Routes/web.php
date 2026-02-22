<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth'])->group(function () {
    Volt::route('admin/geonames/countries', 'admin.geonames.countries.index')->name('admin.geonames.countries.index');
    Volt::route('admin/geonames/admin1', 'admin.geonames.admin1.index')->name('admin.geonames.admin1.index');
    Volt::route('admin/geonames/postcodes', 'admin.geonames.postcodes.index')->name('admin.geonames.postcodes.index');
});
