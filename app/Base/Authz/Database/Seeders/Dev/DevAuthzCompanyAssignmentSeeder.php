<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Base\Authz\Database\Seeders\Dev;

use App\Base\Authz\Enums\PrincipalType;
use App\Base\Authz\Models\PrincipalRole;
use App\Base\Authz\Models\Role;
use App\Base\Database\Seeders\DevSeeder;
use App\Modules\Core\User\Models\User;

class DevAuthzCompanyAssignmentSeeder extends DevSeeder
{
    /**
     * Seed the database.
     *
     * 1. Grants the dev admin user (DEV_ADMIN_EMAIL) all system roles for full access.
     * 2. Assigns the first user in each remaining company to core_admin for basic testing.
     */
    protected function seed(): void
    {
        $systemRoles = Role::query()
            ->whereNull('company_id')
            ->where('is_system', true)
            ->get();

        if ($systemRoles->isEmpty()) {
            return;
        }

        $this->grantDevAdminFullAccess($systemRoles);
        $this->assignCoreAdminPerCompany($systemRoles);
    }

    /**
     * Grant all system roles to the dev admin user identified by DEV_ADMIN_EMAIL.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Role>  $systemRoles
     */
    private function grantDevAdminFullAccess($systemRoles): void
    {
        $adminEmail = env('DEV_ADMIN_EMAIL', 'admin@example.com');

        $adminUser = User::query()
            ->where('email', $adminEmail)
            ->whereNotNull('company_id')
            ->first();

        if ($adminUser === null) {
            return;
        }

        foreach ($systemRoles as $role) {
            PrincipalRole::query()->firstOrCreate([
                'company_id' => $adminUser->company_id,
                'principal_type' => PrincipalType::HUMAN_USER->value,
                'principal_id' => $adminUser->id,
                'role_id' => $role->id,
            ]);
        }
    }

    /**
     * Assign core_admin to the first user in each company (excluding the dev admin's company).
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Role>  $systemRoles
     */
    private function assignCoreAdminPerCompany($systemRoles): void
    {
        $coreAdminRole = $systemRoles->firstWhere('code', 'core_admin');

        if ($coreAdminRole === null) {
            return;
        }

        $adminEmail = env('DEV_ADMIN_EMAIL', 'admin@example.com');

        $users = User::query()
            ->whereNotNull('company_id')
            ->where('email', '!=', $adminEmail)
            ->orderBy('id')
            ->get()
            ->groupBy('company_id')
            ->map(fn ($companyUsers) => $companyUsers->first())
            ->filter();

        foreach ($users as $user) {
            PrincipalRole::query()->firstOrCreate([
                'company_id' => $user->company_id,
                'principal_type' => PrincipalType::HUMAN_USER->value,
                'principal_id' => $user->id,
                'role_id' => $coreAdminRole->id,
            ]);
        }
    }
}
