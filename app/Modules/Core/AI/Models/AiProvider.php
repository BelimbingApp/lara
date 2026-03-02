<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Models;

use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\Employee\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiProvider extends Model
{
    /**
     * @var string
     */
    protected $table = 'ai_providers';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'display_name',
        'base_url',
        'api_key',
        'is_active',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the company that owns this provider.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee who created this provider.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    /**
     * Get the models registered under this provider.
     */
    public function models(): HasMany
    {
        return $this->hasMany(AiProviderModel::class, 'ai_provider_id');
    }

    /**
     * Scope to active providers only.
     */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope to providers belonging to a specific company.
     *
     * @param  int  $companyId  Company ID
     */
    public function scopeForCompany($query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }
}
