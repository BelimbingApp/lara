<?php

use App\Base\AI\Services\GithubCopilotAuthService;
use App\Modules\Core\AI\Services\RuntimeCredentialResolver;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

const RCR_PROXY_BASE_URL = 'http://localhost:1337/v1';
const RCR_PROXY_PROVIDER = 'copilot-proxy';

function makeResolver(): RuntimeCredentialResolver
{
    return new RuntimeCredentialResolver(
        Mockery::mock(GithubCopilotAuthService::class),
    );
}

test('copilot-proxy returns connection error when server is unreachable', function (): void {
    Http::fake([
        'localhost:1337/v1/models' => Http::response(status: 500),
    ]);

    // ConnectionException isn't easily faked via Http::fake, so test the HTTP error path
    $result = makeResolver()->resolve([
        'api_key' => 'not-required',
        'base_url' => RCR_PROXY_BASE_URL,
        'provider_name' => RCR_PROXY_PROVIDER,
    ]);

    expect($result)
        ->toHaveKey('error')
        ->toHaveKey('error_type', 'connection_error')
        ->and($result['error'])->toContain('Copilot Proxy')
        ->and($result['error'])->toContain('500');
});

test('copilot-proxy passes when server is reachable', function (): void {
    Http::fake([
        'localhost:1337/v1/models' => Http::response(['data' => []], 200),
    ]);

    $result = makeResolver()->resolve([
        'api_key' => 'not-required',
        'base_url' => RCR_PROXY_BASE_URL,
        'provider_name' => RCR_PROXY_PROVIDER,
    ]);

    expect($result)
        ->toHaveKey('api_key', 'not-required')
        ->toHaveKey('base_url', RCR_PROXY_BASE_URL)
        ->not->toHaveKey('error');
});

test('non-proxy providers skip connectivity check', function (): void {
    Http::fake();

    $result = makeResolver()->resolve([
        'api_key' => 'sk-test',
        'base_url' => 'https://api.openai.com/v1',
        'provider_name' => 'openai',
    ]);

    expect($result)
        ->toHaveKey('api_key', 'sk-test')
        ->toHaveKey('base_url', 'https://api.openai.com/v1')
        ->not->toHaveKey('error');

    Http::assertNothingSent();
});

test('copilot-proxy connection error message mentions VS Code extension', function (): void {
    Http::fake([
        'localhost:1337/v1/models' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'),
    ]);

    $result = makeResolver()->resolve([
        'api_key' => 'not-required',
        'base_url' => RCR_PROXY_BASE_URL,
        'provider_name' => RCR_PROXY_PROVIDER,
    ]);

    expect($result)
        ->toHaveKey('error_type', 'connection_error')
        ->and($result['error'])->toContain('Could not connect')
        ->and($result['error'])->toContain('VS Code');
});
