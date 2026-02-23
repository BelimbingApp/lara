<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\DTO;

final readonly class Actor
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public string $type,
        public int $id,
        public ?int $companyId,
        public ?int $actingForUserId = null,
        public array $attributes = [],
    ) {}

    public function isHumanUser(): bool
    {
        return $this->type === 'human_user';
    }

    public function isPersonalAgent(): bool
    {
        return $this->type === 'personal_agent';
    }
}
