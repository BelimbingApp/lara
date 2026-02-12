<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Geonames\Database\Seeders;

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
        $filePath = $this->downloadFile();

        if (! $filePath) {
            return;
        }

        $records = $this->parseFile($filePath);

        if (empty($records)) {
            $this->command?->info('No admin1 records to import.');

            return;
        }

        $this->command?->info('Upserting '.count($records).' admin1 records...');

        $updateColumns = array_values(array_diff(
            array_keys($records[0]),
            ['code', 'name', 'created_at'],
        ));

        foreach (array_chunk($records, 100) as $chunk) {
            DB::table('geonames_admin1')->upsert(
                $chunk,
                ['code'],
                $updateColumns,
            );
        }

        $this->command?->info('Imported '.count($records).' admin1 records.');
    }

    /**
     * Download the admin1 codes file from geonames.org.
     *
     * Uses a cached copy when available, and re-downloads when the file is missing
     * or older than 7 days.
     */
    protected function downloadFile(): ?string
    {
        $url = 'https://download.geonames.org/export/dump/admin1CodesASCII.txt';
        $downloadPath = storage_path('download/geonames');
        $filePath = $downloadPath.'/admin1CodesASCII.txt';

        if (! File::exists($downloadPath)) {
            File::makeDirectory($downloadPath, 0755, true);
        }

        if (
            ! File::exists($filePath)
            || File::lastModified($filePath) < now()->subDays(7)->timestamp
        ) {
            $this->command?->info('Downloading admin1CodesASCII.txt...');
            $response = Http::timeout(300)->get($url);

            if (! $response->successful()) {
                $this->command?->error('Failed to download file: '.$response->status());

                return null;
            }

            File::put($filePath, $response->body());
            $this->command?->info('Downloaded successfully.');
        } else {
            $this->command?->info('Using cached admin1CodesASCII.txt file.');
        }

        return $filePath;
    }

    /**
     * Parse the admin1 codes file and return importable records.
     *
     * @param  string  $filePath  Path to the admin1CodesASCII.txt file
     * @return array<int, array<string, mixed>>
     */
    protected function parseFile(string $filePath): array
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $records = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            $parts = explode("\t", $line);

            if (count($parts) < 4) {
                continue;
            }

            $code = $parts[0];

            $records[] = [
                'code' => $code,
                'name' => $parts[1] ?? null,
                'alt_name' => $parts[2] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $records;
    }
}
