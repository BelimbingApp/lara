<?php

use App\Modules\Core\User\Livewire\Auth\ConfirmPassword;
use App\Modules\Core\User\Models\User;
use Livewire\Livewire;

test('password can be confirmed', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(ConfirmPassword::class)
        ->set('password', 'password')
        ->call('confirmPassword');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));
});

test('password is not confirmed with invalid password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(ConfirmPassword::class)
        ->set('password', 'wrong-password')
        ->call('confirmPassword');

    $response->assertHasErrors(['password']);
});
