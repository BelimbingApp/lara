<?php

use App\Base\Authz\Models\Role;
use App\Modules\Core\Company\Models\Company;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';

    public string $code = '';

    public string $description = '';

    public ?int $company_id = null;

    /**
     * Create a new custom role.
     */
    public function createRole(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('base_authz_roles', 'code')
                    ->when(
                        $this->company_id !== null,
                        fn ($rule) => $rule->where('company_id', $this->company_id),
                        fn ($rule) => $rule->whereNull('company_id'),
                    ),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        $role = Role::query()->create([
            'company_id' => $this->company_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description ?: null,
            'is_system' => false,
        ]);

        $this->redirect(route('admin.roles.show', $role), navigate: true);
    }

    public function with(): array
    {
        return [
            'companies' => Company::query()
                ->where('id', Company::LICENSEE_ID)
                ->orWhere('parent_id', Company::LICENSEE_ID)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Create Role') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Create Role')" :subtitle="__('Create a new custom role')">
            <x-slot name="actions">
                <x-ui.button variant="ghost" as="a" href="{{ route('admin.roles.index') }}" wire:navigate>
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <x-ui.card>
            <form wire:submit="createRole" class="space-y-4 max-w-lg">
                <x-ui.input
                    wire:model="name"
                    label="{{ __('Name') }}"
                    required
                    placeholder="{{ __('e.g. Sales Manager') }}"
                    :error="$errors->first('name')"
                />

                <x-ui.input
                    wire:model="code"
                    label="{{ __('Code') }}"
                    required
                    placeholder="{{ __('e.g. sales_manager') }}"
                    :error="$errors->first('code')"
                />
                <p class="text-xs text-muted -mt-2">{{ __('Lowercase letters, numbers, and underscores only.') }}</p>

                <x-ui.textarea
                    wire:model="description"
                    label="{{ __('Description') }}"
                    rows="3"
                    placeholder="{{ __('What this role is for...') }}"
                    :error="$errors->first('description')"
                />

                <x-ui.select
                    wire:model="company_id"
                    label="{{ __('Company Scope') }}"
                    :error="$errors->first('company_id')"
                >
                    <option value="">{{ __('Global (all companies)') }}</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </x-ui.select>
                <p class="text-xs text-muted -mt-2">{{ __('Leave as global to make this role available to all companies, or scope it to a specific company.') }}</p>

                <div class="pt-2">
                    <x-ui.button type="submit" variant="primary">
                        {{ __('Create Role') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
