<?php

use App\Base\Foundation\Providers\ProviderRegistry;

return ProviderRegistry::resolve(
    appProviders: [
        App\Providers\AppServiceProvider::class,
    ]
);
