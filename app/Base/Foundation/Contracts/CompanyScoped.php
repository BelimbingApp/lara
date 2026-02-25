<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Foundation\Contracts;

interface CompanyScoped
{
    /**
     * Get the company ID the entity belongs to.
     */
    public function getCompanyId(): ?int;
}
