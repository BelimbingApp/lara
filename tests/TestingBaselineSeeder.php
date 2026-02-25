<?php

namespace Tests;

use App\Base\Database\Models\SeederRegistry;
use App\Base\Foundation\ModuleConfigRegistry;
use Illuminate\Database\Seeder;

/**
 * Baseline seed data for automated tests.
 *
 * Runs production seeders only for modules that opt in via config:
 * config key is resolved from ModuleConfigRegistry; then <config_key>.seed_for_testing must be true.
 * Registry is populated by migrations before this seeder runs.
 */
class TestingBaselineSeeder extends Seeder
{
    /**
     * Seed by running registry seeders for modules that opted in to testing.
     */
    public function run(): void
    {
        $modules = $this->moduleNamesForTesting();

        if ($modules === []) {
            return;
        }

        $seedersToRun = SeederRegistry::query()
            ->runnable()
            ->forModules($modules)
            ->inMigrationOrder()
            ->get();

        foreach ($seedersToRun as $seeder) {
            $seeder->markAsRunning();

            try {
                $this->call($seeder->seeder_class);
                $seeder->markAsCompleted();
            } catch (\Throwable $e) {
                $seeder->markAsFailed($e->getMessage());
                throw $e;
            }
        }
    }

    /**
     * Module names that opted in via config (ModuleConfigRegistry config key + seed_for_testing = true).
     *
     * @return array<int, string>
     */
    private function moduleNamesForTesting(): array
    {
        $names = SeederRegistry::query()
            ->distinct()
            ->pluck('module_name')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        return array_values(array_filter($names, function (string $moduleName): bool {
            $configKey = ModuleConfigRegistry::getConfigKey($moduleName);
            if ($configKey === null) {
                return false;
            }

            return (bool) config($configKey.'.seed_for_testing', false);
        }));
    }
}
