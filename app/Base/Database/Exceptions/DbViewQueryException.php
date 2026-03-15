<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Database\Exceptions;

use App\Base\Foundation\Enums\BlbErrorCode;
use App\Base\Foundation\Exceptions\BlbDataContractException;

/**
 * Thrown when a DB View query fails validation or execution.
 */
final class DbViewQueryException extends BlbDataContractException
{
    /**
     * Create an exception for an invalid query.
     *
     * @param  string  $reason  Human-readable explanation of the failure
     */
    public static function invalidQuery(string $reason): self
    {
        return new self(
            'Invalid DB View query: '.$reason,
            BlbErrorCode::DB_VIEW_QUERY_INVALID,
            ['reason' => $reason],
        );
    }

    /**
     * Create an exception for a query that failed during execution.
     *
     * @param  string  $message  The database error message
     * @param  \Throwable  $previous  The original database exception
     */
    public static function executionFailed(string $message, \Throwable $previous): self
    {
        return new self(
            'DB View query execution failed: '.$message,
            BlbErrorCode::DB_VIEW_QUERY_EXECUTION_FAILED,
            ['error' => $message],
            previous: $previous,
        );
    }
}
