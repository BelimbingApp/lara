<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database;

use App\Base\Database\Models\SeederRegistry;
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
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this->getDefinition()->addOption(
            new InputOption(
                'module',
                null,
                InputOption::VALUE_REQUIRED,
                'Load migrations by module(s) (comma-delimited, case-sensitive)',
            ),
        );
    }

    /**
     * Get the modules specified via --module option.
     *
     * Parses comma-delimited module names into an array.
     * Returns empty array if no modules specified.
     * Supports "*" wildcard to match all modules.
     *
     * @return array Array of case-sensitive module names (or ["*"] for all modules)
     */
    protected function getModules(): array
    {
        $moduleOption = $this->option('module');

        if (! $moduleOption) {
            return [];
        }

        // Split by comma and trim whitespace
        $modules = array_map('trim', explode(',', $moduleOption));

        // Filter out empty strings
        return array_filter($modules, fn ($module) => $module !== '');
    }

    /**
     * Execute the console command.
     *
     * Extends parent by loading module-specific migrations before running.
     * If --module option is provided, migrations are loaded from specified modules.
     */
    public function handle(): int
    {
        $modules = $this->getModules();
        foreach ($modules as $module) {
            $this->loadModuleMigrations($module);
        }

        // Call parent handle() to run migrations
        return parent::handle();
    }

    /**
     * Run the pending migrations.
     *
     * Overrides parent to handle module-aware seeding.
     */
    protected function runMigrations(): void
    {
        $this->migrator->usingConnection(
            $this->option('database'),
            function () {
                $this->prepareDatabase();

                // Next, we will check to see if a path option has been defined. If it has
                // we will use the path relative to the root of this installation folder
                // so that migrations may be run for any path within the applications.
                $this->migrator
                    ->setOutput($this->output)
                    ->run($this->getMigrationPaths(), [
                        'pretend' => $this->option('pretend'),
                        'step' => $this->option('step'),
                    ]);

                // Handle seeding with module-aware auto-discovery
                if ($this->option('seed') && ! $this->option('pretend')) {
                    $this->runModuleSeeders();
                }
            },
        );
    }

    /**
     * Run seeders with module-aware registry-based execution.
     *
     * If --seeder is provided, uses that seeder (overrides registry).
     * If --module is provided, only seeds matching modules.
     * Otherwise, seeds all pending seeders from registry.
     */
    protected function runModuleSeeders(): void
    {
        // If --seeder is explicitly provided, use it (overrides registry)
        if ($this->option('seeder')) {
            $this->call('db:seed', [
                '--class' => $this->option('seeder'),
                '--force' => true,
            ]);

            return;
        }

        // Query registry for runnable seeders (pending or failed)
        // Order by migration_file to ensure correct execution order
        $seedersToRun = SeederRegistry::runnable()
            ->forModules($this->getModules())
            ->inMigrationOrder()
            ->get();

        // Run each seeder with status tracking
        foreach ($seedersToRun as $seeder) {
            // Mark as running, clear previous error if retrying
            $seeder->markAsRunning();

            try {
                $this->call('db:seed', [
                    '--class' => $seeder->seeder_class,
                    '--force' => true,
                ]);

                // Mark as completed
                $seeder->markAsCompleted();
            } catch (\Exception $e) {
                // Mark as failed
                $seeder->markAsFailed($e->getMessage());

                // Re-throw to stop execution
                throw $e;
            }
        }
    }

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
            $paths = glob($appPath.$pattern, GLOB_ONLYDIR);
            if (! $paths) {
                continue;
            }
            $migrationPaths = array_merge($migrationPaths, $paths);
        }

        foreach ($migrationPaths as $path) {
            $this->migrator->path($path);
        }
    }
}
