<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Address\Models;

use App\Modules\Core\Geonames\Models\Admin1;
use App\Modules\Core\Geonames\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'addresses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'label',
        'phone',
        'line1',
        'line2',
        'line3',
        'locality',
        'postcode',
        'country_iso',
        'admin1_code',
        'raw_input',
        'source',
        'source_ref',
        'parser_version',
        'parse_confidence',
        'parsed_at',
        'normalized_at',
        'normalization_notes',
        'verification_status',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parse_confidence' => 'decimal:4',
            'parsed_at' => 'datetime',
            'normalized_at' => 'datetime',
            'normalization_notes' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the Geonames country referenced by this address.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_iso', 'iso');
    }

    /**
     * Get the Geonames admin1 referenced by this address.
     */
    public function admin1(): BelongsTo
    {
        return $this->belongsTo(Admin1::class, 'admin1_code', 'code');
    }
}
