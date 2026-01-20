<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database;

use App\Base\Database\Models\SeederRegistry;

trait RegistersSeeders
{
    /**
     * Register a seeder to be run after migration.
     * Simple interface: just pass the seeder class. Module path is auto-derived.
     */
    protected function registerSeeder(string $seederClass): void
    {
        // Get current migration file name and path
        $migrationPath = (new \ReflectionClass($this))->getFileName();
        $migrationFile = basename($migrationPath);
        $modulePath = $this->extractModulePath($migrationPath);
        $moduleName = $this->extractModuleName($modulePath);

        // Delegate to model for database operations
        SeederRegistry::register($seederClass, $moduleName, $modulePath, $migrationFile);
    }

    /**
     * Unregister a seeder (typically called in down() for clean rollbacks).
     */
    protected function unregisterSeeder(string $seederClass): void
    {
        // Delegate to model for database operations
        SeederRegistry::unregister($seederClass);
    }

    /**
     * Extract module path from migration file path.
     *
     * @param  string  $migrationPath  Full path to migration file
     * @return string|null Module path (e.g., 'app/Modules/Core/Geonames')
     */
    protected function extractModulePath(string $migrationPath): ?string
    {
        // Extract path from migration file location
        // Pattern: .../app/Modules/{Layer}/{Module}/Database/Migrations/{file}
        if (preg_match('#app/Modules/[^/]+/[^/]+#', $migrationPath, $matches)) {
            return $matches[0];
        }

        // Pattern: .../app/Base/{Module}/Database/Migrations/{file}
        if (preg_match('#app/Base/[^/]+#', $migrationPath, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Extract module name from module path.
     * e.g., 'app/Modules/Core/Geonames' -> 'Geonames'
     */
    protected function extractModuleName(?string $modulePath): ?string
    {
        if (!$modulePath) {
            return null;
        }
        return basename($modulePath);
    }
}
