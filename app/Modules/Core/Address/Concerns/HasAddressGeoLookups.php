<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Address\Concerns;

use App\Modules\Core\Geonames\Jobs\ImportPostcodes;
use App\Modules\Core\Geonames\Models\Admin1;
use App\Modules\Core\Geonames\Models\Postcode;

trait HasAddressGeoLookups
{
    private const POSTCODE_SEARCH_LIMIT = 10;

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
     * Load postcode options for a country (for editable combobox).
     *
     * Returns an array suitable for the x-ui.combobox component.
     * Limited to 1000 postcodes per country for performance.
     *
     * @param  string  $countryIso  Two-letter ISO country code
     * @return array<int, array{value: string, label: string}>
     */
    public function loadPostcodesForCountry(string $countryIso): array
    {
        $iso = strtoupper($countryIso);

        return Postcode::query()
            ->where('country_iso', $iso)
            ->select('postcode')
            ->distinct()
            ->orderBy('postcode')
            ->limit(1000)
            ->get()
            ->map(fn (Postcode $p) => [
                'value' => (string) $p->postcode,
                'label' => (string) $p->postcode,
            ])
            ->values()
            ->all();
    }

    /**
     * Search postcodes by query (for editable combobox with server-side search).
     *
     * Returns matching postcodes. No limit on total postcodes per country.
     *
     * @param  string  $countryIso  Two-letter ISO country code
     * @param  string  $query  Search query (empty returns first postcodes up to limit)
     * @return array<int, array{value: string, label: string}>
     */
    public function searchPostcodesInCountry(string $countryIso, string $query): array
    {
        $iso = strtoupper($countryIso);
        $q = trim($query);

        $query = Postcode::query()
            ->where('country_iso', $iso)
            ->select('postcode')
            ->distinct();

        if ($q !== '') {
            $pattern = str_replace(['%', '_'], ['\\%', '\\_'], $q);
            $query->where('postcode', 'ilike', $pattern.'%');
        }

        return $query
            ->orderBy('postcode')
            ->limit(self::POSTCODE_SEARCH_LIMIT)
            ->get()
            ->map(fn (Postcode $p) => [
                'value' => (string) $p->postcode,
                'label' => (string) $p->postcode,
            ])
            ->values()
            ->all();
    }

    /**
     * Look up a postcode and return the matching locality and admin1 code.
     *
     * @param  string  $countryIso  Two-letter ISO country code
     * @param  string  $postcode  Postal code to look up
     * @return array{locality: string, admin1_code: string|null}|null
     */
    public function lookupPostcode(string $countryIso, string $postcode): ?array
    {
        $result = $this->lookupLocalitiesByPostcode($countryIso, $postcode);

        if (! $result || empty($result['localities'])) {
            return null;
        }

        return [
            'locality' => $result['localities'][0]['value'],
            'admin1_code' => $result['admin1_code'],
        ];
    }

    /**
     * Look up a postcode and return all matching localities (for editable combobox).
     *
     * @param  string  $countryIso  Two-letter ISO country code
     * @param  string  $postcode  Postal code to look up
     * @return array{localities: array<int, array{value: string, label: string}>, admin1_code: string|null}|null
     */
    public function lookupLocalitiesByPostcode(string $countryIso, string $postcode): ?array
    {
        $iso = strtoupper($countryIso);

        $results = Postcode::query()
            ->where('country_iso', $iso)
            ->where('postcode', $postcode)
            ->get(['place_name', 'admin1_code']);

        if ($results->isEmpty()) {
            return null;
        }

        $seen = [];
        $localities = [];

        foreach ($results as $row) {
            $name = $row->place_name;

            if ($name === null || $name === '' || isset($seen[$name])) {
                continue;
            }

            $seen[$name] = true;
            $localities[] = ['value' => $name, 'label' => $name];
        }

        if (empty($localities)) {
            return null;
        }

        $first = $results->first();
        $admin1Code = $first->admin1_code
            ? $iso.'.'.$first->admin1_code
            : null;

        return [
            'localities' => $localities,
            'admin1_code' => $admin1Code,
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
