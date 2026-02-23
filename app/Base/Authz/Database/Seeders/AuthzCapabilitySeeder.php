<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Database\Seeders;

use App\Base\Authz\Capability\CapabilityKey;
use App\Base\Authz\Models\Capability;
use Illuminate\Database\Seeder;

class AuthzCapabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var array<int, string> $capabilities */
        $capabilities = config('authz.capabilities', []);

        foreach ($capabilities as $capabilityKey) {
            $parts = CapabilityKey::parse($capabilityKey);

            Capability::query()->updateOrCreate(
                ['key' => $capabilityKey],
                [
                    'domain' => $parts['domain'],
                    'resource' => $parts['resource'],
                    'action' => $parts['action'],
                ]
            );
        }
    }
}
