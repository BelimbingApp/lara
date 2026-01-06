<?php

use App\Modules\Core\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\Volt\Component;

new class extends Component {
    public User $user;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
    }

    /**
     * Update the user.
     */
    public function update(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user->id)
            ],
        ];

        // Only validate password if it's provided
        if (!empty($this->password)) {
            $rules['password'] = ['required', 'string', 'confirmed', Rules\Password::defaults()];
        }

        $validated = $this->validate($rules);

        $this->user->name = $validated['name'];
        $this->user->email = $validated['email'];

        if (!empty($this->password)) {
            $this->user->password = Hash::make($validated['password']);
        }

        // Reset email verification if email changed
        if ($this->user->isDirty('email')) {
            $this->user->email_verified_at = null;
        }

        $this->user->save();

        Session::flash('success', __('User updated successfully.'));

        $this->redirect(route('users.index'), navigate: true);
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Edit User') }}</x-slot>

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ __('Edit User') }}</h1>
                <p class="text-sm text-base-content/70 mt-1">{{ __('Update user information') }}</p>
            </div>
            <a href="{{ route('users.index') }}" wire:navigate class="btn btn-ghost">
                <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                {{ __('Back') }}
            </a>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <form wire:submit="update" class="space-y-6">
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

                    <div class="divider">{{ __('Change Password (Optional)') }}</div>

                    <x-mary-input
                        wire:model="password"
                        label="{{ __('New Password') }}"
                        type="password"
                        autocomplete="new-password"
                        placeholder="{{ __('Leave blank to keep current password') }}"
                    />

                    <x-mary-input
                        wire:model="password_confirmation"
                        label="{{ __('Confirm New Password') }}"
                        type="password"
                        autocomplete="new-password"
                        placeholder="{{ __('Confirm new password') }}"
                    />

                    <div class="flex items-center gap-4">
                        <x-mary-button type="submit" class="btn-primary">
                            {{ __('Update User') }}
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

