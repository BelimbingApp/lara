<?php

use App\Base\Foundation\Enums\BlbErrorCode;
use App\Base\Foundation\Exceptions\BlbConfigurationException;
use App\Base\Foundation\Exceptions\BlbDataContractException;
use App\Base\Foundation\Exceptions\BlbIntegrationException;
use App\Base\Foundation\Exceptions\BlbInvariantViolationException;

it('stores reason code and context on BLB configuration exceptions', function (): void {
    $exception = new BlbConfigurationException(
        'Invalid config',
        BlbErrorCode::LARA_PROMPT_RESOURCE_MISSING,
        ['path' => '/tmp/missing.md']
    );

    expect($exception->reasonCode)->toBe(BlbErrorCode::LARA_PROMPT_RESOURCE_MISSING)
        ->and($exception->context)->toBe(['path' => '/tmp/missing.md']);
});

it('uses default reason codes for specialized BLB exceptions', function (): void {
    $configuration = new BlbConfigurationException('Config failure');
    $invariant = new BlbInvariantViolationException('Invariant failure');
    $dataContract = new BlbDataContractException('Data contract failure');
    $integration = new BlbIntegrationException('Integration failure');

    expect($configuration->reasonCode)->toBe(BlbErrorCode::BLB_CONFIGURATION)
        ->and($invariant->reasonCode)->toBe(BlbErrorCode::BLB_INVARIANT_VIOLATION)
        ->and($dataContract->reasonCode)->toBe(BlbErrorCode::BLB_DATA_CONTRACT)
        ->and($integration->reasonCode)->toBe(BlbErrorCode::BLB_INTEGRATION);
});
