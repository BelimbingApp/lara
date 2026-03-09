<?php

use App\Base\AI\Services\GithubCopilotAuthService;
use App\Base\AI\Services\LlmClient;
use App\Modules\Core\AI\DTO\Message;
use App\Modules\Core\AI\Services\ConfigResolver;
use App\Modules\Core\AI\Services\DigitalWorkerRuntime;
use Tests\TestCase;

uses(TestCase::class);

function makeRuntime(
    ConfigResolver $configResolver,
    LlmClient $llmClient,
    ?GithubCopilotAuthService $copilotAuth = null,
): DigitalWorkerRuntime {
    return new DigitalWorkerRuntime(
        $configResolver,
        $llmClient,
        $copilotAuth ?? Mockery::mock(GithubCopilotAuthService::class),
    );
}

function makeConfig(string $provider, string $model, string $apiKey = 'sk-test', string $baseUrl = 'https://api.example.com/v1'): array
{
    return [
        'api_key' => $apiKey,
        'base_url' => $baseUrl,
        'model' => $model,
        'max_tokens' => 2048,
        'temperature' => 0.7,
        'timeout' => 60,
        'provider_name' => $provider,
    ];
}

function makeSuccessResponse(string $content, int $latencyMs = 200): array
{
    return [
        'content' => $content,
        'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
        'latency_ms' => $latencyMs,
    ];
}

function makeErrorResponse(string $error, string $errorType, int $latencyMs): array
{
    return [
        'error' => $error,
        'error_type' => $errorType,
        'latency_ms' => $latencyMs,
    ];
}

function makeMessage(string $role, string $content): Message
{
    return new Message(
        role: $role,
        content: $content,
        timestamp: new DateTimeImmutable,
    );
}

function assertFallbackAttempt(
    array $attempt,
    string $provider,
    string $model,
    string $errorFragment,
    string $errorType,
    int $latencyMs,
): void {
    expect($attempt['provider'])->toBe($provider)
        ->and($attempt['model'])->toBe($model)
        ->and($attempt['error'])->toContain($errorFragment)
        ->and($attempt['error_type'])->toBe($errorType)
        ->and($attempt['latency_ms'])->toBe($latencyMs);
}

it('returns empty fallback_attempts on first model success', function (): void {
    $configResolver = Mockery::mock(ConfigResolver::class);
    $configResolver->shouldReceive('resolve')->with(1)->andReturn([
        makeConfig('openai', 'gpt-4o'),
    ]);

    $llmClient = Mockery::mock(LlmClient::class);
    $llmClient->shouldReceive('chat')->once()->andReturn(makeSuccessResponse('Hello!'));

    $runtime = makeRuntime($configResolver, $llmClient);
    $result = $runtime->run([makeMessage('user', 'Hi')], 1);

    expect($result['content'])->toBe('Hello!')
        ->and($result['meta']['fallback_attempts'])->toBeArray()->toBeEmpty()
        ->and($result['meta']['model'])->toBe('gpt-4o')
        ->and($result['meta']['provider_name'])->toBe('openai')
        ->and($result['meta']['llm']['provider'])->toBe('openai')
        ->and($result['meta']['llm']['model'])->toBe('gpt-4o');
});

it('collects fallback attempt entries on transient failures before success', function (): void {
    $configResolver = Mockery::mock(ConfigResolver::class);
    $configResolver->shouldReceive('resolve')->with(1)->andReturn([
        makeConfig('provider-a', 'model-a'),
        makeConfig('provider-b', 'model-b'),
        makeConfig('provider-c', 'model-c'),
    ]);

    $llmClient = Mockery::mock(LlmClient::class);
    // First call: server error (fallback-worthy)
    $llmClient->shouldReceive('chat')->once()->ordered()->andReturn(
        makeErrorResponse('HTTP 500: Internal Server Error', 'server_error', 150)
    );
    // Second call: rate limit (fallback-worthy)
    $llmClient->shouldReceive('chat')->once()->ordered()->andReturn(
        makeErrorResponse('HTTP 429: Too Many Requests', 'rate_limit', 50)
    );
    // Third call: success
    $llmClient->shouldReceive('chat')->once()->ordered()->andReturn(
        makeSuccessResponse('Finally worked!', 300)
    );

    $runtime = makeRuntime($configResolver, $llmClient);
    $result = $runtime->run([makeMessage('user', 'Hi')], 1);

    expect($result['content'])->toBe('Finally worked!')
        ->and($result['meta']['model'])->toBe('model-c')
        ->and($result['meta']['fallback_attempts'])->toHaveCount(2);

    assertFallbackAttempt($result['meta']['fallback_attempts'][0], 'provider-a', 'model-a', '500', 'server_error', 150);
    assertFallbackAttempt($result['meta']['fallback_attempts'][1], 'provider-b', 'model-b', '429', 'rate_limit', 50);
});

