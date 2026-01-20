<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Geonames\Database\Seeders;

use App\Modules\Core\Geonames\Models\Admin1;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class Admin1Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $url = "https://download.geonames.org/export/dump/admin1CodesASCII.txt";
        $downloadPath = storage_path("download/geonames");
        $filePath = $downloadPath . "/admin1CodesASCII.txt";

        // Create directory if it doesn't exist
        if (!File::exists($downloadPath)) {
            File::makeDirectory($downloadPath, 0755, true);
        }

        // Download file if it doesn't exist or is older than 7 days
        if (
            !File::exists($filePath) ||
            File::lastModified($filePath) < now()->subDays(7)->timestamp
        ) {
            $this->command->info("Downloading admin1CodesASCII.txt...");
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
            $this->command->info("Using cached admin1CodesASCII.txt file.");
        }

        // Parse and insert data
        $this->command->info("Parsing admin1CodesASCII.txt...");
        $content = File::get($filePath);
        $lines = explode("\n", $content);

        $admin1s = [];
        $skipped = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines
            if (empty($line)) {
                $skipped++;
                continue;
            }

            // Parse tab-separated values
            // Format: code<TAB>name<TAB>alt_name<TAB>geoname_id
            $parts = explode("\t", $line);

            if (count($parts) < 4) {
                $skipped++;
                continue;
            }

            $admin1s[] = [
                "code" => $parts[0] ?? null,
                "name" => $parts[1] ?? null,
                "alt_name" => $parts[2] ?? null,
                "geoname_id" => !empty($parts[3]) ? (int) $parts[3] : null,
                "created_at" => now(),
                "updated_at" => now(),
            ];
        }

        // Bulk insert in chunks
        $this->command->info(
            "Inserting " . count($admin1s) . " admin1 records...",
        );
        $chunks = array_chunk($admin1s, 100);

        foreach ($chunks as $chunk) {
            DB::table("geonames_admin1")->insertOrIgnore($chunk);
        }

        $this->command->info(
            "Seeded " .
                count($admin1s) .
                " admin1 records. Skipped " .
                $skipped .
                " lines.",
        );
    }
}
