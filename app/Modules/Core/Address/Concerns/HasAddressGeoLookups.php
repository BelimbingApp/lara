<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Address\Concerns;

use App\Modules\Core\Geonames\Jobs\ImportPostcodes;
use App\Modules\Core\Geonames\Models\Admin1;
use App\Modules\Core\Geonames\Models\Postcode;

trait HasAddressGeoLookups
{
    /**
     * Load admin1 (state/province) options for a country.
     *
     * Returns an array suitable for the x-ui.combobox component.
     *
     * @param  string  $countryIso  Two-letter ISO country code
     * @return array<int, array{value: string, label: string}>
     */
    public function loadAdmin1ForCountry(string $countryIso): array
    {
        $iso = strtoupper($countryIso);

        $options = Admin1::query()
            ->forCountry($iso)
            ->orderBy('name')
            ->get(['code', 'name'])
            ->map(fn (Admin1 $a) => ['value' => $a->code, 'label' => $a->name])
            ->values()
            ->all();

        if (! empty($options)) {
            return $options;
        }

        // Fallback when Admin1 seed data is missing: derive options from imported postcodes.
        return Postcode::query()
            ->where('country_iso', $iso)
            ->whereNotNull('admin1_code')
            ->select('admin1_code')
            ->distinct()
            ->orderBy('admin1_code')
            ->get()
            ->map(function (Postcode $postcode) use ($iso): array {
                $code = (string) $postcode->admin1_code;

                return [
                    'value' => $iso.'.'.$code,
                    'label' => $code,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Look up a postcode and return the matching locality and admin1 code.
     *
     * Dispatches a background import job when postcode data is not yet
     * available for the given country so future lookups will succeed.
     *
     * @param  string  $countryIso  Two-letter ISO country code
     * @param  string  $postcode  Postal code to look up
     * @return array{locality: string, admin1_code: string|null}|null
     */
    public function lookupPostcode(string $countryIso, string $postcode): ?array
    {
        $iso = strtoupper($countryIso);

        $result = Postcode::query()
            ->where('country_iso', $iso)
            ->where('postcode', $postcode)
            ->first(['place_name', 'admin1_code']);

        if (! $result) {
            $this->ensurePostcodesImported($iso);

            return null;
        }

        return [
            'locality' => $result->place_name,
            'admin1_code' => $result->admin1_code
                ? $iso.'.'.$result->admin1_code
                : null,
        ];
    }

    /**
     * Dispatch a postcode import job if data is missing for the country.
     *
     * @param  string  $countryIso  Two-letter ISO country code (uppercase)
     */
    protected function ensurePostcodesImported(string $countryIso): void
    {
        if (Postcode::query()->where('country_iso', $countryIso)->exists()) {
            return;
        }

        ImportPostcodes::dispatch([$countryIso])
            ->onQueue(ImportPostcodes::QUEUE);
        ImportPostcodes::runWorkerOnce();

        if (Postcode::query()->where('country_iso', $countryIso)->exists()) {
            return;
        }

        // Fallback path for environments where queue worker-once does not execute.
        ImportPostcodes::dispatchSync([$countryIso]);
    }
}