it('includes fallback attempts when all models fail', function (): void {
    $configResolver = Mockery::mock(ConfigResolver::class);
    $configResolver->shouldReceive('resolve')->with(1)->andReturn([
        makeConfig('prov-a', 'model-a'),
        makeConfig('prov-b', 'model-b'),
    ]);

    $llmClient = Mockery::mock(LlmClient::class);
    $llmClient->shouldReceive('chat')->once()->ordered()->andReturn(
        makeErrorResponse('HTTP 500: Server Error', 'server_error', 100)
    );
    $llmClient->shouldReceive('chat')->once()->ordered()->andReturn(
        makeErrorResponse('Connection refused', 'connection_error', 50)
    );

    $runtime = makeRuntime($configResolver, $llmClient);
    $result = $runtime->run([makeMessage('user', 'Hi')], 1);

    // Last failure is returned as the result
    expect($result['meta']['error'])->toContain('Connection refused')
        ->and($result['meta']['fallback_attempts'])->toHaveCount(2);

    // Both attempts recorded
    expect($result['meta']['fallback_attempts'][0]['provider'])->toBe('prov-a')
        ->and($result['meta']['fallback_attempts'][0]['error_type'])->toBe('server_error')
        ->and($result['meta']['fallback_attempts'][1]['provider'])->toBe('prov-b')
        ->and($result['meta']['fallback_attempts'][1]['error_type'])->toBe('connection_error');
});

it('does not fall back on client errors and still records empty attempts', function (): void {
    $configResolver = Mockery::mock(ConfigResolver::class);
    $configResolver->shouldReceive('resolve')->with(1)->andReturn([
        makeConfig('openai', 'gpt-4o'),
        makeConfig('anthropic', 'claude-3'),
    ]);

    $llmClient = Mockery::mock(LlmClient::class);
    // Client error (401) — should NOT trigger fallback
    $llmClient->shouldReceive('chat')->once()->andReturn(
        makeErrorResponse('HTTP 401: Unauthorized', 'client_error', 30)
    );

    $runtime = makeRuntime($configResolver, $llmClient);
    $result = $runtime->run([makeMessage('user', 'Hi')], 1);

    // Should stop at first model, no fallback
    expect($result['meta']['error'])->toContain('401')
        ->and($result['meta']['fallback_attempts'])->toBeEmpty();
});

it('records config_error in result without fallback since not transient', function (): void {
    $configResolver = Mockery::mock(ConfigResolver::class);
    $configResolver->shouldReceive('resolve')->with(1)->andReturn([
        makeConfig('broken', 'model-a', '', 'https://api.example.com/v1'),
        makeConfig('working', 'model-b'),
    ]);

    $llmClient = Mockery::mock(LlmClient::class);

    $runtime = makeRuntime($configResolver, $llmClient);
    $result = $runtime->run([makeMessage('user', 'Hi')], 1);

    // config_error is NOT in the shouldFallback transient list, so no fallback
    expect($result['meta']['error'])->toContain('API key is not configured')
        ->and($result['meta']['provider_name'])->toBe('broken')
        ->and($result['meta']['llm']['provider'])->toBe('broken')
        ->and($result['meta']['llm']['model'])->toBe('model-a')
        ->and($result['meta']['fallback_attempts'])->toBeEmpty();
});
