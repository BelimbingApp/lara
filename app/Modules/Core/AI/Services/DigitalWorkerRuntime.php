<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Services;

use App\Modules\Core\AI\DTO\Message;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Stage 0 Digital Worker runtime adapter.
 *
 * Calls an OpenAI-compatible chat completions API using Laravel's HTTP client.
 * Supports per-DW LLM configuration with ordered fallback: tries each configured
 * model in priority order, falling back on transient failures (connection error,
 * HTTP 429, 5xx).
 */
class DigitalWorkerRuntime
{
    public function __construct(
        private readonly ConfigResolver $configResolver,
    ) {}

    /**
     * Run a conversation turn and return the assistant response with metadata.
     *
     * Resolves LLM config for the given Digital Worker and tries models in
     * priority order, falling back on transient failures.
     *
     * @param  list<Message>  $messages  Conversation history
     * @param  int  $employeeId  Digital Worker employee ID
     * @param  string|null  $systemPrompt  Optional system prompt for the Digital Worker
     * @return array{content: string, run_id: string, meta: array<string, mixed>}
     */
    public function run(array $messages, int $employeeId, ?string $systemPrompt = null): array
    {
        $runId = 'run_'.Str::random(12);
        $configs = $this->configResolver->resolve($employeeId);

        $lastResult = null;

        foreach ($configs as $config) {
            $result = $this->tryModel($messages, $systemPrompt, $config, $runId);

            if (! $this->shouldFallback($result)) {
                return $result;
            }

            $lastResult = $result;
        }

        return $lastResult ?? $this->errorResult($runId, 'unknown', 0, __('No LLM configuration available.'));
    }

    /**
     * Try a single model configuration and return the result.
     *
     * @param  list<Message>  $messages  Conversation history
     * @param  string|null  $systemPrompt  Optional system prompt
     * @param  array{api_key: string, base_url: string, model: string, max_tokens: int, temperature: float, timeout: int, provider_name: string|null}  $config
     * @param  string  $runId  Run identifier
     * @return array{content: string, run_id: string, meta: array<string, mixed>}
     */
    private function tryModel(array $messages, ?string $systemPrompt, array $config, string $runId): array
    {
        $model = $config['model'];
        $apiKey = $config['api_key'];
        $baseUrl = $config['base_url'];

        if (empty($apiKey)) {
            return $this->errorResult($runId, $model, 0, __('API key is not configured for provider :provider.', [
                'provider' => $config['provider_name'] ?? 'default',
            ]), 'config_error');
        }

        if (empty($baseUrl)) {
            return $this->errorResult($runId, $model, 0, __('Base URL is not configured for provider :provider.', [
                'provider' => $config['provider_name'] ?? 'default',
            ]), 'config_error');
        }

        $startTime = hrtime(true);
        $apiMessages = $this->buildApiMessages($messages, $systemPrompt);

        try {
            $response = Http::withToken($apiKey)
                ->timeout($config['timeout'])
                ->post($baseUrl.'/chat/completions', [
                    'model' => $model,
                    'messages' => $apiMessages,
                    'max_tokens' => $config['max_tokens'],
                    'temperature' => $config['temperature'],
                ]);
        } catch (ConnectionException $e) {
            $latencyMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

            return $this->errorResult($runId, $model, $latencyMs, $e->getMessage(), 'connection_error');
        }

        $latencyMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

        if ($response->failed()) {
            $body = $response->json();
            $errorDetail = $body['error']['message']
                ?? $body['error']['code']
                ?? $response->body();

            $errorType = match (true) {
                $response->status() === 429 => 'rate_limit',
                $response->status() >= 500 => 'server_error',
                default => 'client_error',
            };

            return $this->errorResult($runId, $model, $latencyMs, "HTTP {$response->status()}: {$errorDetail}", $errorType);
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '';
        $usage = $data['usage'] ?? [];

        return [
            'content' => $content,
            'run_id' => $runId,
            'meta' => [
                'model' => $model,
                'provider_name' => $config['provider_name'],
                'latency_ms' => $latencyMs,
                'tokens' => [
                    'prompt' => $usage['prompt_tokens'] ?? null,
                    'completion' => $usage['completion_tokens'] ?? null,
                ],
            ],
        ];
    }

    /**
     * Determine whether the runtime should fall back to the next model.
     *
     * Falls back on transient failures (connection, rate limit, server error).
     * Does NOT fall back on client errors (400, 401, 403) or success.
     *
     * @param  array{content: string, run_id: string, meta: array<string, mixed>}  $result
     */
    private function shouldFallback(array $result): bool
    {
        $errorType = $result['meta']['error_type'] ?? null;

        return in_array($errorType, ['connection_error', 'rate_limit', 'server_error'], true);
    }

    /**
     * Build an error response with the detail surfaced in both chat and debug panel.
     *
     * @return array{content: string, run_id: string, meta: array<string, mixed>}
     */
    private function errorResult(string $runId, string $model, int $latencyMs, string $detail, string $errorType = 'unknown'): array
    {
        return [
            'content' => __('⚠ :detail', ['detail' => $detail]),
            'run_id' => $runId,
            'meta' => [
                'model' => $model,
                'latency_ms' => $latencyMs,
                'error' => $detail,
                'error_type' => $errorType,
            ],
        ];
    }

    /**
     * Build the messages array for the OpenAI API.
     *
     * @param  list<Message>  $messages
     * @return list<array{role: string, content: string}>
     */
    private function buildApiMessages(array $messages, ?string $systemPrompt): array
    {
        $apiMessages = [];

        if ($systemPrompt !== null) {
            $apiMessages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        foreach ($messages as $message) {
            if ($message->role === 'user' || $message->role === 'assistant') {
                $apiMessages[] = [
                    'role' => $message->role,
                    'content' => $message->content,
                ];
            }
        }

        return $apiMessages;
    }
}
