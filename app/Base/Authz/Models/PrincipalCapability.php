<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Models;

use Illuminate\Database\Eloquent\Model;

class PrincipalCapability extends Model
{
    /**
     * @var string
     */
    protected $table = 'base_authz_principal_capabilities';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'principal_type',
        'principal_id',
        'capability_key',
        'is_allowed',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_allowed' => 'boolean',
    ];
}
