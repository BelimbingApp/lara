<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database\Concerns;

/**
 * Trait for interacting with module-aware migrations.
 *
 * This trait is intended to be used by Laravel migration commands that extend
 * \Illuminate\Database\Console\Migrations\BaseCommand or its subclasses.
 *
 * @mixin \Illuminate\Database\Console\Migrations\BaseCommand
 *
 * @property \Illuminate\Database\Migrations\Migrator $migrator
 */
trait InteractsWithModuleMigrations
{
    use InteractsWithModuleOption;

    /**
     * Load migrations for a specific module with case-sensitive matching.
     * Searches in Base and Modules layers for the specified module name.
     *
     * @param  string  $moduleName  case-sensitive, "*" for all modules
     */
    protected function loadModuleMigrations(string $moduleName): void
    {
        $migrationPaths = [];

        $layers = [
            app_path('Base') => "/$moduleName/Database/Migrations",
            app_path('Modules') => "/*/$moduleName/Database/Migrations",
        ];

        foreach ($layers as $appPath => $pattern) {
            $paths = glob($appPath.$pattern, GLOB_ONLYDIR) ?: [];
            $migrationPaths = array_merge($migrationPaths, $paths);
        }

        foreach ($migrationPaths as $path) {
            $this->migrator->path($path);
        }
    }

    /**
     * Load migrations for all specified modules.
     *
     * Iterates through modules from --module option and loads their migrations.
     */
    protected function loadAllModuleMigrations(): void
    {
        foreach ($this->getModules() as $module) {
            $this->loadModuleMigrations($module);
        }
    }

    /**
     * Get all of the migration paths.
     *
     * Always includes `database/migrations/` for Laravel core tables (cache, jobs, sessions),
     * plus module migration paths when modules are specified.
     *
     * @return string[]
     */
    protected function getMigrationPaths(): array
    {
        $paths = $this->migrator->paths();

        // Always include database/migrations for Laravel core tables (cache, jobs, sessions)
        $corePath = $this->laravel->databasePath('migrations');
        if (!in_array($corePath, $paths)) {
            $paths[] = $corePath;
        }

        return $paths;
    }
}
