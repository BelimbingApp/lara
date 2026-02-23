<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\FreshCommand as IlluminateFreshCommand;
use Illuminate\Database\Events\DatabaseRefreshed;
use Symfony\Component\Console\Input\InputOption;

class FreshCommand extends IlluminateFreshCommand
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables and re-run all migrations (with module and dev support)';

    /**
     * Execute the console command.
     *
     * Re-implements parent to pass --seed, --seeder, and --dev through to `migrate`
     * instead of handling seeding separately. This lets MigrateCommand manage
     * production and dev seeders in one place.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->isProhibited() ||
            ! $this->confirmToProceed()) {
            return Command::FAILURE;
        }

        $database = $this->input->getOption('database');

        $this->migrator->usingConnection($database, function () use ($database) {
            if ($this->migrator->repositoryExists()) {
                $this->newLine();

                $this->components->task('Dropping all tables', fn () => $this->callSilent('db:wipe', array_filter([
                    '--database' => $database,
                    '--drop-views' => $this->option('drop-views'),
                    '--drop-types' => $this->option('drop-types'),
                    '--force' => true,
                ])) == 0);
            }
        });

        $this->newLine();

        // Pass --seed and --dev through to migrate so MigrateCommand handles
        // both production seeders and dev seeders in a single flow.
        $this->call('migrate', array_filter([
            '--database' => $database,
            '--path' => $this->input->getOption('path'),
            '--realpath' => $this->input->getOption('realpath'),
            '--schema-path' => $this->input->getOption('schema-path'),
            '--force' => true,
            '--step' => $this->option('step'),
            '--seed' => $this->needsSeeding(),
            '--seeder' => $this->option('seeder'),
            '--dev' => $this->option('dev'),
        ]));

        if ($this->laravel->bound(Dispatcher::class)) {
            $this->laravel[Dispatcher::class]->dispatch(
                new DatabaseRefreshed($database, $this->needsSeeding())
            );
        }

        return 0;
    }

    /**
     * Determine if the developer has requested database seeding.
     *
     * Extends parent to also consider --dev as needing seeding.
     *
     * @return bool
     */
    protected function needsSeeding()
    {
        return $this->option('seed') || $this->option('seeder') || $this->option('dev');
    }

    /**
     * Get the console command options.
     *
     * Extends parent by adding --dev option.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['dev', null, InputOption::VALUE_NONE, 'Run dev seeders after production seeders (APP_ENV=local only). Implies --seed.'],
        ]);
    }
}
