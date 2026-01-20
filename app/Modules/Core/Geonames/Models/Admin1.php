<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Geonames\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admin1 extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "geonames_admin1";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ["code", "name", "alt_name", "geoname_id"];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "geoname_id" => "integer",
            "created_at" => "datetime",
            "updated_at" => "datetime",
        ];
    }

    /**
     * Extract the country code from the code field.
     *
     * @return string|null
     */
    public function getCountryCodeAttribute(): ?string
    {
        if (!$this->code) {
            return null;
        }

        $parts = explode(".", $this->code);
        return $parts[0] ?? null;
    }

    /**
     * Get the country that this admin1 division belongs to.
     */
    public function country(): BelongsTo
    {
        $countryCode = $this->country_code;
        if (!$countryCode) {
            // Return a query that will never match if country_code is null
            return $this->belongsTo(Country::class)->whereRaw("1 = 0");
        }

        return $this->belongsTo(Country::class, "iso", "iso")->where(
            "geonames_countries.iso",
            "=",
            $countryCode,
        );
    }

    /**
     * Get the country model for this admin1 division.
     *
     * @return Country|null
     */
    public function getCountry(): ?Country
    {
        $countryCode = $this->country_code;
        if (!$countryCode) {
            return null;
        }

        return Country::where("iso", $countryCode)->first();
    }
}
