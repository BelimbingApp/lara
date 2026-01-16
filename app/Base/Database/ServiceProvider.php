<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand as LaravelMigrateCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Override Laravel's MigrateCommand by extending the binding
        // Laravel's MigrationServiceProvider (deferred) binds MigrateCommand::class directly,
        // so we extend the class name, not an alias. The extend() callback runs when
        // the binding is resolved, after Laravel's MigrationServiceProvider registers it.
        $this->app->extend(LaravelMigrateCommand::class, function ($command, $app) {
            return new MigrateCommand(
                $app->make(Migrator::class),
                $app->make(Dispatcher::class)
            );
        });
    }
}
