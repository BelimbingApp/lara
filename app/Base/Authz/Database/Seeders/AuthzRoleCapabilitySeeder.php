<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Database\Seeders;

use App\Base\Authz\Capability\CapabilityRegistry;
use App\Base\Authz\Models\Capability;
use App\Base\Authz\Models\Role;
use Illuminate\Database\Seeder;
use RuntimeException;

class AuthzRoleCapabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var array<string, array{name: string, description: string|null, capabilities: array<int, string>}> $roles */
        $roles = config('authz.roles', []);

        /** @var CapabilityRegistry $capabilityRegistry */
        $capabilityRegistry = app(CapabilityRegistry::class);

        foreach ($roles as $roleCode => $roleConfig) {
            $role = Role::query()
                ->whereNull('company_id')
                ->where('code', $roleCode)
                ->first();

            if ($role === null) {
                throw new RuntimeException("Missing role [$roleCode] before seeding role capabilities.");
            }

            $capabilityIds = [];

            foreach ($roleConfig['capabilities'] as $capabilityKey) {
                $capabilityRegistry->assertKnown($capabilityKey);

                $capability = Capability::query()->where('key', $capabilityKey)->first();

                if ($capability === null) {
                    throw new RuntimeException("Missing capability [$capabilityKey] before role mapping.");
                }

                $capabilityIds[] = $capability->id;
            }

            // Keep role templates deterministic across repeated seeding.
            $role->capabilities()->sync($capabilityIds);
        }
    }
}
