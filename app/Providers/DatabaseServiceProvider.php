<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole() && $module = $this->getMigrateModule()) {
            $this->loadModuleMigrations($module);
        }
    }

    /**
     * Detect module parameter from `artisan migrate --module=value` command.
     */
    protected function getMigrateModule(): string
    {
        // Check if command is 'migrate' (argv[0] = 'artisan', argv[1] = command)
        if (!isset($_SERVER['argv'][1]) || $_SERVER['argv'][1] !== 'migrate') {
            return '';
        }

        // Search for module parameter in remaining arguments
        foreach (array_slice($_SERVER['argv'], 2) as $arg) {
            // Match: --module=geonames (strict, requires non-empty value)
            if (preg_match('/^--module=([^=]+)$/', $arg, $matches)) {
                return $matches[1];
            }
        }

        return '';
    }

    /**
     * Load migrations for a specific module, "*" for all modules.
     * Searches in Base and Modules layers for the specified module name.
     */
    protected function loadModuleMigrations(string $module): void
    {
        $migrationPaths = [];
        $patterns = [
            "Base" => "/$module/Database/Migrations",
            "Modules" => "/*/$module/Database/Migrations",
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

    public function register(): void
    {
        //
    }
}
