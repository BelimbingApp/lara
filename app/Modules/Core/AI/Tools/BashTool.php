<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Tools;

use App\Modules\Core\AI\Contracts\DigitalWorkerTool;
use Illuminate\Support\Facades\Process;

/**
 * Bash CLI execution tool for Digital Workers.
 *
 * Allows a DW to run arbitrary bash commands on behalf of the user.
 * This is the most powerful tool — gated by `ai.tool_bash.execute`.
 *
 * Safety: Timeout enforced per execution. Authz gating is the primary
 * control — only users with explicit bash capability can trigger this.
 */
class BashTool implements DigitalWorkerTool
{
    private const TIMEOUT_SECONDS = 30;

    public function name(): string
    {
        return 'bash';
    }

    public function description(): string
    {
        return 'Execute a bash command and return its output. '
            .'Use this for system commands, file operations, package management, git, etc. '
            .'Commands run from the BLB project root directory.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'command' => [
                    'type' => 'string',
                    'description' => 'The bash command to execute. '
                        .'Examples: "ls -la storage/app", "cat .env | grep DB_", "git log --oneline -5".',
                ],
            ],
            'required' => ['command'],
        ];
    }

    public function requiredCapability(): ?string
    {
        return 'ai.tool_bash.execute';
    }

    public function execute(array $arguments): string
    {
        $command = $arguments['command'] ?? '';

        if (! is_string($command) || trim($command) === '') {
            return 'Error: No command provided.';
        }

        $command = trim($command);

        $result = Process::timeout(self::TIMEOUT_SECONDS)
            ->path(base_path())
            ->run($command);

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
