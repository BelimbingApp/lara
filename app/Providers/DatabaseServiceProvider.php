<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * This method runs during service registration and only loads migrations
     * when running in console (artisan commands), avoiding HTTP request overhead.
     */
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadModuleMigrations();
        }
    }

    /**
     * Auto-discover and load migrations from all modules.
     * Migrations are run in filename (timestamp prefix) order, not array order.
     */
    protected function loadModuleMigrations(): void
    {
        $migrationPaths = [];
        $patterns = [
            "Base" => "/*/Database/Migrations",
            "Modules" => "/*/*/Database/Migrations",
        ];

        foreach ($patterns as $layer => $pattern) {
            $path = app_path($layer);
            if (is_dir($path)) {
                $found = glob($path . $pattern, GLOB_ONLYDIR);
                if ($found !== false) {
                    $migrationPaths = array_merge($migrationPaths, $found);
                }
            }
        }

        /**
         * The paths will be sorted by filename here:
         * @see vendor/laravel/framework/src/Illuminate/Database/Migrations/Migrator.php
         * @method getMigrationFiles()
         */
        if ($migrationPaths) {
            $this->loadMigrationsFrom($migrationPaths);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
