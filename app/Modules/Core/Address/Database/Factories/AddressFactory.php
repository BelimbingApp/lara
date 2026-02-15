<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Address\Database\Factories;

use App\Modules\Core\Address\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Core\Address\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => fake()->randomElement(['HQ', 'Warehouse', 'Office', 'Billing', 'Shipping']),
            'phone' => fake()->phoneNumber(),
            'line1' => fake()->streetAddress(),
            'line2' => fake()->optional()->secondaryAddress(),
            'locality' => fake()->city(),
            'postcode' => fake()->postcode(),
            'country_iso' => fake()->countryCode(),
            'verification_status' => 'unverified',
            'source' => 'manual',
        ];
    }

    /**
     * Indicate that the address is verified.
     */
    public function verified(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'verification_status' => 'verified',
            ],
        );
    }

    /**
     * Indicate that the address is suggested.
     */
    public function suggested(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'verification_status' => 'suggested',
            ],
        );
    }

    /**
     * Indicate that the address is unverified.
     */
    public function unverified(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'verification_status' => 'unverified',
            ],
        );
    }

    /**
     * Indicate that the address has raw input.
     */
    public function withRawInput(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'raw_input' => fake()->address(),
                'parsed_at' => now(),
                'parser_version' => '1.0.0',
                'parse_confidence' => fake()->randomFloat(4, 0.5, 1.0),
            ],
        );
    }

    /**
     * Indicate that the address was sourced from a scan.
     */
    public function fromScan(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'source' => 'scan',
                'source_ref' => fake()->uuid(),
            ],
        );
    }

    /**
     * Indicate that the address was sourced from an import.
     */
    public function fromImport(): static
    {
        return $this->state(
            fn (array $attributes) => [
                'source' => 'import',
                'source_ref' => fake()->uuid(),
            ],
        );
    }
}
