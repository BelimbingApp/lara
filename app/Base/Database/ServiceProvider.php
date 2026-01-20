<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand as LaravelMigrateCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand as LaravelRefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand as LaravelResetCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand as LaravelRollbackCommand;
use Illuminate\Database\Console\Migrations\StatusCommand as LaravelStatusCommand;
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

        // Override Laravel's RollbackCommand by extending the binding
        // Laravel's MigrationServiceProvider (deferred) binds RollbackCommand::class as a singleton,
        // so we extend the class name. The extend() callback runs when the binding is resolved.
        $this->app->extend(LaravelRollbackCommand::class, function ($command, $app) {
            return new RollbackCommand($app->make(Migrator::class));
        });

        $this->app->extend(LaravelStatusCommand::class, function ($command, $app) {
            return new StatusCommand($app->make(Migrator::class));
        });

        $this->app->extend(LaravelResetCommand::class, function ($command, $app) {
            return new ResetCommand($app->make(Migrator::class));
        });

        $this->app->extend(LaravelRefreshCommand::class, function ($command, $app) {
            return new RefreshCommand;
        });
    }
}
