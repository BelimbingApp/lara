<?php

use App\Modules\Core\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Store a newly created user.
     */
    public function store(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        Session::flash('success', __('User created successfully.'));

        $this->redirect(route('users.index'), navigate: true);
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Create User') }}</x-slot>

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ __('Create User') }}</h1>
                <p class="text-sm text-base-content/70 mt-1">{{ __('Add a new user to the system') }}</p>
            </div>
            <a href="{{ route('users.index') }}" wire:navigate class="btn btn-ghost">
                <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                {{ __('Back') }}
            </a>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <form wire:submit="store" class="space-y-6">
                    <x-mary-input
                        wire:model="name"
                        label="{{ __('Name') }}"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="{{ __('Enter user name') }}"
                    />

                    <x-mary-input
                        wire:model="email"
                        label="{{ __('Email') }}"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="{{ __('Enter email address') }}"
                    />

                    <x-mary-input
                        wire:model="password"
                        label="{{ __('Password') }}"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="{{ __('Enter password') }}"
                    />

                    <x-mary-input
                        wire:model="password_confirmation"
                        label="{{ __('Confirm Password') }}"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="{{ __('Confirm password') }}"
                    />

                    <div class="flex items-center gap-4">
                        <x-mary-button type="submit" class="btn-primary">
                            {{ __('Create User') }}
                        </x-mary-button>
                        <a href="{{ route('users.index') }}" wire:navigate class="btn btn-ghost">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

