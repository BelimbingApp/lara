<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Exceptions;

use App\Base\Authz\DTO\AuthorizationDecision;
use RuntimeException;

final class AuthorizationDeniedException extends RuntimeException
{
    public function __construct(public readonly AuthorizationDecision $decision)
    {
        parent::__construct('Authorization denied: '.$decision->reasonCode->value);
    }
}
