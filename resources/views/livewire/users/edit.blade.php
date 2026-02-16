<?php

use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\Volt\Component;

new class extends Component
{
    public User $user;

    /** @var int|string|null */
    public $company_id = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->company_id = $user->company_id;
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function with(): array
    {
        return [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * Update the user.
     */
    public function update(): void
    {
        if ($this->company_id === '') {
            $this->company_id = null;
        }

        $rules = [
            'company_id' => ['nullable', 'integer', Rule::exists(Company::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user->id),
            ],
        ];

        if (! empty($this->password)) {
            $rules['password'] = ['required', 'string', 'confirmed', Rules\Password::defaults()];
        }

        $validated = $this->validate($rules);

        $this->user->company_id = ($validated['company_id'] ?? null) ? (int) $validated['company_id'] : null;
        $this->user->name = $validated['name'];
        $this->user->email = $validated['email'];

        if (! empty($this->password)) {
            $this->user->password = Hash::make($validated['password']);
        }

        if ($this->user->isDirty('email')) {
            $this->user->email_verified_at = null;
        }

        $this->user->save();

        Session::flash('success', __('User updated successfully.'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Edit User') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Edit User')" :subtitle="__('Update user information')">
            <x-slot name="actions">
                <x-ui.button variant="ghost" as="a" href="{{ route('admin.users.index') }}" wire:navigate>
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <x-ui.card>
            <form wire:submit="update" class="space-y-6">
                <x-ui.select
                    wire:model="company_id"
                    label="{{ __('Company') }}"
                    :error="$errors->first('company_id')"
                >
                    <option value="">{{ __('No company') }}</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </x-ui.select>

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

                <div class="border-t border-border-input my-6 pt-6">
                    <h3 class="text-sm font-medium text-ink mb-4">{{ __('Change Password (Optional)') }}</h3>
                </div>

                <x-ui.input
                    wire:model="password"
                    label="{{ __('New Password') }}"
                    type="password"
                    autocomplete="new-password"
                    placeholder="{{ __('Leave blank to keep current password') }}"
                    :error="$errors->first('password')"
                />

                <x-ui.input
                    wire:model="password_confirmation"
                    label="{{ __('Confirm New Password') }}"
                    type="password"
                    autocomplete="new-password"
                    placeholder="{{ __('Confirm new password') }}"
                    :error="$errors->first('password_confirmation')"
                />

                <div class="flex items-center gap-4">
                    <x-ui.button type="submit" variant="primary">
                        {{ __('Update User') }}
                    </x-ui.button>
                    <x-ui.button variant="ghost" as="a" href="{{ route('admin.users.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>

