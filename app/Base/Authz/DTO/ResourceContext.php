<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\DTO;

final readonly class ResourceContext
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public string $type,
        public int|string|null $id,
        public ?int $companyId = null,
        public array $attributes = [],
    ) {}
}
