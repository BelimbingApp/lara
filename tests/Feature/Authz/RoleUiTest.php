<?php

use App\Base\Authz\Enums\PrincipalType;
use App\Base\Authz\Models\PrincipalRole;
use App\Base\Authz\Models\Role;
use App\Base\Authz\Models\RoleCapability;
use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\User\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $roles = config('authz.roles', []);

    foreach ($roles as $code => $roleDef) {
        $role = Role::query()->firstOrCreate(
            ['company_id' => null, 'code' => $code],
            ['name' => $roleDef['name'], 'description' => $roleDef['description'] ?? null, 'is_system' => true]
        );

        $now = now();

        foreach ($roleDef['capabilities'] as $capKey) {
            DB::table('base_authz_role_capabilities')->insertOrIgnore([
                'role_id' => $role->id,
                'capability_key' => strtolower($capKey),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
});

/**
 * Create a user with core_admin role for tests that need authz capabilities.
 */
function createRoleTestAdmin(): User
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::query()->where('code', 'core_admin')->whereNull('company_id')->firstOrFail();

    PrincipalRole::query()->create([
        'company_id' => $company->id,
        'principal_type' => PrincipalType::HUMAN_USER->value,
        'principal_id' => $user->id,
        'role_id' => $role->id,
    ]);

    return $user;
}

test('guests are redirected to login from role pages', function (): void {
    $role = Role::query()->first();

    $this->get(route('admin.roles.index'))->assertRedirect(route('login'));
    $this->get(route('admin.roles.show', $role))->assertRedirect(route('login'));
});

test('authenticated users with capability can view role pages', function (): void {
    $user = createRoleTestAdmin();
    $role = Role::query()->first();

    $this->actingAs($user);

    $this->get(route('admin.roles.index'))->assertOk();
    $this->get(route('admin.roles.show', $role))->assertOk();
});

test('authenticated users without capability are denied role pages', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::query()->first();

    $this->actingAs($user);

    $this->get(route('admin.roles.index'))->assertStatus(403);
    $this->get(route('admin.roles.show', $role))->assertStatus(403);
});

test('role index displays roles with search', function (): void {
    $user = createRoleTestAdmin();
    $this->actingAs($user);

    Livewire::test('admin.roles.index')
        ->assertSee('Core Administrator')
        ->assertSee('User Viewer')
        ->assertSee('User Editor');

    Livewire::test('admin.roles.index')
        ->set('search', 'viewer')
        ->assertSee('User Viewer')
        ->assertDontSee('Core Administrator');
});

test('role show displays role details and capabilities', function (): void {
    $user = createRoleTestAdmin();
    $role = Role::query()->where('code', 'core_admin')->firstOrFail();

    $this->actingAs($user);

    Livewire::test('admin.roles.show', ['role' => $role])
        ->assertSee('Core Administrator')
        ->assertSee('core_admin')
        ->assertSee('core.user.view');
});

test('capabilities can be assigned to a role', function (): void {
    $user = createRoleTestAdmin();
    $role = Role::query()->where('code', 'user_viewer')->firstOrFail();

    $this->actingAs($user);

    $initialCount = $role->capabilities()->count();

    Livewire::test('admin.roles.show', ['role' => $role])
        ->set('selectedCapabilities', ['core.user.create'])
        ->call('assignCapabilities');

    expect($role->capabilities()->count())->toBe($initialCount + 1);
    expect(
        $role->capabilities()->where('capability_key', 'core.user.create')->exists()
    )->toBeTrue();
});

test('capabilities can be removed from a role', function (): void {
    $user = createRoleTestAdmin();
    $role = Role::query()->where('code', 'user_viewer')->firstOrFail();

    $cap = $role->capabilities()->where('capability_key', 'core.user.view')->first();

    $this->actingAs($user);

    Livewire::test('admin.roles.show', ['role' => $role])
        ->call('removeCapability', $cap->id);

    expect(
        $role->capabilities()->where('capability_key', 'core.user.view')->exists()
    )->toBeFalse();
});

test('users without update capability cannot modify role capabilities', function (): void {
    $company = Company::factory()->create();
    $viewer = User::factory()->create(['company_id' => $company->id]);
    $viewerRole = Role::query()->where('code', 'user_viewer')->whereNull('company_id')->firstOrFail();

    // Give viewer only user_viewer role (has core.user.list + core.user.view, not admin.role.update)
    PrincipalRole::query()->create([
        'company_id' => $company->id,
        'principal_type' => PrincipalType::HUMAN_USER->value,
        'principal_id' => $viewer->id,
        'role_id' => $viewerRole->id,
    ]);

    $targetRole = Role::query()->where('code', 'user_editor')->firstOrFail();
    $initialCount = $targetRole->capabilities()->count();

    $this->actingAs($viewer);

    Livewire::test('admin.roles.show', ['role' => $targetRole])
        ->set('selectedCapabilities', ['core.company.view'])
        ->call('assignCapabilities');

    expect($targetRole->capabilities()->count())->toBe($initialCount);
});
