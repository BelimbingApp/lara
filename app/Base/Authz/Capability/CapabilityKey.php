<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Capability;

use InvalidArgumentException;

final class CapabilityKey
{
    /**
     * Capability key pattern: <domain>.<resource>.<action>.
     */
    private const PATTERN = '/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/';

    /**
     * Determine whether a capability key uses the BLB grammar.
     */
    public static function isValid(string $key): bool
    {
        return preg_match(self::PATTERN, $key) === 1;
    }

    /**
     * Parse a capability key into [domain, resource, action].
     *
     * @return array{domain: string, resource: string, action: string}
     */
    public static function parse(string $key): array
    {
        if (! self::isValid($key)) {
            throw new InvalidArgumentException("Invalid capability key [$key].");
        }

        [$domain, $resource, $action] = explode('.', $key, 3);

        return [
            'domain' => $domain,
            'resource' => $resource,
            'action' => $action,
        ];
    }

    /**
     * Build and validate a capability key from parts.
     */
    public static function fromParts(string $domain, string $resource, string $action): string
    {
        $key = strtolower($domain.'.'.$resource.'.'.$action);

        if (! self::isValid($key)) {
            throw new InvalidArgumentException("Invalid capability key [$key].");
        }

        return $key;
    }
}
