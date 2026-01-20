<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database\Concerns;

use Symfony\Component\Console\Input\InputOption;

trait InteractsWithModuleOption
{
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
     * Add the --module option to the command options array.
     *
     * @param  array  $options  Parent command options
     * @return array Options with --module added
     */
    protected function addModuleOption(array $options): array
    {
        $options[] = [
            'module',
            null,
            InputOption::VALUE_REQUIRED,
            'Load migrations by module(s) (comma-delimited, case-sensitive)',
        ];

        return $options;
    }
}
