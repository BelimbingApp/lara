<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <!-- Header -->
        <div class="navbar bg-base-100 border-b border-base-300 dark:border-base-700">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 me-5" wire:navigate>
                <x-app-logo />
            </a>

            <!-- Desktop Navigation -->
            <div class="hidden lg:flex">
                <ul class="menu menu-horizontal px-1">
                    <li>
                        <a href="{{ route('dashboard') }}" wire:navigate class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <x-icon name="heroicon-o-squares-2x2" class="w-5 h-5" />
                            {{ __('Dashboard') }}
                        </a>
                    </li>
                </ul>
            </div>

            <div class="flex-1"></div>

            <!-- Action Buttons -->
            <div class="hidden lg:flex gap-2">
                <a href="#" class="btn btn-ghost btn-sm" title="{{ __('Search') }}">
                    <x-icon name="heroicon-o-magnifying-glass" class="w-5 h-5" />
                </a>
                <a href="https://github.com/BelimbingApp/lara" target="_blank" class="btn btn-ghost btn-sm" title="{{ __('Repository') }}">
                    <x-icon name="heroicon-o-folder" class="w-5 h-5" />
                </a>
                <a href="https://laravel.com/docs/starter-kits#livewire" target="_blank" class="btn btn-ghost btn-sm" title="{{ __('Documentation') }}">
                    <x-icon name="heroicon-o-book-open" class="w-5 h-5" />
                </a>
            </div>

            <!-- User Menu -->
            <x-ui.user-menu :user="auth()->user()" />
        </div>

        <!-- Mobile Sidebar -->
        <div class="drawer lg:hidden">
            <input id="mobile-drawer" type="checkbox" class="drawer-toggle" />
            <div class="drawer-content">
                {{ $slot }}
            </div>
            <div class="drawer-side">
                <label for="mobile-drawer" class="drawer-overlay"></label>
                <aside class="w-64 min-h-full bg-base-200 border-r border-base-300 dark:border-base-700 p-4">
                    <label for="mobile-drawer" class="btn btn-square btn-ghost mb-4">
                        <x-icon name="heroicon-o-x-mark" class="w-6 h-6" />
                    </label>
                    <ul class="menu menu-vertical w-full">
                        <li>
                            <a href="{{ route('dashboard') }}" wire:navigate class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <x-icon name="heroicon-o-squares-2x2" class="w-5 h-5" />
                                {{ __('Dashboard') }}
                            </a>
                        </li>
                    </ul>
                    <div class="divider"></div>
                    <ul class="menu menu-vertical w-full">
                        <li>
                            <a href="https://github.com/BelimbingApp/lara" target="_blank">
                                <x-icon name="heroicon-o-folder" class="w-5 h-5" />
                                {{ __('Repository') }}
                            </a>
                        </li>
                        <li>
                            <a href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                                <x-icon name="heroicon-o-book-open" class="w-5 h-5" />
                                {{ __('Documentation') }}
                            </a>
                        </li>
                    </ul>
                </aside>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1 p-6">
            {{ $slot }}
        </main>
    </body>
</html>
