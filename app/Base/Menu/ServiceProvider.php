<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Menu;

use App\Base\Menu\Contracts\MenuAccessChecker;
use App\Base\Menu\Services\DefaultMenuAccessChecker;
use App\Base\Menu\Services\MenuDiscoveryService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(MenuDiscoveryService::class);
        $this->app->singleton(MenuRegistry::class);
        $this->app->singleton(MenuBuilder::class);
        $this->app->bindIf(MenuAccessChecker::class, DefaultMenuAccessChecker::class, true);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerViewComposer();
    }

    /**
     * Register view composer to provide menu data to layouts.
     */
    protected function registerViewComposer(): void
    {
        View::composer(['components.layouts.app', 'layouts::app'], function ($view): void {
            // Skip if not authenticated (avoid redirect loop on login pages)
            if (! auth()->check()) {
                $view->with('menuTree', []);

                return;
            }
            $registry = $this->app->make(MenuRegistry::class);
            $discovery = $this->app->make(MenuDiscoveryService::class);
            $builder = $this->app->make(MenuBuilder::class);
            $menuAccessChecker = $this->app->make(MenuAccessChecker::class);

            // Environment-aware caching
            if ($this->app->environment('local')) {
                // Development: Always discover fresh (no cache)
                $registry->registerFromDiscovery($discovery->discover());
                $errors = $registry->validate();

                if (! empty($errors)) {
                    logger()->error('Menu validation errors', ['errors' => $errors]);
                }
            } else {
                // Production/Staging: Use cache
                if (! $registry->loadFromCache()) {
                    // Cache miss: discover and persist
                    $registry->registerFromDiscovery($discovery->discover());
                    $errors = $registry->validate();

                    if (! empty($errors)) {
                        logger()->error('Menu validation errors', ['errors' => $errors]);
                    }

                    $registry->persist();
                }
            }

            // Build tree with current route for active marking
            $currentRoute = request()->route()?->getName();
            $user = auth()->user();

            $filteredItems = $registry->getAll()->filter(function (MenuItem $item) use ($menuAccessChecker, $user): bool {
                return $menuAccessChecker->canView($item, $user);
            });

            $menuTree = $builder->build($filteredItems, $currentRoute);

            // Flat map of all navigable items for pinned section lookup.
            // Keyed by item ID, value is a simple array with label, icon, route/url.
            $menuItemsFlat = $filteredItems
                ->filter(fn (MenuItem $item) => $item->hasRoute())
                ->mapWithKeys(fn (MenuItem $item) => [
                    $item->id => [
                        'label' => $item->label,
                        'icon' => $item->icon ?? 'heroicon-o-squares-2x2',
                        'href' => $item->route ? route($item->route) : $item->url,
                        'route' => $item->route,
                    ],
                ])
                ->all();

            $view->with('menuTree', $menuTree);
            $view->with('menuItemsFlat', $menuItemsFlat);

            // Load user's pinned menu item IDs (ordered by sort_order).
            // Uses duck-typing: calls getPinnedMenuItemIds() on the User model
            // without importing it (Base cannot depend on Modules). Falls back
            // to empty array if the method doesn't exist (e.g., during tests
            // with a stub user) or if the table hasn't been migrated yet.
            $pinnedIds = [];

            try {
                $pinnedIds = method_exists($user, 'getPinnedMenuItemIds')
                    ? $user->getPinnedMenuItemIds()
                    : [];
            } catch (\Throwable) {
                // Table may not exist yet (pre-migration). Degrade gracefully.
            }

            $view->with('pinnedMenuItemIds', $pinnedIds);
        });
    }
}
