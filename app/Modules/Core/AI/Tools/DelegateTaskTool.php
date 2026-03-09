<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Tools;

use App\Modules\Core\AI\Contracts\DigitalWorkerTool;
use App\Modules\Core\AI\Services\LaraCapabilityMatcher;
use App\Modules\Core\AI\Services\LaraTaskDispatcher;

/**
 * Task delegation tool for Digital Workers.
 *
 * Allows a DW to delegate a sub-task to another accessible Digital Worker,
 * enabling multi-agent workflows within BLB. The best-matched worker is
 * selected automatically unless a specific worker_id is supplied.
 *
 * Gated by `ai.tool_delegate.execute` authz capability.
 */
class DelegateTaskTool implements DigitalWorkerTool
{
    private const ERROR_PREFIX = 'Error: ';

    public function __construct(
        private readonly LaraCapabilityMatcher $capabilityMatcher,
        private readonly LaraTaskDispatcher $taskDispatcher,
    ) {}

    public function name(): string
    {
        return 'delegate_task';
    }

    public function description(): string
    {
        return 'Delegate a sub-task to another accessible Digital Worker. '
            .'Use this when the task requires a specialist or a different DW\'s capabilities. '
            .'Omit worker_id to auto-select the best match, or supply it to target a specific worker.';
    }

    /**
     * @return array<string, mixed>
     */
    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'task' => [
                    'type' => 'string',
                    'description' => 'A clear description of the sub-task to delegate. '
                        .'Example: "Generate a sales report for Q1 2025 and email it to the finance team."',
                ],
                'worker_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: the employee ID of a specific Digital Worker to delegate to. '
                        .'Leave blank to auto-select the best available worker.',
                ],
            ],
            'required' => ['task'],
        ];
    }

    public function requiredCapability(): ?string
    {
        return 'ai.tool_delegate.execute';
    }

    /**
     * Execute the tool with the given arguments.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function execute(array $arguments): string
    {
        $task = $arguments['task'] ?? '';

        if (! is_string($task) || trim($task) === '') {
            return self::ERROR_PREFIX.'No task description provided.';
        }

        return $this->dispatchTask(trim($task), $arguments);
    }

    /**
     * Resolve the target worker and dispatch the task.
     *
     * @param  array<string, mixed>  $arguments
     */
    private function dispatchTask(string $task, array $arguments): string
    {
        $workerId = isset($arguments['worker_id']) ? (int) $arguments['worker_id'] : null;
        $match = $this->resolveWorker($task, $workerId);

        if ($match === null) {
            return self::ERROR_PREFIX.'No accessible Digital Worker is available for this task.';
        }

        try {
            $dispatch = $this->taskDispatcher->dispatchForCurrentUser($match['employee_id'], $task);
        } catch (\Exception $e) {
            return self::ERROR_PREFIX.'Task delegation failed — '.$e->getMessage();
        }

        return 'Task delegated to '.$dispatch['employee_name']
            .' (dispatch ID: '.$dispatch['dispatch_id'].').';
    }

    /**
     * Find a specific worker by ID, or auto-select the best match for the task.
     *
     * @return array{employee_id: int, name: string, capability_summary: string}|null
     */
    private function resolveWorker(string $task, ?int $workerId): ?array
    {
        if ($workerId !== null) {
            return $this->capabilityMatcher->findAccessibleWorkerById($workerId);
        }

        return $this->capabilityMatcher->matchBestForTask($task);
    }
}
