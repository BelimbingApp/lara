<?php

use App\Base\AI\Exceptions\ProviderDiscoveryException;
use App\Base\AI\Services\ProviderDiscoveryService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('throws a dedicated exception when provider model discovery fails', function (): void {
    Http::fake([
        'https://example.test/models' => Http::response([], 502),
    ]);

    $service = new ProviderDiscoveryService;

    expect(fn () => $service->discoverModels('https://example.test'))
        ->toThrow(function (ProviderDiscoveryException $exception): void {
            expect($exception->getMessage())->toBe('Model discovery failed: HTTP 502')
                ->and($exception->context['status'] ?? null)->toBe(502);
        });
});
