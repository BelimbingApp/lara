<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Foundation;

/**
 * Registry mapping module names (e.g. 'Authz', 'Company') to Laravel config keys.
 *
 * Modules register in their ServiceProvider so consumers can resolve config key
 * without assuming a naming convention. Used by test baseline seeding and any
 * feature that needs config-by-module.
 */
final class ModuleConfigRegistry
{
    /**
     * Module name => config key (e.g. 'Authz' => 'authz').
     *
     * @var array<string, string>
     */
    private static array $configKeys = [];

    /**
     * Register the config key for a module.
     *
     * Call from the module's ServiceProvider; the key should match the second
     * argument to mergeConfigFrom() for that module.
     *
     * @param string $moduleName  Module name (e.g. 'Authz', 'Company')
     * @param string $configKey   Config key (e.g. 'authz', 'company')
     */
    public static function register(string $moduleName, string $configKey): void
    {
        self::$configKeys[$moduleName] = $configKey;
    }

    /**
     * Return the config key for a module, or null if not registered.
     *
     * @param string $moduleName Module name (e.g. 'Authz')
     * @return string|null Config key (e.g. 'authz') or null
     */
    public static function getConfigKey(string $moduleName): ?string
    {
        return self::$configKeys[$moduleName] ?? null;
    }
}
