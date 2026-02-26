<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\User\Database\Seeders\Dev;

use App\Base\Database\Seeders\DevSeeder;
use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\Employee\Models\Employee;
use App\Modules\Core\User\Models\User;

class DevUserSeeder extends DevSeeder
{
    protected array $dependencies = [
        \App\Modules\Core\Company\Database\Seeders\Dev\DevCompanyAddressSeeder::class,
        \App\Modules\Core\Employee\Database\Seeders\Dev\DevEmployeeSeeder::class,
    ];

    /**
     * Creates realistic dev users across seeded companies.
     * Each user gets password 'password' for easy local login.
     * Idempotent via firstOrCreate on email.
     * Assigns employee_id to admin user by DEV_ADMIN_EMAIL.
     */
    protected function seed(): void
    {
        $companies = Company::query()->orderBy('id')->get();

        if ($companies->isEmpty()) {
            return;
        }

        foreach ($this->users() as $definition) {
            $company = $companies->firstWhere('name', $definition['company']);

            if ($company === null) {
                continue;
            }

            User::query()->firstOrCreate(
                ['email' => $definition['email']],
                [
                    'company_id' => $company->id,
                    'name' => $definition['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->assignAdminEmployeeId();
    }

    /**
     * Assign employee_id to admin user by DEV_ADMIN_EMAIL. Creates admin user if missing.
     */
    protected function assignAdminEmployeeId(): void
    {
        $name = env('DEV_ADMIN_NAME', 'Administrator');
        $email = env('DEV_ADMIN_EMAIL', 'admin@example.com');
        $password = env('DEV_ADMIN_PASSWORD', 'password');

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'company_id' => Company::LICENSEE_ID,
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        $employee = Employee::query()
            ->where('email', $email)
            ->where('company_id', Company::LICENSEE_ID)
            ->first();

        if ($employee) {
            $user->update(['employee_id' => $employee->id]);
        }
    }

    /**
     * Dev user definitions mapped to company names from DevCompanyAddressSeeder.
     *
     * @return array<int, array{name: string, email: string, company: string}>
     */
    private function users(): array
    {
        return [
            // Stellar Industries — two users
            [
                'name' => 'Lim Wei Jie',
                'email' => 'weijie.lim@stellarindustries.com.my',
                'company' => 'Stellar Industries Sdn Bhd',
            ],
            [
                'name' => 'Siti Aminah',
                'email' => 'aminah.siti@stellarindustries.com.my',
                'company' => 'Stellar Industries Sdn Bhd',
            ],

            // Nusantara Trading — one user
            [
                'name' => 'Tan Boon Kiat',
                'email' => 'boonkiat.tan@nusantaratrading.sg',
                'company' => 'Nusantara Trading Co',
            ],

            // Borneo Logistics — one user
            [
                'name' => 'Ahmad Razak',
                'email' => 'razak.ahmad@borneologistics.my',
                'company' => 'Borneo Logistics',
            ],
        ];
    }
}
