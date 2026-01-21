<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database\Console\Commands;

use App\Base\Database\Concerns\InteractsWithModuleOption;
use Illuminate\Console\Command;
use Illuminate\Database\Console\Migrations\RefreshCommand as IlluminateRefreshCommand;

class RefreshCommand extends IlluminateRefreshCommand
{
    use InteractsWithModuleOption;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset and re-run all migrations (with module support)';

    /**
     * Execute the console command.
     *
     * Extends parent by passing through --module to migrate:reset / migrate:rollback / migrate.
     */
    public function handle(): int
    {
        $moduleOption = $this->option('module');

        // Re-implement parent flow so we can pass --module through.
        if ($this->isProhibited() || ! $this->confirmToProceed()) {
            return Command::FAILURE;
        }

        $database = $this->input->getOption('database');
        $path = $this->input->getOption('path');
        $step = $this->input->getOption('step') ?: 0;

        // In module mode, --step is ambiguous (it targets global order). Prefer a full
        // module reset, then migrate.
        if ($moduleOption && $step > 0) {
            $step = 0;
        }

        if ($step > 0) {
            $this->call('migrate:rollback', array_filter([
                '--database' => $database,
                '--path' => $path,
                '--realpath' => $this->input->getOption('realpath'),
                '--step' => $step,
                '--force' => true,
                '--module' => $moduleOption,
            ]));
        } else {
            $this->call('migrate:reset', array_filter([
                '--database' => $database,
                '--path' => $path,
                '--realpath' => $this->input->getOption('realpath'),
                '--force' => true,
                '--module' => $moduleOption,
            ]));
        }

        $this->call('migrate', array_filter([
            '--database' => $database,
            '--path' => $path,
            '--realpath' => $this->input->getOption('realpath'),
            '--force' => true,
            '--module' => $moduleOption,
            '--seed' => $this->needsSeeding(),
            '--seeder' => $this->option('seeder'),
        ]));

        if ($this->laravel->bound(\Illuminate\Contracts\Events\Dispatcher::class)) {
            $this->laravel[\Illuminate\Contracts\Events\Dispatcher::class]->dispatch(
                new \Illuminate\Database\Events\DatabaseRefreshed($database, $this->needsSeeding())
            );
        }

        return 0;
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
