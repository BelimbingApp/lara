<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database\Console\Commands;

use App\Base\Database\Concerns\InteractsWithModuleMigrations;
use Illuminate\Database\Console\Migrations\StatusCommand as IlluminateStatusCommand;

class StatusCommand extends IlluminateStatusCommand
{
    use InteractsWithModuleMigrations;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each migration (with module support)';

    /**
     * Execute the console command.
     *
     * Extends parent by loading module-specific migrations before reporting status.
     */
    public function handle(): int
    {
        $this->loadAllModuleMigrations();

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
