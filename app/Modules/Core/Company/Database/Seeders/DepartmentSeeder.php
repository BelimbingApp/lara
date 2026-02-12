<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Company\Database\Seeders;

use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\Company\Models\Department;
use App\Modules\Core\Company\Models\DepartmentType;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // For development: Seed departments for all existing companies
        Company::query()->chunk(100, function ($companies): void {
            foreach ($companies as $company) {
                $this->seedDepartmentsForCompany($company);
            }
        });
    }

    /**
     * Seed standard departments for a company based on active department types.
     *
     * @param  Company  $company  The company to seed departments for
     */
    protected function seedDepartmentsForCompany(Company $company): void
    {
        // Get all active department types
        $departmentTypes = DepartmentType::query()->active()->get();

        foreach ($departmentTypes as $type) {
            Department::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'department_type_id' => $type->id,
                ],
                [
                    'status' => 'active',
                ]
            );
        }
    }
}
