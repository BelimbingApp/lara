<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\User\Database\Seeders\Dev;

use App\Base\Database\Seeders\DevSeeder;
use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\User\Models\User;

class DevAdminUserSeeder extends DevSeeder
{
    /**
     * Seed the database.
     *
     * Creates an admin user for development only if the users table is empty.
     * Reads name, email, password from DEV_ADMIN_* env vars; falls back to safe dev defaults.
     * Delete DEV_ADMIN_* from .env in production.
     */
    protected function seed(): void
    {
        if (User::query()->exists()) {
            return;
        }

        // env() won't read .env if config:cache is active; acceptable since DevSeeder blocks non-local.
        $name = env('DEV_ADMIN_NAME', 'Administrator');
        $email = env('DEV_ADMIN_EMAIL', 'admin@example.com');
        $password = env('DEV_ADMIN_PASSWORD', 'password');

        User::create([
            'company_id' => Company::LICENSEE_ID,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
        ]);
    }
}
