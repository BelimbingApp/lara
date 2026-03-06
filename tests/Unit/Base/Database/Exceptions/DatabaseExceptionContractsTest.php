<?php

use App\Base\Database\Exceptions\CircularSeederDependencyException;
use App\Base\Database\Exceptions\DevSeederProductionEnvironmentException;
use App\Base\Foundation\Enums\BlbErrorCode;

it('returns structured reason code for dev seeder environment violations', function (): void {
    $exception = DevSeederProductionEnvironmentException::forEnvironment('production');

    expect($exception->reasonCode)->toBe(BlbErrorCode::DEV_SEEDER_NON_LOCAL_ENV)
        ->and($exception->context)->toBe(['environment' => 'production'])
        ->and($exception->getMessage())->toContain('APP_ENV=local');
});

it('returns structured reason code for circular seeder dependencies', function (): void {
    $exception = CircularSeederDependencyException::forClasses([
        'App\\Seeders\\A',
        'App\\Seeders\\B',
    ]);

    expect($exception->reasonCode)->toBe(BlbErrorCode::CIRCULAR_SEEDER_DEPENDENCY)
        ->and($exception->context)->toBe([
            'seeder_classes' => ['App\\Seeders\\A', 'App\\Seeders\\B'],
        ])
        ->and($exception->getMessage())->toContain('Circular dependency detected');
});
