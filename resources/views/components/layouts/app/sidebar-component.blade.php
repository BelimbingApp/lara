<?php
// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2025 Ng Kiat Siong
?>

<aside class="w-64 min-h-full bg-base-200 dark:bg-zinc-800 border-r border-base-300 dark:border-base-700 flex flex-col sidebar-container">
    <!-- Logo/Branding -->
    <div class="p-4 border-b border-base-300 dark:border-base-700">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3" wire:navigate>
            <x-app-logo />
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto p-4 flex flex-col">
        <!-- Unified Navigation Menu -->
        <ul class="menu menu-vertical w-full gap-1">
            <!-- Primary Navigation -->
            <li>
                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    class="{{ request()->routeIs('dashboard') ? 'active' : '' }} nav-link"
                >
                    <x-icon name="heroicon-o-home" class="w-4 h-4 shrink-0" />
                    <span class="text-xs whitespace-nowrap">{{ __('Dashboard') }}</span>
                </a>
            </li>

            <!-- Secondary Navigation -->
            <li>
                <a
                    href="https://github.com/BelimbingApp/lara"
                    target="_blank"
                    class="nav-link"
                >
                    <x-icon name="heroicon-o-folder" class="w-4 h-4 shrink-0" />
                    <span class="text-xs whitespace-nowrap">{{ __('Repository') }}</span>
                </a>
            </li>
            <li>
                <a
                    href="https://laravel.com/docs/starter-kits#livewire"
                    target="_blank"
                    class="nav-link"
                >
                    <x-icon name="heroicon-o-book-open" class="w-4 h-4 shrink-0" />
                    <span class="text-xs whitespace-nowrap">{{ __('Documentation') }}</span>
                </a>
            </li>

            <!-- Spacer to push Settings/Logout to bottom -->
            <li class="flex-1"></li>

            <!-- Settings and Logout -->
            <li>
                <a href="{{ route('profile.edit') }}" wire:navigate class="nav-link">
                    <x-icon name="heroicon-o-cog-6-tooth" class="w-4 h-4 shrink-0" />
                    <span class="text-xs whitespace-nowrap">{{ __('Settings') }}</span>
                </a>
            </li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left nav-link" data-test="logout-button">
                        <x-icon name="heroicon-o-arrow-right-on-rectangle" class="w-4 h-4 shrink-0" />
                        <span class="text-xs whitespace-nowrap">{{ __('Log Out') }}</span>
                    </button>
                </form>
            </li>
        </ul>
    </nav>

    <!-- User Profile Section (Sticky Bottom) -->
    <div class="p-4 border-t border-base-300 dark:border-base-700 bg-base-200 dark:bg-zinc-800">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-neutral-200 dark:bg-neutral-700 flex items-center justify-center shrink-0">
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
    </div>
</aside>
