<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Exceptions;

use App\Base\Authz\DTO\AuthorizationDecision;
use App\Base\Foundation\Enums\BlbErrorCode;
use App\Base\Foundation\Exceptions\BlbException;

final class AuthorizationDeniedException extends BlbException
{
    public function __construct(public readonly AuthorizationDecision $decision)
    {
        parent::__construct(
            'Authorization denied: '.$decision->reasonCode->value,
            BlbErrorCode::AUTHZ_DENIED,
            [
                'authorization_reason' => $decision->reasonCode->value,
                'applied_policies' => $decision->appliedPolicies,
                'audit_meta' => $decision->auditMeta,
            ],
        );
    }
}
