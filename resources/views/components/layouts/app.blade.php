<?php
// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2025 Ng Kiat Siong
?>

@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
    @if($title)
        <title>{{ $title }} - {{ config('app.name', 'Belimbing') }}</title>
    @endif
</head>
<body class="min-h-screen bg-base-100 dark:bg-zinc-900">
    <!-- Mobile Drawer -->
    <div class="drawer lg:drawer-open">
        <input id="sidebar-drawer" type="checkbox" class="drawer-toggle" />

        <!-- Mobile Header and Content -->
        <div class="drawer-content flex flex-col lg:hidden">
            <div class="navbar bg-base-100 border-b border-base-300 dark:border-base-700 px-4 min-h-0">
                <label for="sidebar-drawer" class="btn btn-square btn-ghost drawer-button">
                    <x-icon name="heroicon-o-bars-3" class="w-6 h-6 hamburger-icon" />
                </label>
            </div>
            <div class="flex-1 overflow-y-auto bg-base-100 dark:bg-zinc-900 p-6">
                {{ $slot }}
            </div>
        </div>

        <!-- Desktop Layout: Sidebar + Content -->
        <div class="hidden lg:flex lg:h-screen lg:overflow-hidden">
            <x-layouts.app.sidebar-component />
            <x-layouts.app.content>
                {{ $slot }}
            </x-layouts.app.content>
        </div>

        <!-- Mobile Drawer Side -->
        <div class="drawer-side lg:hidden">
            <label for="sidebar-drawer" class="drawer-overlay"></label>
            <x-layouts.app.sidebar-component />
        </div>
    </div>
</body>
</html>
