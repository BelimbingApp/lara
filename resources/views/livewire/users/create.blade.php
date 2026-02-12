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
                <h1 class="text-2xl font-bold text-ink">{{ __('Create User') }}</h1>
                <p class="text-sm text-muted mt-1">{{ __('Add a new user to the system') }}</p>
            </div>
            <a href="{{ route('users.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-surface-subtle text-link transition-colors">
                <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                {{ __('Back') }}
            </a>
        </div>

        <div class="bg-surface-card border border-border-default shadow-sm rounded-lg">
            <div class="p-6">
                <form wire:submit="store" class="space-y-6">
                    <x-ui.input
                        wire:model="name"
                        label="{{ __('Name') }}"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="{{ __('Enter user name') }}"
                        :error="$errors->first('name')"
                    />

                    <x-ui.input
                        wire:model="email"
                        label="{{ __('Email') }}"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="{{ __('Enter email address') }}"
                        :error="$errors->first('email')"
                    />

                    <x-ui.input
                        wire:model="password"
                        label="{{ __('Password') }}"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="{{ __('Enter password') }}"
                        :error="$errors->first('password')"
                    />

                    <x-ui.input
                        wire:model="password_confirmation"
                        label="{{ __('Confirm Password') }}"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="{{ __('Confirm password') }}"
                        :error="$errors->first('password_confirmation')"
                    />

                    <div class="flex items-center gap-4">
                        <x-ui.button type="submit" variant="primary">
                            {{ __('Create User') }}
                        </x-ui.button>
                        <a href="{{ route('users.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-surface-subtle text-link transition-colors">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

