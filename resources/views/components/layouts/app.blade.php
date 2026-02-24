<?php
// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>
?>

@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    @if($title)
        <title>{{ $title }} - {{ config('app.name', 'Belimbing') }}</title>
    @endif
</head>
<body
    x-data="{ sidebarOpen: false, sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === '1' }"
    @toggle-sidebar.window="
        if (window.innerWidth >= 1024) {
            sidebarCollapsed = !sidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', sidebarCollapsed ? '1' : '0');
        } else {
            sidebarOpen = !sidebarOpen;
        }
    "
    @keydown.escape.window="sidebarOpen = false"
    class="h-screen overflow-hidden bg-surface-page flex flex-col"
>
    {{-- Impersonation Banner --}}
    <x-layouts.impersonation-banner />

    {{-- Top Bar --}}
    <x-layouts.top-bar />

    {{-- Main Layout: Sidebar + Content --}}
    <div class="relative flex flex-1 overflow-hidden">
        {{-- Desktop Sidebar --}}
        <div class="hidden lg:block">
            <x-menu.sidebar :menuTree="$menuTree" :collapsed="true" x-show="sidebarCollapsed" />
            <x-menu.sidebar :menuTree="$menuTree" :collapsed="false" x-show="!sidebarCollapsed" />
        </div>

        {{-- Mobile Sidebar Backdrop --}}
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            class="lg:hidden fixed inset-0 z-30 bg-black/35"
            style="display: none;"
            aria-hidden="true"
        ></div>

        {{-- Mobile Sidebar Drawer --}}
        <div
            x-show="sidebarOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="lg:hidden fixed top-11 bottom-6 left-0 z-40"
            style="display: none;"
        >
            <x-menu.sidebar :menuTree="$menuTree" :collapsed="false" />
        </div>

        <main class="flex-1 overflow-y-auto bg-surface-page p-3 sm:p-4">
            {{ $slot }}
        </main>
    </div>

    {{-- Status Bar --}}
    <x-layouts.status-bar />
</body>
</html>
