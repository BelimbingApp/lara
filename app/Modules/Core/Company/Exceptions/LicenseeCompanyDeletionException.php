<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Company\Exceptions;

use App\Base\Foundation\Enums\BlbErrorCode;
use App\Base\Foundation\Exceptions\BlbInvariantViolationException;

/**
 * Thrown when an attempt is made to delete the licensee company (id=1).
 */
final class LicenseeCompanyDeletionException extends BlbInvariantViolationException
{
    public function __construct()
    {
        parent::__construct(
            'The licensee company (id=1) cannot be deleted.',
            BlbErrorCode::LICENSEE_COMPANY_DELETION_FORBIDDEN,
            ['company_id' => 1],
        );
    }
}
