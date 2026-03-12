<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\AI\Exceptions;

use App\Base\Foundation\Exceptions\BlbIntegrationException;

final class ProviderDiscoveryException extends BlbIntegrationException
{
    public static function httpFailure(int $status): self
    {
        return new self('Model discovery failed: HTTP '.$status, context: ['status' => $status]);
    }
}
