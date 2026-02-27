<?php

use App\Base\Authz\Enums\PrincipalType;
use App\Base\Authz\Models\PrincipalRole;
use App\Base\Authz\Models\Role;
use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\User\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $roles = config('authz.roles', []);

    foreach ($roles as $code => $roleDef) {
        $role = Role::query()->firstOrCreate(
            ['company_id' => null, 'code' => $code],
            ['name' => $roleDef['name'], 'description' => $roleDef['description'] ?? null, 'is_system' => true, 'grant_all' => $roleDef['grant_all'] ?? false]
        );

        $now = now();

        foreach ($roleDef['capabilities'] ?? [] as $capKey) {
            DB::table('base_authz_role_capabilities')->insertOrIgnore([
                'role_id' => $role->id,
                'capability_key' => strtolower($capKey),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
});

test('guests are redirected to login from employee pages', function (): void {
    $this->get(route('admin.employees.index'))->assertRedirect(route('login'));
});

test('authenticated users can view employee index', function (): void {
    $user = createAdminUser();
    $this->actingAs($user);

    $this->get(route('admin.employees.index'))->assertOk();
});

test('employees.index Volt component resolves', function (): void {
    $user = createAdminUser();
    $this->actingAs($user);

    Livewire::test('employees.index')->assertOk();
});
