<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database;

use Illuminate\Database\Console\Migrations\MigrateCommand as IlluminateMigrateCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'migrate')]
class MigrateCommand extends IlluminateMigrateCommand
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database migrations (with module support)';

    /**
     * Configure the command options by adding --module to the parent definition.
     *
     * @inheritdoc
     */
    protected function configure(): void
    {
        parent::configure();

        $this->getDefinition()->addOption(
            new InputOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Load migrations by module(s)'
            )
        );
    }

    /**
     * Execute the console command.
     *
     * @inheritdoc
     */
    public function handle()
    {
        // Load module migrations if --module option is provided
        $modules = $this->option('module');
        if ($modules) {
            foreach ((array) $modules as $module) {
                if ($module !== '') {
                    $this->loadModuleMigrations($module);
                }
            }
        }

        // Call parent handle() to run migrations
        return parent::handle();
    }

    /**
     * Load migrations for a specific module with case-insensitive matching.
     * Searches in Base and Modules layers for the specified module name.
     *
     * @param string $moduleName case-insensitive, "*" for all modules
     */
    protected function loadModuleMigrations(string $moduleName): void
    {
        $migrationPaths = [];

        $layers = [
            app_path('Base') => '/*',
            app_path('Modules') => '/*/*',
        ];
        foreach ($layers as $appPath => $pattern) {
            if (is_dir($appPath)) {
                $moduleDirs = glob($appPath . $pattern, GLOB_ONLYDIR);
                $this->addMigrationPaths($migrationPaths, $moduleDirs, $moduleName);
            }
        }

        foreach ($migrationPaths as $path) {
            $this->migrator->path($path);
        }
    }

    /**
     * Add migration paths for matching modules to the given array.
     *
     * Searches through module directories and adds migration paths for directories
     * that match the specified module name (case-insensitive). Supports "*" wildcard
     * to include all modules. Migration paths are added as: {moduleDir}/Database/Migrations
     *
     * @param array $migrationPaths Reference to array that will be populated with paths
     * @param array $moduleDirs Array of module directory paths to search
     * @param string $moduleName Module name to match (case-insensitive), or "*" for all modules
     */
    protected function addMigrationPaths(array &$migrationPaths, array $moduleDirs, string $moduleName): void
    {
        foreach ($moduleDirs as $moduleDir) {
            // If not wildcard, skip directories that don't match module name (case-insensitive)
            if ($moduleName !== '*' && strcasecmp(basename($moduleDir), $moduleName) !== 0) {
                continue;
            }

            // Check if migrations directory exists for this module
            $migrationPath = "$moduleDir/Database/Migrations";
            if (is_dir($migrationPath)) {
                $migrationPaths[] = $migrationPath;
            }
        }
    }
}
