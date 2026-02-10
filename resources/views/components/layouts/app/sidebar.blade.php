<?php
// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>
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
    <!-- Desktop Layout (lg and up) -->
        <div class="hidden lg:flex lg:h-screen lg:overflow-hidden">
        <!-- Desktop Sidebar -->
            <aside class="w-64 min-h-full bg-base-200 dark:bg-zinc-800 border-r border-base-300 dark:border-base-700 flex flex-col sidebar-container">
                <!-- Logo/Branding -->
                <div class="p-4 border-b border-base-300 dark:border-base-700">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3" wire:navigate>
                        <x-app-logo />
                    </a>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                    <!-- Primary Navigation -->
                    <ul class="menu menu-vertical w-full gap-1">
                        <li>
                            <a
                                href="{{ route('dashboard') }}"
                                wire:navigate
                                class="{{ request()->routeIs('dashboard') ? 'active' : '' }} nav-link"
                            >
                                <x-icon name="heroicon-o-home" class="w-5 h-5" />
                                <span>{{ __('Dashboard') }}</span>
                            </a>
                        </li>
                    <li>
                        <a
                            href="{{ route('users.index') }}"
                            wire:navigate
                            class="{{ request()->routeIs('users.*') ? 'active' : '' }} nav-link"
                        >
                            <x-icon name="heroicon-o-users" class="w-5 h-5" />
                            <span>{{ __('User Management') }}</span>
                        </a>
                    </li>
                    </ul>

                    <!-- Divider -->
                    <div class="divider my-2"></div>

                    <!-- Secondary Navigation -->
                    <ul class="menu menu-vertical w-full gap-1">
                        <li>
                            <a
                            href="https://github.com/BelimbingApp/lara"
                                target="_blank"
                                class="nav-link"
                            >
                                <x-icon name="heroicon-o-folder" class="w-5 h-5" />
                                <span>{{ __('Repository') }}</span>
                            </a>
                        </li>
                        <li>
                            <a
                                href="https://laravel.com/docs/starter-kits#livewire"
                                target="_blank"
                                class="nav-link"
                            >
                                <x-icon name="heroicon-o-book-open" class="w-5 h-5" />
                                <span>{{ __('Documentation') }}</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- User Profile Section (Sticky Bottom) -->
                <div class="p-4 border-t border-base-300 dark:border-base-700 bg-base-200 dark:bg-zinc-800">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-neutral-200 dark:bg-neutral-700 flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-semibold text-neutral-800 dark:text-white">
                                {{ auth()->user()->initials() }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-neutral-900 dark:text-white truncate">
                                {{ auth()->user()->name }}
                            </div>
                            <div class="text-xs text-neutral-600 dark:text-neutral-400 truncate">
                                {{ auth()->user()->email }}
                            </div>
                        </div>
                    </div>

                    <div class="divider my-2"></div>

                    <!-- Settings and Logout -->
                    <ul class="menu menu-vertical w-full gap-1">
                        <li>
                            <a href="{{ route('profile.edit') }}" wire:navigate class="nav-link">
                                <x-icon name="heroicon-o-cog-6-tooth" class="w-5 h-5" />
                                <span>{{ __('Settings') }}</span>
                            </a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left nav-link" data-test="logout-button">
                                    <x-icon name="heroicon-o-arrow-right-on-rectangle" class="w-5 h-5" />
                                    <span>{{ __('Log Out') }}</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </aside>

        <!-- Desktop Main Content Area -->
            <div class="flex-1 overflow-y-auto">
                {{ $slot }}
            </div>
        </div>

    <!-- Mobile Layout (below lg) - Custom Alpine.js drawer -->
    <div class="lg:hidden min-h-screen flex flex-col" x-data="{ sidebarOpen: false }">
        <!-- Mobile Header -->
        <div class="navbar bg-base-100 border-b border-base-300 dark:border-base-700 sticky top-0 z-30">
            <button
                type="button"
                @click="sidebarOpen = true"
                class="btn btn-square btn-ghost"
            >
                <x-icon name="heroicon-o-bars-3" class="w-6 h-6" />
            </button>
            <div class="flex-1"></div>
            <x-ui.user-menu :user="auth()->user()" />
        </div>

        <!-- Mobile Page Content -->
        <div class="flex-1 overflow-y-auto">
            {{ $slot }}
        </div>

        <!-- Mobile Sidebar Overlay -->
        <div
            x-show="sidebarOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
            class="fixed inset-0 bg-black/50 z-40"
            style="display: none;"
        ></div>

        <!-- Mobile Sidebar -->
        <aside
            x-show="sidebarOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 w-64 bg-base-200 dark:bg-zinc-800 border-r border-base-300 dark:border-base-700 flex flex-col z-50"
            style="display: none;"
            @click.stop
        >
                <!-- Logo -->
                <div class="p-4 border-b border-base-300 dark:border-base-700">
                <button
                    type="button"
                    @click="sidebarOpen = false"
                    class="btn btn-square btn-ghost mb-4"
                >
                        <x-icon name="heroicon-o-x-mark" class="w-6 h-6" />
                </button>
                <a
                    href="{{ route('dashboard') }}"
                    class="flex items-center gap-3"
                    @click="sidebarOpen = false"
                >
                        <x-app-logo />
                    </a>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                    <ul class="menu menu-vertical w-full gap-1">
                        <li>
                            <a
                                href="{{ route('dashboard') }}"
                            @click="sidebarOpen = false"
                                class="{{ request()->routeIs('dashboard') ? 'active' : '' }} nav-link"
                            >
                                <x-icon name="heroicon-o-home" class="w-5 h-5" />
                                <span>{{ __('Dashboard') }}</span>
                            </a>
                        </li>
                    <li>
                        <a
                            href="{{ route('users.index') }}"
                            @click="sidebarOpen = false"
                            class="{{ request()->routeIs('users.*') ? 'active' : '' }} nav-link"
                        >
                            <x-icon name="heroicon-o-users" class="w-5 h-5" />
                            <span>{{ __('User Management') }}</span>
                        </a>
                    </li>
                    </ul>

                    <div class="divider my-2"></div>

                    <ul class="menu menu-vertical w-full gap-1">
                        <li>
                            <a
                            href="https://github.com/BelimbingApp/lara"
                                target="_blank"
                                class="nav-link"
                            >
                                <x-icon name="heroicon-o-folder" class="w-5 h-5" />
                                <span>{{ __('Repository') }}</span>
                            </a>
                        </li>
                        <li>
                            <a
                                href="https://laravel.com/docs/starter-kits#livewire"
                                target="_blank"
                                class="nav-link"
                            >
                                <x-icon name="heroicon-o-book-open" class="w-5 h-5" />
                                <span>{{ __('Documentation') }}</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- User Profile -->
                <div class="p-4 border-t border-base-300 dark:border-base-700">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-neutral-200 dark:bg-neutral-700 flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-semibold text-neutral-800 dark:text-white">
                                {{ auth()->user()->initials() }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-neutral-900 dark:text-white truncate">
                                {{ auth()->user()->name }}
                            </div>
                            <div class="text-xs text-neutral-600 dark:text-neutral-400 truncate">
                                {{ auth()->user()->email }}
                            </div>
                        </div>
                    </div>

                    <div class="divider my-2"></div>

                    <ul class="menu menu-vertical w-full gap-1">
                        <li>
                        <a
                            href="{{ route('profile.edit') }}"
                            @click="sidebarOpen = false"
                            class="nav-link"
                        >
                                <x-icon name="heroicon-o-cog-6-tooth" class="w-5 h-5" />
                                <span>{{ __('Settings') }}</span>
                            </a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left nav-link" data-test="logout-button">
                                    <x-icon name="heroicon-o-arrow-right-on-rectangle" class="w-5 h-5" />
                                    <span>{{ __('Log Out') }}</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </aside>
    </div>
</body>
</html>
