<?php

use App\Base\Authz\Contracts\AuthorizationService;
use App\Base\Authz\DTO\Actor;
use App\Base\Authz\DTO\ResourceContext;
use App\Base\Authz\Enums\AuthorizationReasonCode;
use App\Base\Authz\Exceptions\AuthorizationDeniedException;

it('denies when actor context is invalid', function (): void {
    $service = app(AuthorizationService::class);

    $decision = $service->can(new Actor('human_user', 0, null), 'core.user.view');

    expect($decision->allowed)->toBeFalse();
    expect($decision->reasonCode)->toBe(AuthorizationReasonCode::DENIED_INVALID_ACTOR_CONTEXT);
});

it('denies when resource company is outside actor scope', function (): void {
    $service = app(AuthorizationService::class);

    $decision = $service->can(
        new Actor('human_user', 888, 10),
        'core.user.view',
        new ResourceContext('users', 1, 20)
    );

    expect($decision->allowed)->toBeFalse();
    expect($decision->reasonCode)->toBe(AuthorizationReasonCode::DENIED_COMPANY_SCOPE);
});

it('denies unknown capability and authorize throws', function (): void {
    $service = app(AuthorizationService::class);

    $decision = $service->can(new Actor('human_user', 999, 10), 'core.user.manage');

    expect($decision->allowed)->toBeFalse();
    expect($decision->reasonCode)->toBe(AuthorizationReasonCode::DENIED_UNKNOWN_CAPABILITY);

    expect(fn () => $service->authorize(new Actor('human_user', 999, 10), 'core.user.manage'))
        ->toThrow(AuthorizationDeniedException::class);
});
