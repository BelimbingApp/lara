<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Exceptions;

use RuntimeException;

final class UnknownCapabilityException extends RuntimeException
{
    public static function fromKey(string $capability): self
    {
        return new self("Unknown capability [$capability].");
    }
}
