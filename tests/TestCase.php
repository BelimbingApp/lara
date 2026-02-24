<?php

namespace Tests;

use Database\Seeders\TestingSeeder;
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
     * Use deterministic test seeding instead of the default DatabaseSeeder.
     *
     * @var class-string
     */
    protected string $seeder = TestingSeeder::class;
}
