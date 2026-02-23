<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Database\Seeders;

use App\Base\Authz\Models\Role;
use Illuminate\Database\Seeder;

class AuthzRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var array<string, array{name: string, description: string|null, capabilities: array<int, string>}> $roles */
        $roles = config('authz.roles', []);

        foreach ($roles as $code => $role) {
            Role::query()->updateOrCreate(
                [
                    'company_id' => null,
                    'code' => $code,
                ],
                [
                    'name' => $role['name'],
                    'description' => $role['description'] ?? null,
                    'is_system' => true,
                ]
            );
        }
    }
}
