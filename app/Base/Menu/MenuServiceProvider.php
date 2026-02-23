<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Menu;

use App\Base\Authz\Contracts\AuthorizationService;
use App\Base\Authz\DTO\Actor;
use App\Base\Menu\Services\MenuDiscoveryService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(MenuDiscoveryService::class);
        $this->app->singleton(MenuRegistry::class);
        $this->app->singleton(MenuBuilder::class);
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
            $authorizationService = $this->app->make(AuthorizationService::class);

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
            $actor = new Actor(
                type: 'human_user',
                id: (int) $user->getAuthIdentifier(),
                companyId: $user->getAttribute('company_id') !== null ? (int) $user->getAttribute('company_id') : null,
            );

            $filteredItems = $registry->getAll()->filter(function (MenuItem $item) use ($authorizationService, $actor): bool {
                if ($item->permission === null) {
                    return true;
                }

                return $authorizationService->can($actor, $item->permission)->allowed;
            });

            $menuTree = $builder->build($filteredItems, $currentRoute);

            $view->with('menuTree', $menuTree);
        });
    }
}
