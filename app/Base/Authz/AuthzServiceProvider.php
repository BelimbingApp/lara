<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz;

use App\Base\Authz\Capability\CapabilityCatalog;
use App\Base\Authz\Capability\CapabilityRegistry;
use App\Base\Authz\Contracts\AuthorizationService;
use App\Base\Authz\Services\AuthorizationServiceImpl;
use Illuminate\Support\ServiceProvider;

class AuthzServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/authz.php', 'authz');

        $this->app->singleton(CapabilityCatalog::class, function (): CapabilityCatalog {
            /** @var array<string, mixed> $config */
            $config = config('authz');

            return CapabilityCatalog::fromConfig($config);
        });

        $this->app->singleton(CapabilityRegistry::class, function ($app): CapabilityRegistry {
            $catalog = $app->make(CapabilityCatalog::class);

            return CapabilityRegistry::fromCatalog($catalog);
        });

        $this->app->singleton(AuthorizationService::class, AuthorizationServiceImpl::class);
    }
}
