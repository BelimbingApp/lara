<?php

return [
    App\Base\Authz\AuthzServiceProvider::class,
    App\Base\Database\ServiceProvider::class,
    App\Base\Menu\MenuServiceProvider::class,
    App\Base\Routing\RouteServiceProvider::class,
    App\Modules\Core\Company\ServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\VoltServiceProvider::class,
];
