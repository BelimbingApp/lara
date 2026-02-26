<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Models;

use App\Modules\Core\Company\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /**
     * @var string
     */
    protected $table = 'base_authz_roles';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'is_system',
        'grant_all',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_system' => 'boolean',
        'grant_all' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function principalRoles(): HasMany
    {
        return $this->hasMany(PrincipalRole::class, 'role_id');
    }

    public function capabilities(): HasMany
    {
        return $this->hasMany(RoleCapability::class, 'role_id');
    }

    public function principalCount(): int
    {
        return $this->principalRoles()->count();
    }
}
