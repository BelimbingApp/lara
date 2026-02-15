<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database\Console\Commands;

use App\Base\Database\Concerns\InteractsWithModuleMigrations;
use App\Base\Database\Models\SeederRegistry;
use Illuminate\Database\Console\Migrations\MigrateCommand as IlluminateMigrateCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'migrate')]
class MigrateCommand extends IlluminateMigrateCommand
{
    use InteractsWithModuleMigrations;

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
     * Execute the console command.
     *
     * Extends parent by loading module-specific migrations before running.
     * If --module option is provided, migrations are loaded from specified modules.
     */
    public function handle(): int
    {
        $this->loadAllModuleMigrations();

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
            $class = $this->normalizeSeederClass($this->option('seeder'));
            $this->call('db:seed', [
                '--class' => $class,
                '--force' => true,
            ]);

            return;
        }

        SeederRegistry::ensureDiscoveredRegistered();

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
     * Normalize seeder class so FQCN reaches db:seed (avoids shell stripping backslashes).
     *
     * Supports shorthand formats:
     * - Module/SeederClass → App\Modules\Core\Module\Database\Seeders\SeederClass
     * - Module/Sub/SeederClass → App\Modules\Core\Module\Database\Seeders\Sub\SeederClass
     */
    private function normalizeSeederClass(string $value): string
    {
        if (str_contains($value, '\\')) {
            return $value;
        }

        $parts = explode('/', $value);

        if (count($parts) >= 2) {
            $module = array_shift($parts);
            $remaining = implode('\\', $parts);

            return 'App\\Modules\\Core\\'.$module.'\\Database\\Seeders\\'.$remaining;
        }

        return $value;
    }
}
