<?php

namespace Tests;

use Tests\TestingBaselineSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed baseline reference data after each test database refresh.
     *
     * Uses a test-only seeder to avoid network-bound or dev-only seeders.
     *
     * @var bool
     */
    protected bool $seed = true;

    /**
     * Use deterministic test seeding (registry seeders for modules listed in tests manifest).
     *
     * @var class-string
     */
    protected string $seeder = TestingBaselineSeeder::class;
}
