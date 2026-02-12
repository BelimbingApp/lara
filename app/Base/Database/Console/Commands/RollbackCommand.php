<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database\Console\Commands;

use App\Base\Database\Concerns\InteractsWithModuleMigrations;
use Illuminate\Database\Console\Migrations\RollbackCommand as IlluminateRollbackCommand;

class RollbackCommand extends IlluminateRollbackCommand
{
    use InteractsWithModuleMigrations;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the last database migration (with module support)';

    /**
     * Execute the console command.
     *
     * Extends parent by loading module-specific migrations before rolling back.
     * If --module option is provided, migrations are loaded from specified modules.
     */
    public function handle(): int
    {
        $modules = $this->getModules();
        foreach ($modules as $module) {
            $this->loadModuleMigrations($module);
        }

        // When modules are specified (including default ['*']) without --batch/--step,
        // Laravel will rollback the last global batch. That can include migrations
        // outside the module, which then cannot be resolved from module-only paths.
        // We instead target the latest batch that includes migrations from the selected module(s).
        if (! empty($modules) && ! $this->option('batch') && ! $this->option('step')) {
            $targetBatch = null;

            $this->migrator->usingConnection($this->option('database'), function () use (&$targetBatch) {
                if (! $this->migrator->repositoryExists()) {
                    return;
                }

                $batches = $this->migrator->getRepository()->getMigrationBatches();

                $moduleMigrationNames = array_map(
                    fn (string $file) => $this->migrator->getMigrationName($file),
                    $this->migrator->getMigrationFiles($this->getMigrationPaths()),
                );

                $moduleBatches = array_values(array_filter(array_map(
                    fn (string $name) => $batches[$name] ?? null,
                    $moduleMigrationNames,
                )));

                $targetBatch = $moduleBatches === [] ? null : max($moduleBatches);
            });

            if ($targetBatch === null) {
                $this->components->info('No module migrations to rollback.');

                return 0;
            }

            $this->input->setOption('batch', $targetBatch);
        }

        // Call parent handle() to run rollback
        return parent::handle();
    }

    /**
     * Get the console command options.
     *
     * Extends parent by adding --module option.
     *
     * {@inheritdoc}
     */
    protected function getOptions(): array
    {
        return $this->addModuleOption(parent::getOptions());
    }
}
