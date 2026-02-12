<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Geonames\Jobs;

use App\Modules\Core\Geonames\Database\Seeders\PostcodeSeeder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportPostcodes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600;

    /**
     * Create a new job instance.
     *
     * @param  array<int, string>  $countryCodes  ISO country codes to import
     */
    public function __construct(
        public array $countryCodes,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(PostcodeSeeder::class)->run($this->countryCodes);
    }
}
