<?php

use App\Base\Authz\Database\Seeders\AuthzRoleCapabilitySeeder;
use App\Base\Authz\Exceptions\AuthzRoleCapabilitySeedingException;

it('throws a dedicated exception when a configured role is missing during capability seeding', function (): void {
    config()->set('authz.roles', [
        'missing-role' => [
            'name' => 'Missing Role',
            'description' => null,
            'capabilities' => [],
        ],
    ]);

    $seeder = new class extends AuthzRoleCapabilitySeeder
    {
        public function call($class, $silent = false, array $parameters = [])
        {
            return $this;
        }
    };

    expect(fn () => $seeder->run())
        ->toThrow(function (AuthzRoleCapabilitySeedingException $exception): void {
            expect($exception->getMessage())->toBe('Missing role [missing-role] before seeding role capabilities.')
                ->and($exception->context['role_code'] ?? null)->toBe('missing-role');
        });
});
