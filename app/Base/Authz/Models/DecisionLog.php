<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Models;

use Illuminate\Database\Eloquent\Model;

class DecisionLog extends Model
{
    /**
     * @var string
     */
    protected $table = 'base_authz_decision_logs';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'actor_type',
        'actor_id',
        'acting_for_user_id',
        'capability',
        'resource_type',
        'resource_id',
        'allowed',
        'reason_code',
        'applied_policies',
        'context',
        'correlation_id',
        'occurred_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'allowed' => 'boolean',
        'applied_policies' => 'array',
        'context' => 'array',
        'occurred_at' => 'datetime',
    ];
}
