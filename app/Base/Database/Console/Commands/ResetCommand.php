<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database\Console\Commands;

use App\Base\Database\Concerns\GuardsGlobalReset;
use App\Base\Database\Concerns\InteractsWithModuleMigrations;
use Illuminate\Database\Console\Migrations\ResetCommand as IlluminateResetCommand;
use Symfony\Component\Console\Input\InputOption;

class ResetCommand extends IlluminateResetCommand
{
    use GuardsGlobalReset;
    use InteractsWithModuleMigrations;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback all database migrations (with module support)';

    /**
     * Execute the console command.
     *
     * Extends parent by loading module-specific migrations before resetting.
     * Blocks unscoped global reset to prevent accidental full database wipes.
     */
    public function handle(): int
    {
        if ($result = $this->guardGlobalReset()) {
            return $result;
        }

        $this->loadAllModuleMigrations();

        return (int) parent::handle();
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
        $options = $this->addModuleOption(parent::getOptions());

        $options[] = [
            'force-wipe',
            null,
            InputOption::VALUE_NONE,
            'Allow destructive global reset (bypasses safety guard).',
        ];

        return $options;
    }
}
