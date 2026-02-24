<?php

namespace Database\Seeders;

use App\Base\Authz\Database\Seeders\AuthzRoleCapabilitySeeder;
use App\Base\Authz\Database\Seeders\AuthzRoleSeeder;
use App\Modules\Core\Company\Database\Seeders\DepartmentTypeSeeder;
use App\Modules\Core\Company\Database\Seeders\LegalEntityTypeSeeder;
use App\Modules\Core\Company\Database\Seeders\RelationshipTypeSeeder;
use Illuminate\Database\Seeder;

/**
 * Baseline seed data for automated tests.
 *
 * This seeder intentionally includes only deterministic, local reference data.
 * It must never depend on external network resources.
 */
class TestingSeeder extends Seeder
{
    /**
     * Seed stable reference data required by feature and domain tests.
     */
    public function run(): void
    {
        $this->call([
            AuthzRoleSeeder::class,
            AuthzRoleCapabilitySeeder::class,
            RelationshipTypeSeeder::class,
            LegalEntityTypeSeeder::class,
            DepartmentTypeSeeder::class,
        ]);
    }
}
