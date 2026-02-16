<?php

use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\User\Models\User;
use Livewire\Livewire;

test('guests are redirected to login from user pages', function (): void {
    $user = User::factory()->create();

    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    $this->get(route('admin.users.create'))->assertRedirect(route('login'));
    $this->get(route('admin.users.show', $user))->assertRedirect(route('login'));
});

test('authenticated users can view user pages', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('admin.users.index'))->assertOk();
    $this->get(route('admin.users.create'))->assertOk();
    $this->get(route('admin.users.show', $other))->assertOk();
});

test('user can be created from create page component', function (): void {
    $actor = User::factory()->create();
    $this->actingAs($actor);

    Livewire::test('users.create')
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'SecurePassword123!')
        ->set('password_confirmation', 'SecurePassword123!')
        ->call('store')
        ->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'jane@example.com')->first();

    expect($user)
        ->not()->toBeNull()
        ->and($user->name)->toBe('Jane Doe')
        ->and($user->company_id)->toBeNull();
});

test('user can be created with company', function (): void {
    $actor = User::factory()->create();
    $company = Company::factory()->create();
    $this->actingAs($actor);

    Livewire::test('users.create')
        ->set('company_id', (string) $company->id)
        ->set('name', 'John Smith')
        ->set('email', 'john@example.com')
        ->set('password', 'SecurePassword123!')
        ->set('password_confirmation', 'SecurePassword123!')
        ->call('store')
        ->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'john@example.com')->first();

    expect($user)
        ->not()->toBeNull()
        ->and($user->company_id)->toBe($company->id);
});

test('user fields can be inline edited from show page', function (): void {
    $actor = User::factory()->create();
    $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);
    $this->actingAs($actor);

    Livewire::test('users.show', ['user' => $user])
        ->call('saveField', 'name', 'New Name');

    $user->refresh();
    expect($user->name)->toBe('New Name');

    Livewire::test('users.show', ['user' => $user])
        ->call('saveField', 'email', 'new@example.com');

    $user->refresh();
    expect($user->email)->toBe('new@example.com');
});

test('email change resets email_verified_at', function (): void {
    $actor = User::factory()->create();
    $user = User::factory()->create([
        'email' => 'verified@example.com',
        'email_verified_at' => now(),
    ]);
    $this->actingAs($actor);

    Livewire::test('users.show', ['user' => $user])
        ->call('saveField', 'email', 'changed@example.com');

    $user->refresh();
    expect($user->email)->toBe('changed@example.com')
        ->and($user->email_verified_at)->toBeNull();
});

test('company can be changed from show page', function (): void {
    $actor = User::factory()->create();
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => null]);
    $this->actingAs($actor);

    Livewire::test('users.show', ['user' => $user])
        ->call('saveCompany', $company->id);

    $user->refresh();
    expect($user->company_id)->toBe($company->id);

    Livewire::test('users.show', ['user' => $user])
        ->call('saveCompany', null);

    $user->refresh();
    expect($user->company_id)->toBeNull();
});

test('password can be updated from show page', function (): void {
    $actor = User::factory()->create();
    $user = User::factory()->create();
    $this->actingAs($actor);

    Livewire::test('users.show', ['user' => $user])
        ->set('password', 'NewSecurePassword123!')
        ->set('password_confirmation', 'NewSecurePassword123!')
        ->call('updatePassword')
        ->assertHasNoErrors();
});

test('password update requires confirmation', function (): void {
    $actor = User::factory()->create();
    $user = User::factory()->create();
    $this->actingAs($actor);

    Livewire::test('users.show', ['user' => $user])
        ->set('password', 'NewSecurePassword123!')
        ->set('password_confirmation', 'WrongConfirmation!')
        ->call('updatePassword')
        ->assertHasErrors(['password']);
});

test('user can be deleted from index and cannot delete self', function (): void {
    $actor = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($actor);

    Livewire::test('users.index')
        ->call('delete', $other->id);

    expect(User::query()->find($other->id))->toBeNull();

    Livewire::test('users.index')
        ->call('delete', $actor->id);

    expect(User::query()->find($actor->id))->not()->toBeNull();
});
