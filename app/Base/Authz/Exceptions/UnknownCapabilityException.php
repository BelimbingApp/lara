<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Exceptions;

use App\Base\Foundation\Enums\BlbErrorCode;
use App\Base\Foundation\Exceptions\BlbDataContractException;

final class UnknownCapabilityException extends BlbDataContractException
{
    public static function fromKey(string $capability): self
    {
        return new self(
            'Unknown capability ['.$capability.'].',
            BlbErrorCode::AUTHZ_UNKNOWN_CAPABILITY,
            ['capability' => $capability],
        );
    }
}
