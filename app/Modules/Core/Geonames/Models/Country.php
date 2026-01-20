<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Geonames\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "geonames_countries";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "iso",
        "iso3",
        "iso_numeric",
        "country",
        "capital",
        "area",
        "population",
        "continent",
        "tld",
        "currency_code",
        "currency_name",
        "phone",
        "postal_code_format",
        "postal_code_regex",
        "languages",
        "geoname_id",
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "area" => "float",
            "population" => "integer",
            "geoname_id" => "integer",
            "created_at" => "datetime",
            "updated_at" => "datetime",
        ];
    }

    /**
     * Get the admin1 divisions for this country.
     * Note: This relationship uses a custom where clause since there's no direct foreign key.
     */
    public function admin1s()
    {
        return Admin1::whereRaw("SUBSTRING(code, 1, LENGTH(?)) = ?", [
            $this->iso,
            $this->iso,
        ]);
    }
}
