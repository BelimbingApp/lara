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
    /** @var int|string|null */
    public $company_id = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function with(): array
    {
        return [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * Store a newly created user.
     */
    public function store(): void
    {
        if ($this->company_id === '') {
            $this->company_id = null;
        }

        $validated = $this->validate([
            'company_id' => ['nullable', 'integer', Rule::exists(Company::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['company_id'] = ($validated['company_id'] ?? null) ? (int) $validated['company_id'] : null;

        User::create($validated);

        Session::flash('success', __('User created successfully.'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Create User') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Create User')" :subtitle="__('Add a new user to the system')">
            <x-slot name="actions">
                <x-ui.button variant="ghost" as="a" href="{{ route('admin.users.index') }}" wire:navigate>
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <x-ui.card>
            <form wire:submit="store" class="space-y-6">
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
                    <x-ui.button variant="ghost" as="a" href="{{ route('admin.users.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>

