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
<body class="h-screen overflow-hidden bg-surface-page flex flex-col">
    {{-- Impersonation Banner --}}
    <x-layouts.impersonation-banner />

    {{-- Top Bar --}}
    <x-layouts.top-bar />

    {{-- Main Layout: Sidebar + Content --}}
    <div class="flex flex-1 overflow-hidden">
        <x-menu.sidebar :menuTree="$menuTree" />
        
        <main class="flex-1 overflow-y-auto bg-surface-page p-4">
            {{ $slot }}
        </main>
    </div>

    {{-- Status Bar --}}
    <x-layouts.status-bar />
</body>
</html>
