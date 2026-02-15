<?php

use App\Modules\Core\Address\Models\Address;
use App\Modules\Core\Company\Models\Company;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component
{
    public Company $company;

    public int $attach_address_id = 0;

    public string $attach_kind = 'billing';

    public bool $attach_is_primary = false;

    public int $attach_priority = 0;

    public bool $showAttachModal = false;

    public function mount(Company $company): void
    {
        $this->company = $company->load([
            'parent',
            'addresses',
            'children',
            'departments.type',
            'relationships.type',
            'relationships.relatedCompany',
            'inverseRelationships.type',
            'inverseRelationships.company',
        ]);
    }

    public function saveField(string $field, mixed $value): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'legal_entity_type' => ['nullable', 'string', 'max:255'],
            'jurisdiction' => ['nullable', 'string', 'max:255'],
        ];

        if (! isset($rules[$field])) {
            return;
        }

        $validated = validator([$field => $value], [$field => $rules[$field]])->validate();

        $this->company->$field = $validated[$field];
        $this->company->save();
    }

    public function saveStatus(string $status): void
    {
        if (! in_array($status, ['active', 'suspended', 'pending', 'archived'])) {
            return;
        }

        $this->company->status = $status;
        $this->company->save();
    }

    public function saveParent(?int $parentId): void
    {
        $this->company->parent_id = $parentId ?: null;
        $this->company->save();
        $this->company->load('parent');
    }

    public function addActivity(string $activity): void
    {
        $activity = trim($activity);
        if ($activity === '') {
            return;
        }

        $activities = $this->company->scope_activities ?? [];
        $activities[] = $activity;
        $this->company->scope_activities = array_values(array_unique($activities));
        $this->company->save();
    }

    public function removeActivity(int $index): void
    {
        $activities = $this->company->scope_activities ?? [];
        unset($activities[$index]);
        $this->company->scope_activities = array_values($activities) ?: null;
        $this->company->save();
    }

    public function saveMetadata(string $json): void
    {
        $json = trim($json);

        if ($json === '') {
            $this->company->metadata = null;
            $this->company->save();

            return;
        }

        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }

        $this->company->metadata = $decoded;
        $this->company->save();
    }

    public function updateAddressPivot(int $addressId, string $field, mixed $value): void
    {
        $allowed = ['kind', 'is_primary', 'priority'];
        if (! in_array($field, $allowed)) {
            return;
        }

        if ($field === 'is_primary') {
            $value = (bool) $value;
        } elseif ($field === 'priority') {
            $value = (int) $value;
        }

        $this->company->addresses()->updateExistingPivot($addressId, [$field => $value]);
        $this->company->load('addresses');
    }

    public function unlinkAddress(int $addressId): void
    {
        $this->company->addresses()->detach($addressId);
        $this->company->load('addresses');
        Session::flash('success', __('Address unlinked.'));
    }

    public function attachAddress(): void
    {
        if ($this->attach_address_id === 0) {
            return;
        }

        $this->company->addresses()->attach($this->attach_address_id, [
            'kind' => $this->attach_kind,
            'is_primary' => $this->attach_is_primary,
            'priority' => $this->attach_priority,
            'valid_from' => now()->toDateString(),
        ]);

        $this->company->load('addresses');
        $this->showAttachModal = false;
        $this->reset(['attach_address_id', 'attach_kind', 'attach_is_primary', 'attach_priority']);
        Session::flash('success', __('Address attached.'));
    }

    public function with(): array
    {
        $linkedIds = $this->company->addresses->pluck('id')->toArray();

        return [
            'availableAddresses' => Address::query()
                ->whereNotIn('id', $linkedIds)
                ->orderBy('label')
                ->get(['id', 'label', 'line1', 'locality', 'country_iso']),
            'parentCompanies' => Company::query()
                ->where('id', '!=', $this->company->id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}; ?>

<div>
    <x-slot name="title">{{ $company->name }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="$company->name" :subtitle="$company->legal_name">
            <x-slot name="actions">
                <a href="{{ route('admin.companies.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl hover:bg-surface-subtle text-link transition-colors">
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back to List') }}
                </a>
            </x-slot>
        </x-ui.page-header>

        @if (session('success'))
            <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
        @endif

        <x-ui.card>
            <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-4">{{ __('Company Details') }}</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div x-data="{ editing: false, val: '{{ addslashes($company->name) }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Name') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span x-text="val || '-'"></span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <input
                                x-show="editing"
                                x-ref="input"
                                x-model="val"
                                @keydown.enter="editing = false; $wire.saveField('name', val)"
                                @keydown.escape="editing = false; val = '{{ addslashes($company->name) }}'"
                                @blur="editing = false; $wire.saveField('name', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            />
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ addslashes($company->legal_name ?? '') }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Legal Name') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span x-text="val || '-'"></span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <input
                                x-show="editing"
                                x-ref="input"
                                x-model="val"
                                @keydown.enter="editing = false; $wire.saveField('legal_name', val)"
                                @keydown.escape="editing = false; val = '{{ addslashes($company->legal_name ?? '') }}'"
                                @blur="editing = false; $wire.saveField('legal_name', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            />
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ $company->status }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Status') }}</dt>
                        <dd class="mt-0.5">
                            <div x-show="!editing" @click="editing = true" class="group flex items-center gap-1.5 cursor-pointer">
                                <x-ui.badge :variant="match($company->status) {
                                    'active' => 'success',
                                    'suspended' => 'danger',
                                    'pending' => 'warning',
                                    default => 'default',
                                }">{{ ucfirst($company->status) }}</x-ui.badge>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <select
                                x-show="editing"
                                x-model="val"
                                @change="editing = false; $wire.saveStatus(val)"
                                @keydown.escape="editing = false; val = '{{ $company->status }}'"
                                @blur="editing = false"
                                class="px-2 py-1 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            >
                                <option value="active">{{ __('Active') }}</option>
                                <option value="suspended">{{ __('Suspended') }}</option>
                                <option value="pending">{{ __('Pending') }}</option>
                                <option value="archived">{{ __('Archived') }}</option>
                            </select>
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ addslashes($company->legal_entity_type ?? '') }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Legal Entity Type') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span x-text="val || '-'"></span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <input
                                x-show="editing"
                                x-ref="input"
                                x-model="val"
                                @keydown.enter="editing = false; $wire.saveField('legal_entity_type', val)"
                                @keydown.escape="editing = false; val = '{{ addslashes($company->legal_entity_type ?? '') }}'"
                                @blur="editing = false; $wire.saveField('legal_entity_type', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            />
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ addslashes($company->registration_number ?? '') }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Registration Number') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span x-text="val || '-'"></span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <input
                                x-show="editing"
                                x-ref="input"
                                x-model="val"
                                @keydown.enter="editing = false; $wire.saveField('registration_number', val)"
                                @keydown.escape="editing = false; val = '{{ addslashes($company->registration_number ?? '') }}'"
                                @blur="editing = false; $wire.saveField('registration_number', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            />
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ addslashes($company->tax_id ?? '') }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Tax ID') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span x-text="val || '-'"></span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <input
                                x-show="editing"
                                x-ref="input"
                                x-model="val"
                                @keydown.enter="editing = false; $wire.saveField('tax_id', val)"
                                @keydown.escape="editing = false; val = '{{ addslashes($company->tax_id ?? '') }}'"
                                @blur="editing = false; $wire.saveField('tax_id', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            />
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ addslashes($company->jurisdiction ?? '') }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Jurisdiction') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span x-text="val || '-'"></span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <input
                                x-show="editing"
                                x-ref="input"
                                x-model="val"
                                @keydown.enter="editing = false; $wire.saveField('jurisdiction', val)"
                                @keydown.escape="editing = false; val = '{{ addslashes($company->jurisdiction ?? '') }}'"
                                @blur="editing = false; $wire.saveField('jurisdiction', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            />
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ addslashes($company->email ?? '') }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Email') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span x-text="val || '-'"></span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <input
                                x-show="editing"
                                x-ref="input"
                                x-model="val"
                                @keydown.enter="editing = false; $wire.saveField('email', val)"
                                @keydown.escape="editing = false; val = '{{ addslashes($company->email ?? '') }}'"
                                @blur="editing = false; $wire.saveField('email', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            />
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ addslashes($company->website ?? '') }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Website') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span x-text="val || '-'"></span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <input
                                x-show="editing"
                                x-ref="input"
                                x-model="val"
                                @keydown.enter="editing = false; $wire.saveField('website', val)"
                                @keydown.escape="editing = false; val = '{{ addslashes($company->website ?? '') }}'"
                                @blur="editing = false; $wire.saveField('website', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            />
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ $company->parent_id ?? '' }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Parent Company') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span>
                                    @if($company->parent)
                                        {{ $company->parent->name }}
                                    @else
                                        <span class="text-muted">{{ __('None') }}</span>
                                    @endif
                                </span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <select
                                x-show="editing"
                                x-model="val"
                                @change="editing = false; $wire.saveParent(val ? parseInt(val) : null)"
                                @keydown.escape="editing = false; val = '{{ $company->parent_id ?? '' }}'"
                                @blur="editing = false"
                                class="px-2 py-1 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            >
                                <option value="">{{ __('None') }}</option>
                                @foreach($parentCompanies as $parentCompany)
                                    <option value="{{ $parentCompany->id }}">{{ $parentCompany->name }}</option>
                                @endforeach
                            </select>
                        </dd>
                    </div>
                </div>

                <div class="mt-4" x-data="{ adding: false, newItem: '' }">
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Business Activities') }}</dt>
                    <p class="text-xs text-muted mt-0.5 mb-1">{{ __('Industry, services, and business focus areas of this company.') }}</p>
                    <dd class="flex flex-wrap items-center gap-2">
                        @forelse($company->scope_activities ?? [] as $index => $activity)
                            @if(is_string($activity))
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-surface-subtle text-ink border border-border-default group">
                                    {{ $activity }}
                                    <button
                                        wire:click="removeActivity({{ $index }})"
                                        class="text-muted hover:text-status-danger opacity-0 group-hover:opacity-100 transition-opacity"
                                        title="{{ __('Remove') }}"
                                    >&times;</button>
                                </span>
                            @endif
                        @empty
                            <span class="text-sm text-muted" x-show="!adding">-</span>
                        @endforelse

                        <button
                            x-show="!adding"
                            @click="adding = true; $nextTick(() => $refs.newInput.focus())"
                            class="inline-flex items-center gap-0.5 px-2 py-1 rounded-full text-xs text-muted hover:text-ink hover:bg-surface-subtle border border-dashed border-border-default transition-colors"
                            title="{{ __('Add activity') }}"
                        >
                            <x-icon name="heroicon-o-plus" class="w-3 h-3" />
                            {{ __('Add') }}
                        </button>

                        <div x-show="adding" class="inline-flex items-center gap-1">
                            <input
                                x-ref="newInput"
                                x-model="newItem"
                                @keydown.enter="if (newItem.trim()) { $wire.addActivity(newItem.trim()); newItem = ''; } else { adding = false; }"
                                @keydown.escape="adding = false; newItem = ''"
                                @blur="if (newItem.trim()) { $wire.addActivity(newItem.trim()); newItem = ''; } adding = false;"
                                type="text"
                                placeholder="{{ __('e.g. manufacturing') }}"
                                class="px-2 py-0.5 text-xs border border-accent rounded-full bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent w-40"
                            />
                        </div>
                    </dd>
                </div>

                @php
                    $metadataJson = $company->metadata ? json_encode($company->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '';
                @endphp

                <div class="mt-4" x-data="{ editing: false, val: {{ $metadataJson ? '`' . addslashes($metadataJson) . '`' : "''" }} }">
                    <div class="flex items-center gap-1.5">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Metadata') }}</dt>
                        <button @click="editing = !editing; if (editing) $nextTick(() => $refs.textarea.focus())" class="group">
                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-50 hover:opacity-100 transition-opacity" />
                        </button>
                    </div>

                    <div x-show="!editing">
                        @if($company->metadata)
                            <dd class="mt-1"><pre class="text-sm text-ink bg-surface-subtle rounded-2xl p-3 overflow-x-auto">{{ $metadataJson }}</pre></dd>
                        @else
                            <dd class="text-sm text-muted mt-1">-</dd>
                        @endif
                    </div>

                    <div x-show="editing" class="mt-1 space-y-2">
                        <textarea
                            x-ref="textarea"
                            x-model="val"
                            @keydown.escape="editing = false; val = {{ $metadataJson ? '`' . addslashes($metadataJson) . '`' : "''" }}"
                            rows="6"
                            class="w-full px-input-x py-input-y text-sm font-mono border border-accent rounded-2xl bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            placeholder="{{ __('{"employee_count":120,"founded_year":2014}') }}"
                        ></textarea>
                        <div class="flex items-center gap-2">
                            <button @click="editing = false; $wire.saveMetadata(val)" class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-lg bg-accent text-accent-on hover:bg-accent-hover transition-colors">{{ __('Save') }}</button>
                            <button @click="editing = false; val = {{ $metadataJson ? '`' . addslashes($metadataJson) . '`' : "''" }}" class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-lg hover:bg-surface-subtle text-muted transition-colors">{{ __('Cancel') }}</button>
                        </div>
                    </div>
                </div>
        </x-ui.card>

        <x-ui.card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted">
                    {{ __('Addresses') }}
                    <x-ui.badge>{{ $company->addresses->count() }}</x-ui.badge>
                </h3>
                <x-ui.button variant="primary" size="sm" wire:click="$set('showAttachModal', true)">
                    <x-icon name="heroicon-o-plus" class="w-4 h-4" />
                    {{ __('Attach Address') }}
                </x-ui.button>
            </div>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Label') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Address') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Kind') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Primary') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Priority') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Valid From') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Valid To') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-right text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($company->addresses as $address)
                            <tr wire:key="address-{{ $address->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-ink font-medium">
                                    <a href="{{ route('admin.addresses.show', $address) }}" wire:navigate class="text-link hover:underline">{{ $address->label ?? '-' }}</a>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ collect([$address->line1, $address->locality, $address->country_iso])->filter()->implode(', ') }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted"
                                    x-data="{ editing: false, val: '{{ $address->pivot->kind }}' }"
                                >
                                    <div x-show="!editing" @click="editing = true" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                        <span x-text="val.charAt(0).toUpperCase() + val.slice(1)"></span>
                                        <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                    <select
                                        x-show="editing"
                                        x-model="val"
                                        @change="editing = false; $wire.updateAddressPivot({{ $address->id }}, 'kind', val)"
                                        @keydown.escape="editing = false; val = '{{ $address->pivot->kind }}'"
                                        @blur="editing = false"
                                        class="px-2 py-1 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                                    >
                                        <option value="headquarters">{{ __('Headquarters') }}</option>
                                        <option value="billing">{{ __('Billing') }}</option>
                                        <option value="shipping">{{ __('Shipping') }}</option>
                                        <option value="branch">{{ __('Branch') }}</option>
                                        <option value="other">{{ __('Other') }}</option>
                                    </select>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    <button
                                        wire:click="updateAddressPivot({{ $address->id }}, 'is_primary', {{ $address->pivot->is_primary ? '0' : '1' }})"
                                        class="cursor-pointer"
                                        title="{{ __('Toggle primary') }}"
                                    >
                                        @if($address->pivot->is_primary)
                                            <x-ui.badge variant="success">{{ __('Yes') }}</x-ui.badge>
                                        @else
                                            <span class="text-muted hover:text-ink transition-colors">{{ __('No') }}</span>
                                        @endif
                                    </button>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums"
                                    x-data="{ editing: false, val: '{{ $address->pivot->priority }}' }"
                                >
                                    <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                        <span x-text="val"></span>
                                        <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                    <input
                                        x-show="editing"
                                        x-ref="input"
                                        x-model="val"
                                        @keydown.enter="editing = false; $wire.updateAddressPivot({{ $address->id }}, 'priority', val)"
                                        @keydown.escape="editing = false; val = '{{ $address->pivot->priority }}'"
                                        @blur="editing = false; $wire.updateAddressPivot({{ $address->id }}, 'priority', val)"
                                        type="number"
                                        min="0"
                                        class="w-16 px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                                    />
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $address->pivot->valid_from ?? '-' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $address->pivot->valid_to ?? '-' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-right">
                                    <button
                                        wire:click="unlinkAddress({{ $address->id }})"
                                        wire:confirm="{{ __('Are you sure you want to unlink this address?') }}"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg hover:bg-status-danger-subtle text-status-danger transition-colors"
                                    >
                                        <x-icon name="heroicon-o-link-slash" class="w-4 h-4" />
                                        {{ __('Unlink') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No addresses linked.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <x-ui.modal wire:model="showAttachModal" class="max-w-lg">
            <div class="p-6 space-y-4">
                <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Attach Address') }}</h3>

                <div class="space-y-1">
                    <label class="block text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Address') }}</label>
                    <x-ui.select wire:model="attach_address_id">
                        <option value="0">{{ __('Select an address...') }}</option>
                        @foreach($availableAddresses as $addr)
                            <option value="{{ $addr->id }}">{{ $addr->label }} — {{ collect([$addr->line1, $addr->locality, $addr->country_iso])->filter()->implode(', ') }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="space-y-1">
                    <label class="block text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Kind') }}</label>
                    <x-ui.select wire:model="attach_kind">
                        <option value="headquarters">{{ __('Headquarters') }}</option>
                        <option value="billing">{{ __('Billing') }}</option>
                        <option value="shipping">{{ __('Shipping') }}</option>
                        <option value="branch">{{ __('Branch') }}</option>
                        <option value="other">{{ __('Other') }}</option>
                    </x-ui.select>
                </div>

                <x-ui.checkbox wire:model="attach_is_primary" label="{{ __('Primary Address') }}" />

                <x-ui.input wire:model="attach_priority" label="{{ __('Priority') }}" type="number" />

                <div class="flex items-center gap-4 pt-2">
                    <x-ui.button variant="primary" wire:click="attachAddress">{{ __('Attach') }}</x-ui.button>
                    <x-ui.button type="button" variant="ghost" wire:click="$set('showAttachModal', false)">{{ __('Cancel') }}</x-ui.button>
                </div>
            </div>
        </x-ui.modal>

        @if($company->children->isNotEmpty())
            <x-ui.card>
                <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-4">
                    {{ __('Subsidiaries') }}
                    <x-ui.badge>{{ $company->children->count() }}</x-ui.badge>
                </h3>

                <div class="overflow-x-auto -mx-card-inner px-card-inner">
                    <table class="min-w-full divide-y divide-border-default text-sm">
                        <thead class="bg-surface-subtle/80">
                            <tr>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Name') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Legal Entity Type') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Jurisdiction') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface-card divide-y divide-border-default">
                            @foreach($company->children as $child)
                                <tr wire:key="child-{{ $child->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-ink font-medium">
                                        <a href="{{ route('admin.companies.show', $child) }}" wire:navigate class="text-link hover:underline">{{ $child->name }}</a>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                        <x-ui.badge :variant="match($child->status) {
                                            'active' => 'success',
                                            'suspended' => 'danger',
                                            'pending' => 'warning',
                                            default => 'default',
                                        }">{{ ucfirst($child->status) }}</x-ui.badge>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $child->legal_entity_type ?? '-' }}</td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $child->jurisdiction ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endif

        <x-ui.card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted">
                    {{ __('Departments') }}
                    <x-ui.badge>{{ $company->departments->count() }}</x-ui.badge>
                </h3>
                <x-ui.button variant="ghost" size="sm" as="a" href="{{ route('admin.companies.departments', $company) }}" wire:navigate>
                    <x-icon name="heroicon-o-cog-6-tooth" class="w-4 h-4" />
                    {{ __('Manage') }}
                </x-ui.button>
            </div>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Department Type') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Category') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Head') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($company->departments as $dept)
                            <tr wire:key="dept-{{ $dept->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-ink font-medium">{{ $dept->type->name }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $dept->type->category ?? '-' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <x-ui.badge :variant="match($dept->status) {
                                        'active' => 'success',
                                        'suspended' => 'danger',
                                        'pending' => 'warning',
                                        default => 'default',
                                    }">{{ ucfirst($dept->status) }}</x-ui.badge>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $dept->head?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No departments.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <x-ui.card>
            @php
                $allRelationships = $company->relationships->map(fn ($r) => (object) [
                    'id' => $r->id,
                    'company' => $r->relatedCompany,
                    'type' => $r->type,
                    'direction' => __('Outgoing'),
                    'effective_from' => $r->effective_from,
                    'effective_to' => $r->effective_to,
                    'is_active' => $r->isActive(),
                ])->concat($company->inverseRelationships->map(fn ($r) => (object) [
                    'id' => $r->id,
                    'company' => $r->company,
                    'type' => $r->type,
                    'direction' => __('Incoming'),
                    'effective_from' => $r->effective_from,
                    'effective_to' => $r->effective_to,
                    'is_active' => $r->isActive(),
                ]));
            @endphp

            <div class="flex items-center justify-between mb-4">
                <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted">
                    {{ __('Relationships') }}
                    <x-ui.badge>{{ $allRelationships->count() }}</x-ui.badge>
                </h3>
                <x-ui.button variant="ghost" size="sm" as="a" href="{{ route('admin.companies.relationships', $company) }}" wire:navigate>
                    <x-icon name="heroicon-o-cog-6-tooth" class="w-4 h-4" />
                    {{ __('Manage') }}
                </x-ui.button>
            </div>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Related Company') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Type') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Direction') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Effective From') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Effective To') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($allRelationships as $rel)
                            <tr wire:key="rel-{{ $rel->id }}-{{ $rel->direction }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-ink font-medium">
                                    <a href="{{ route('admin.companies.show', $rel->company) }}" wire:navigate class="text-link hover:underline">{{ $rel->company->name }}</a>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $rel->type->name ?? '-' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $rel->direction }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $rel->effective_from?->format('Y-m-d') ?? '-' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $rel->effective_to?->format('Y-m-d') ?? '-' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <x-ui.badge :variant="$rel->is_active ? 'success' : 'default'">{{ $rel->is_active ? __('Active') : __('Ended') }}</x-ui.badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No relationships.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</div>
