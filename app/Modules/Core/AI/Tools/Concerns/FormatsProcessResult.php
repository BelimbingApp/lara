<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Tools\Concerns;

use Illuminate\Contracts\Process\ProcessResult;

trait FormatsProcessResult
{
    protected function formatProcessResult(ProcessResult $result): string
    {
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
