<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Capability extends Model
{
    /**
     * @var string
     */
    protected $table = 'base_authz_capabilities';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'domain',
        'resource',
        'action',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'base_authz_role_capabilities',
            'capability_id',
            'role_id'
        )->withTimestamps();
    }

    public function principalCapabilities(): HasMany
    {
        return $this->hasMany(PrincipalCapability::class, 'capability_id');
    }
}
