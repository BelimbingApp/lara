<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Services;

use App\Modules\Core\AI\Models\AiProvider;
use App\Modules\Core\Employee\Models\Employee;

/**
 * Resolves LLM configuration for a Digital Worker.
 *
 * Cascade: DW workspace config.json → company provider credentials → runtime defaults.
 * Supports multiple models with ordered fallback (first model is primary, rest are fallbacks).
 */
class ConfigResolver
{
    /**
     * Resolve an ordered list of LLM configurations for a Digital Worker.
     *
     * Returns one or more configs in priority order. The runtime should try
     * the first config and fall back to subsequent ones on transient failures
     * (connection error, HTTP 429, 5xx).
     *
     * Returns an empty array if no LLM configuration is available.
     *
     * @param  int  $employeeId  Digital Worker employee ID
     * @return list<array{api_key: string, base_url: string, model: string, max_tokens: int, temperature: float, timeout: int, provider_name: string|null}>
     */
    public function resolve(int $employeeId): array
    {
        $workspaceConfig = $this->readWorkspaceConfig($employeeId);

        if ($workspaceConfig === null) {
            return [];
        }

        $modelConfigs = $workspaceConfig['llm']['models'] ?? [];

        if (count($modelConfigs) === 0) {
            return [];
        }

        $employee = Employee::query()->find($employeeId);
        $companyId = $employee?->company_id ? (int) $employee->company_id : null;

        $runtimeDefaults = $this->runtimeDefaults();
        $resolved = [];

        foreach ($modelConfigs as $modelConfig) {
            $resolved[] = $this->resolveModelConfig($modelConfig, $companyId, $runtimeDefaults);
        }

        return $resolved;
    }

    /**
     * Read the workspace config.json for a Digital Worker.
     *
     * @param  int  $employeeId  Digital Worker employee ID
     * @return array<string, mixed>|null
     */
    public function readWorkspaceConfig(int $employeeId): ?array
    {
        $path = config('ai.workspace_path').'/'.$employeeId.'/config.json';

        if (! file_exists($path)) {
            return null;
        }

        $data = json_decode(file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }

    /**
     * Write workspace config.json for a Digital Worker.
     *
     * @param  int  $employeeId  Digital Worker employee ID
     * @param  array<string, mixed>  $config  Configuration to write
     */
    public function writeWorkspaceConfig(int $employeeId, array $config): void
    {
        $dir = config('ai.workspace_path').'/'.$employeeId;

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $dir.'/config.json',
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }

    /**
     * Resolve a single model config entry against company providers and runtime defaults.
     *
     * @param  array<string, mixed>  $modelConfig  Per-model config from workspace
     * @param  int|null  $companyId  Company ID for provider lookup
     * @param  array<string, mixed>  $runtimeDefaults  Fallback runtime parameters
     * @return array{api_key: string, base_url: string, model: string, max_tokens: int, temperature: float, timeout: int, provider_name: string|null}
     */
    private function resolveModelConfig(array $modelConfig, ?int $companyId, array $runtimeDefaults): array
    {
        $resolved = [
            'api_key' => '',
            'base_url' => '',
            'model' => $modelConfig['model'] ?? '',
            'max_tokens' => (int) ($modelConfig['max_tokens'] ?? $runtimeDefaults['max_tokens']),
            'temperature' => (float) ($modelConfig['temperature'] ?? $runtimeDefaults['temperature']),
            'timeout' => (int) ($modelConfig['timeout'] ?? $runtimeDefaults['timeout']),
            'provider_name' => null,
        ];

        $providerName = $modelConfig['provider'] ?? null;

        if ($providerName !== null && $companyId !== null) {
            $provider = AiProvider::query()
                ->forCompany($companyId)
                ->active()
                ->where('name', $providerName)
                ->first();

            if ($provider) {
                $resolved['api_key'] = $provider->api_key;
                $resolved['base_url'] = $provider->base_url;
                $resolved['provider_name'] = $provider->name;
            }
        }

        return $resolved;
    }

    /**
     * Get runtime parameter defaults from application config.
     *
     * @return array{max_tokens: int, temperature: float, timeout: int}
     */
    private function runtimeDefaults(): array
    {
        return [
            'max_tokens' => (int) config('ai.llm.max_tokens', 2048),
            'temperature' => (float) config('ai.llm.temperature', 0.7),
            'timeout' => (int) config('ai.llm.timeout', 60),
        ];
    }
}
