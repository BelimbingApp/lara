<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Policies;

use App\Base\Authz\Capability\CapabilityRegistry;
use App\Base\Authz\Contracts\AuthorizationPolicy;
use App\Base\Authz\DTO\Actor;
use App\Base\Authz\DTO\AuthorizationDecision;
use App\Base\Authz\DTO\ResourceContext;
use App\Base\Authz\Enums\AuthorizationReasonCode;

/**
 * Rejects capabilities not registered in the catalog.
 *
 * Denies unknown capabilities. Abstains for known ones.
 */
class KnownCapabilityPolicy implements AuthorizationPolicy
{
    public function __construct(private readonly CapabilityRegistry $registry) {}

    public function key(): string
    {
        return 'capability_registry';
    }

    public function evaluate(
        Actor $actor,
        string $capability,
        ?ResourceContext $resource,
        array $context
    ): ?AuthorizationDecision {
        if ($this->registry->has($capability)) {
            return null;
        }

        return AuthorizationDecision::deny(AuthorizationReasonCode::DENIED_UNKNOWN_CAPABILITY);
    }
}
