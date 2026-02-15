<?php

use App\Modules\Core\Company\Models\Company;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $parent_id = null;

    public string $name = '';

    public ?string $slug = null;

    public string $status = 'active';

    public ?string $legal_name = null;

    public ?string $registration_number = null;

    public ?string $tax_id = null;

    public ?string $legal_entity_type = null;

    public ?string $jurisdiction = null;

    public ?string $email = null;

    public ?string $website = null;

    public string $scope_activities_json = '';

    public string $metadata_json = '';

    public function with(): array
    {
        return [
            'parentCompanies' => Company::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    public function store(): void
    {
        $validated = $this->validate($this->rules());

        $validated['scope_activities'] = $this->decodeJsonField($validated['scope_activities_json']);
        $validated['metadata'] = $this->decodeJsonField($validated['metadata_json']);

        unset($validated['scope_activities_json'], $validated['metadata_json']);

        Company::query()->create($validated);

        Session::flash('success', __('Company created successfully.'));

        $this->redirect(route('admin.companies.index'), navigate: true);
    }

    protected function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists(Company::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(Company::class, 'slug')],
            'status' => ['required', 'in:active,suspended,pending,archived'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'legal_entity_type' => ['nullable', 'string', 'max:255'],
            'jurisdiction' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'scope_activities_json' => ['nullable', 'json'],
            'metadata_json' => ['nullable', 'json'],
        ];
    }

    protected function decodeJsonField(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Create Company') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Create Company')" :subtitle="__('Add a company record and business context')">
            <x-slot name="actions">
                <a href="{{ route('admin.companies.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl hover:bg-surface-subtle text-link transition-colors">
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back') }}
                </a>
            </x-slot>
        </x-ui.page-header>

        <x-ui.card>
            <form wire:submit="store" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.select wire:model="parent_id" label="{{ __('Parent Company') }}" :error="$errors->first('parent_id')">
                        <option value="">{{ __('None (Root Company)') }}</option>
                        @foreach($parentCompanies as $parentCompany)
                            <option value="{{ $parentCompany->id }}">{{ $parentCompany->name }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.select wire:model="status" label="{{ __('Status') }}" :error="$errors->first('status')">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="suspended">{{ __('Suspended') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="archived">{{ __('Archived') }}</option>
                    </x-ui.select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.input
                        wire:model="name"
                        label="{{ __('Name') }}"
                        type="text"
                        required
                        placeholder="{{ __('Company display name') }}"
                        :error="$errors->first('name')"
                    />

                    <x-ui.input
                        wire:model="legal_name"
                        label="{{ __('Legal Name') }}"
                        type="text"
                        placeholder="{{ __('Registered legal entity name') }}"
                        :error="$errors->first('legal_name')"
                    />

                    <x-ui.input
                        wire:model="legal_entity_type"
                        label="{{ __('Legal Entity Type') }}"
                        type="text"
                        placeholder="{{ __('LLC, Corporation, Partnership, etc.') }}"
                        :error="$errors->first('legal_entity_type')"
                    />

                    <x-ui.input
                        wire:model="registration_number"
                        label="{{ __('Registration Number') }}"
                        type="text"
                        placeholder="{{ __('Business registration number') }}"
                        :error="$errors->first('registration_number')"
                    />

                    <x-ui.input
                        wire:model="tax_id"
                        label="{{ __('Tax ID') }}"
                        type="text"
                        placeholder="{{ __('Tax identification number') }}"
                        :error="$errors->first('tax_id')"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-ui.input
                        wire:model="jurisdiction"
                        label="{{ __('Jurisdiction') }}"
                        type="text"
                        placeholder="{{ __('Country/state of registration') }}"
                        :error="$errors->first('jurisdiction')"
                    />

                    <x-ui.input
                        wire:model="email"
                        label="{{ __('Email') }}"
                        type="email"
                        placeholder="{{ __('Company contact email') }}"
                        :error="$errors->first('email')"
                    />

                    <x-ui.input
                        wire:model="website"
                        label="{{ __('Website') }}"
                        type="text"
                        placeholder="{{ __('example.com') }}"
                        :error="$errors->first('website')"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.textarea
                        wire:model="scope_activities_json"
                        label="{{ __('Business Activities (JSON)') }}"
                        rows="6"
                        placeholder="{{ __('{\"industry\":\"Manufacturing\",\"services\":[\"Shipping\"],\"business_focus\":\"Regional trade\"}') }}"
                        :error="$errors->first('scope_activities_json')"
                    />

                    <x-ui.textarea
                        wire:model="metadata_json"
                        label="{{ __('Metadata (JSON)') }}"
                        rows="6"
                        placeholder="{{ __('{\"employee_count\":120,\"founded_year\":2014}') }}"
                        :error="$errors->first('metadata_json')"
                    />
                </div>

                <div class="flex items-center gap-4">
                    <x-ui.button type="submit" variant="primary">
                        {{ __('Create Company') }}
                    </x-ui.button>
                    <a href="{{ route('admin.companies.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl hover:bg-surface-subtle text-link transition-colors">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
