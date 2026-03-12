<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Exceptions;

use App\Base\Foundation\Exceptions\BlbConfigurationException;

final class AuthzRoleCapabilitySeedingException extends BlbConfigurationException
{
    public static function missingRole(string $roleCode): self
    {
        return new self(
            'Missing role ['.$roleCode.'] before seeding role capabilities.',
            context: ['role_code' => $roleCode],
        );
    }
}
