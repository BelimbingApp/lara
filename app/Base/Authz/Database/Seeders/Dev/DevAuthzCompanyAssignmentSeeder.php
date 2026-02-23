<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Database\Seeders\Dev;

use App\Base\Authz\Models\PrincipalRole;
use App\Base\Authz\Models\Role;
use App\Base\Database\Seeders\DevSeeder;
use App\Modules\Core\User\Models\User;

class DevAuthzCompanyAssignmentSeeder extends DevSeeder
{
    /**
     * Seed the database.
     *
     * Assigns the first user in each company to the core_admin role for local testing.
     */
    protected function seed(): void
    {
        $coreAdminRole = Role::query()
            ->whereNull('company_id')
            ->where('code', 'core_admin')
            ->first();

        if ($coreAdminRole === null) {
            return;
        }

        $users = User::query()
            ->whereNotNull('company_id')
            ->orderBy('id')
            ->get()
            ->groupBy('company_id')
            ->map(fn ($companyUsers) => $companyUsers->first())
            ->filter();

        foreach ($users as $user) {
            PrincipalRole::query()->firstOrCreate([
                'company_id' => $user->company_id,
                'principal_type' => 'human_user',
                'principal_id' => $user->id,
                'role_id' => $coreAdminRole->id,
            ]);
        }
    }
}
