<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrincipalRole extends Model
{
    /**
     * @var string
     */
    protected $table = 'base_authz_principal_roles';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'principal_type',
        'principal_id',
        'role_id',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
