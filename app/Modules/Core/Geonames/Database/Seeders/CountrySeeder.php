<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Geonames\Database\Seeders;

use App\Modules\Core\Geonames\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $url = "https://download.geonames.org/export/dump/countryInfo.txt";
        $downloadPath = storage_path("download/geonames");
        $filePath = $downloadPath . "/countryInfo.txt";

        // Create directory if it doesn't exist
        if (!File::exists($downloadPath)) {
            File::makeDirectory($downloadPath, 0755, true);
        }

        // Download file if it doesn't exist or is older than 7 days
        if (
            !File::exists($filePath) ||
            File::lastModified($filePath) < now()->subDays(7)->timestamp
        ) {
            $this->command->info("Downloading countryInfo.txt...");
            $response = Http::timeout(300)->get($url);

            if ($response->successful()) {
                File::put($filePath, $response->body());
                $this->command->info("Downloaded successfully.");
            } else {
                $this->command->error(
                    "Failed to download file: " . $response->status(),
                );
                return;
            }
        } else {
            $this->command->info("Using cached countryInfo.txt file.");
        }

        // Parse and insert data
        $this->command->info("Parsing countryInfo.txt...");
        $content = File::get($filePath);
        $lines = explode("\n", $content);

        $countries = [];
        $skipped = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, "#")) {
                $skipped++;
                continue;
            }

            // Parse tab-separated values
            // Columns: ISO, ISO3, ISO-Numeric, fips, Country, Capital, Area, Population, Continent, tld, CurrencyCode, CurrencyName, Phone, Postal Code Format, Postal Code Regex, Languages, geonameid, neighbours, EquivalentFipsCode
            $parts = explode("\t", $line);

            if (count($parts) < 17) {
                $skipped++;
                continue;
            }

            // Map columns (excluding fips, neighbours, EquivalentFipsCode)
            $countries[] = [
                "iso" => $parts[0] ?? null,
                "iso3" => $parts[1] ?? null,
                "iso_numeric" => $parts[2] ?? null,
                "country" => $parts[4] ?? null,
                "capital" => $parts[5] ?? null,
                "area" => !empty($parts[6]) ? (float) $parts[6] : null,
                "population" => !empty($parts[7]) ? (int) $parts[7] : null,
                "continent" => $parts[8] ?? null,
                "tld" => $parts[9] ?? null,
                "currency_code" => $parts[10] ?? null,
                "currency_name" => $parts[11] ?? null,
                "phone" => $parts[12] ?? null,
                "postal_code_format" => $parts[13] ?? null,
                "postal_code_regex" => $parts[14] ?? null,
                "languages" => $parts[15] ?? null,
                "geoname_id" => !empty($parts[16]) ? (int) $parts[16] : null,
                "created_at" => now(),
                "updated_at" => now(),
            ];
        }

        // Bulk insert in chunks
        $this->command->info(
            "Inserting " . count($countries) . " countries...",
        );
        $chunks = array_chunk($countries, 100);

        foreach ($chunks as $chunk) {
            DB::table("geonames_countries")->insertOrIgnore($chunk);
        }

        $this->command->info(
            "Seeded " .
                count($countries) .
                " countries. Skipped " .
                $skipped .
                " lines.",
        );
    }
}
