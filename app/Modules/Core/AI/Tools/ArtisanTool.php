<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Tools;

use App\Modules\Core\AI\Contracts\DigitalWorkerTool;
use Illuminate\Support\Facades\Process;

/**
 * Artisan command execution tool for Digital Workers.
 *
 * Allows a DW to run `php artisan` commands on behalf of the user.
 * Gated by `ai.tool_artisan.execute` authz capability.
 *
 * Safety: Only `php artisan` commands are allowed. Laravel's Process
 * class uses proc_open without shell invocation, so metacharacters
 * have no shell-level effect. Timeout enforced per execution.
 */
class ArtisanTool implements DigitalWorkerTool
{
    private const TIMEOUT_SECONDS = 30;

    public function name(): string
    {
        return 'artisan';
    }

    public function description(): string
    {
        return 'Execute a php artisan command and return its output. '
            .'Use this to query data (e.g., tinker), run BLB commands, check system status, etc. '
            .'Only artisan commands are allowed — no arbitrary shell commands.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'command' => [
                    'type' => 'string',
                    'description' => 'The artisan command to run (without "php artisan" prefix). '
                        .'Examples: "tinker --execute=\'echo App\\\\Modules\\\\Core\\\\User\\\\Models\\\\User::count();\'", '
                        .'"blb:ai:catalog:sync --dry-run", "route:list --columns=name,uri".',
                ],
            ],
            'required' => ['command'],
        ];
    }

    public function requiredCapability(): ?string
    {
        return 'ai.tool_artisan.execute';
    }

    public function execute(array $arguments): string
    {
        $command = $arguments['command'] ?? '';

        if (! is_string($command) || trim($command) === '') {
            return 'Error: No command provided.';
        }

        $command = trim($command);

        // Strip "php artisan" prefix if the LLM included it
        $command = preg_replace('/^(php\s+)?artisan\s+/', '', $command) ?? $command;

        if ($command === '') {
            return 'Error: Empty command after parsing.';
        }

        $fullCommand = 'php artisan '.$command;

        $result = Process::timeout(self::TIMEOUT_SECONDS)
            ->path(base_path())
            ->run($fullCommand);

        $output = trim($result->output());
        $errorOutput = trim($result->errorOutput());

        if (! $result->successful()) {
            $message = 'Command failed (exit code '.$result->exitCode().').';

            if ($errorOutput !== '') {
                $message .= "\n".$errorOutput;
            }

            if ($output !== '') {
                $message .= "\n".$output;
            }

            return $message;
        }

        if ($output === '' && $errorOutput === '') {
            return 'Command completed successfully (no output).';
        }

        return $output !== '' ? $output : $errorOutput;
    }
}
