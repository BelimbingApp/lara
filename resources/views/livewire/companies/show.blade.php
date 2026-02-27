<?php

use App\Modules\Core\Address\Models\Address;
use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\Company\Models\LegalEntityType;
use App\Modules\Core\Address\Concerns\HasAddressGeoLookups;
use App\Modules\Core\Geonames\Models\Admin1 as GeonamesAdmin1;
use App\Modules\Core\Geonames\Models\Country;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component
{
    use HasAddressGeoLookups;

    public Company $company;

    public int $attach_address_id = 0;

    public array $attach_kind = [];

    public bool $attach_is_primary = false;

    public int $attach_priority = 0;

    public bool $showAttachModal = false;

    public bool $showCreateAddressModal = false;

    public string $new_address_label = '';

    public ?string $new_address_phone = null;

    public ?string $new_address_line1 = null;

    public ?string $new_address_line2 = null;

    public ?string $new_address_line3 = null;

    public ?string $new_address_locality = null;

    public ?string $new_address_postcode = null;

    public ?string $new_address_country_iso = null;

    public array $new_address_kind = [];

    public bool $new_address_is_primary = false;

    public int $new_address_priority = 0;

    public ?string $new_address_admin1_code = null;

    public array $new_address_admin1_options = [];

    public array $new_address_postcode_options = [];

    public array $new_address_locality_options = [];

    public bool $new_address_admin1_is_auto = false;

    public bool $new_address_locality_is_auto = false;

    public bool $showEditAddressModal = false;

    public ?int $edit_address_id = null;

    public string $edit_address_label = '';

    public ?string $edit_address_phone = null;

    public ?string $edit_address_line1 = null;

    public ?string $edit_address_line2 = null;

    public ?string $edit_address_line3 = null;

    public ?string $edit_address_locality = null;

    public ?string $edit_address_postcode = null;

    public ?string $edit_address_country_iso = null;

    public ?string $edit_address_admin1_code = null;

    public array $edit_address_admin1_options = [];

    public array $edit_address_postcode_options = [];

    public array $edit_address_locality_options = [];

    public bool $edit_address_admin1_is_auto = false;

    public bool $edit_address_locality_is_auto = false;

    public function mount(Company $company): void
    {
        $this->company = $company->load([
            'parent',
            'legalEntityType',
            'addresses',
            'children.legalEntityType',
            'departments.type',
            'relationships.type',
            'relationships.relatedCompany',
            'inverseRelationships.type',
            'inverseRelationships.company',
            'externalAccesses.user',
        ]);
    }

    public function saveField(string $field, mixed $value): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'legal_entity_type_id' => ['nullable', 'integer', 'exists:company_legal_entity_types,id'],
            'jurisdiction' => ['nullable', 'string', 'max:2', 'exists:geonames_countries,iso'],
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
        $allowed = ['is_primary', 'priority'];
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

    public function saveAddressKinds(int $addressId, array $kinds): void
    {
        $valid = ['headquarters', 'billing', 'shipping', 'branch', 'other'];
        $kinds = array_values(array_intersect($kinds, $valid));

        $this->company->addresses()->updateExistingPivot($addressId, ['kind' => $kinds]);
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

    public function updatedNewAddressCountryIso($value): void
    {
        $this->new_address_admin1_code = null;
        $this->new_address_admin1_is_auto = false;
        $this->new_address_admin1_options = [];
        $this->new_address_postcode = null;
        $this->new_address_postcode_options = [];
        $this->new_address_locality = null;
        $this->new_address_locality_is_auto = false;
        $this->new_address_locality_options = [];

        if ($value) {
            $this->ensurePostcodesImported(strtoupper($value));
            $this->new_address_admin1_options = $this->loadAdmin1ForCountry($value);
        }
    }

    public function updatedNewAddressPostcode($value): void
    {
        if (! $this->new_address_country_iso || ! $value) {
            $this->new_address_locality_options = [];
            return;
        }

        if ($this->new_address_admin1_is_auto) {
            $this->new_address_admin1_code = null;
            $this->new_address_admin1_is_auto = false;
        }
        if ($this->new_address_locality_is_auto) {
            $this->new_address_locality = null;
            $this->new_address_locality_is_auto = false;
        }

        $result = $this->lookupLocalitiesByPostcode($this->new_address_country_iso, $value);

        if (! $result) {
            $this->new_address_locality_options = [];
            return;
        }

        $this->new_address_locality_options = $result['localities'];

        if (count($result['localities']) === 1) {
            $this->new_address_locality = $result['localities'][0]['value'];
            $this->new_address_locality_is_auto = true;
        }

        if ($result['admin1_code']) {
            $this->new_address_admin1_code = $result['admin1_code'];
            $this->new_address_admin1_is_auto = true;

            if (empty($this->new_address_admin1_options)) {
                $this->new_address_admin1_options = $this->loadAdmin1ForCountry($this->new_address_country_iso);
            }
        }
    }

    public function updatedNewAddressAdmin1Code(): void
    {
        $this->new_address_admin1_is_auto = false;
    }

    public function updatedNewAddressLocality(): void
    {
        $this->new_address_locality_is_auto = false;
    }

    public function createAndAttachAddress(): void
    {
        $validated = $this->validate([
            'new_address_label' => ['nullable', 'string', 'max:255'],
            'new_address_phone' => ['nullable', 'string', 'max:255'],
            'new_address_line1' => ['nullable', 'string'],
            'new_address_line2' => ['nullable', 'string'],
            'new_address_line3' => ['nullable', 'string'],
            'new_address_locality' => ['nullable', 'string', 'max:255'],
            'new_address_postcode' => ['nullable', 'string', 'max:255'],
            'new_address_country_iso' => ['nullable', 'string', 'size:2'],
            'new_address_admin1_code' => ['nullable', 'string', 'max:20'],
            'new_address_kind' => ['required', 'array', 'min:1'],
            'new_address_kind.*' => ['string', 'in:headquarters,billing,shipping,branch,other'],
            'new_address_is_primary' => ['boolean'],
            'new_address_priority' => ['integer'],
        ]);

        $address = Address::query()->create([
            'label' => $validated['new_address_label'],
            'phone' => $validated['new_address_phone'],
            'line1' => $validated['new_address_line1'],
            'line2' => $validated['new_address_line2'],
            'line3' => $validated['new_address_line3'],
            'locality' => $validated['new_address_locality'],
            'postcode' => $validated['new_address_postcode'],
            'country_iso' => $validated['new_address_country_iso'] ? strtoupper($validated['new_address_country_iso']) : null,
            'admin1_code' => $validated['new_address_admin1_code'],
            'source' => 'manual',
            'verification_status' => 'unverified',
        ]);

        $this->company->addresses()->attach($address->id, [
            'kind' => $validated['new_address_kind'],
            'is_primary' => $validated['new_address_is_primary'],
            'priority' => $validated['new_address_priority'],
            'valid_from' => now()->toDateString(),
        ]);

        $this->company->load('addresses');
        $this->showCreateAddressModal = false;
        $this->reset([
            'new_address_label', 'new_address_phone', 'new_address_line1',
            'new_address_line2', 'new_address_line3', 'new_address_locality',
            'new_address_postcode', 'new_address_country_iso', 'new_address_kind',
            'new_address_is_primary', 'new_address_priority',
            'new_address_admin1_code', 'new_address_admin1_options',
            'new_address_postcode_options', 'new_address_locality_options',
            'new_address_admin1_is_auto', 'new_address_locality_is_auto',
        ]);
        Session::flash('success', __('Address created and attached.'));
    }

    public function editAddress(int $addressId): void
    {
        $address = Address::query()->findOrFail($addressId);

        $this->edit_address_id = $address->id;
        $this->edit_address_label = $address->label ?? '';
        $this->edit_address_phone = $address->phone;
        $this->edit_address_line1 = $address->line1;
        $this->edit_address_line2 = $address->line2;
        $this->edit_address_line3 = $address->line3;
        $this->edit_address_locality = $address->locality;
        $this->edit_address_postcode = $address->postcode;
        $this->edit_address_country_iso = $address->country_iso;
        $this->edit_address_admin1_code = $address->admin1_code;
        $this->edit_address_admin1_options = $address->country_iso
            ? $this->loadAdmin1ForCountry($address->country_iso)
            : [];
        $this->edit_address_postcode_options = [];
        $localityLookup = ($address->country_iso && $address->postcode)
            ? $this->lookupLocalitiesByPostcode($address->country_iso, $address->postcode)
            : null;
        $this->edit_address_locality_options = $localityLookup ? $localityLookup['localities'] : [];

        $this->showEditAddressModal = true;
    }

    public function updatedEditAddressCountryIso($value): void
    {
        $this->edit_address_admin1_code = null;
        $this->edit_address_admin1_is_auto = false;
        $this->edit_address_admin1_options = [];
        $this->edit_address_postcode = null;
        $this->edit_address_postcode_options = [];
        $this->edit_address_locality = null;
        $this->edit_address_locality_is_auto = false;
        $this->edit_address_locality_options = [];

        if ($value) {
            $this->ensurePostcodesImported(strtoupper($value));
            $this->edit_address_admin1_options = $this->loadAdmin1ForCountry($value);
        }
    }

    public function updatedEditAddressPostcode($value): void
    {
        if (! $this->edit_address_country_iso || ! $value) {
            $this->edit_address_locality_options = [];
            return;
        }

        if ($this->edit_address_admin1_is_auto) {
            $this->edit_address_admin1_code = null;
            $this->edit_address_admin1_is_auto = false;
        }
        if ($this->edit_address_locality_is_auto) {
            $this->edit_address_locality = null;
            $this->edit_address_locality_is_auto = false;
        }

        $result = $this->lookupLocalitiesByPostcode($this->edit_address_country_iso, $value);

        if (! $result) {
            $this->edit_address_locality_options = [];
            return;
        }

        $this->edit_address_locality_options = $result['localities'];

        if (count($result['localities']) === 1) {
            $this->edit_address_locality = $result['localities'][0]['value'];
            $this->edit_address_locality_is_auto = true;
        }

        if ($result['admin1_code']) {
            $this->edit_address_admin1_code = $result['admin1_code'];
            $this->edit_address_admin1_is_auto = true;

            if (empty($this->edit_address_admin1_options)) {
                $this->edit_address_admin1_options = $this->loadAdmin1ForCountry($this->edit_address_country_iso);
            }
        }
    }

    public function updatedEditAddressAdmin1Code(): void
    {
        $this->edit_address_admin1_is_auto = false;
    }

    public function updatedEditAddressLocality(): void
    {
        $this->edit_address_locality_is_auto = false;
    }

    public function updateAddress(): void
    {
        $validated = $this->validate([
            'edit_address_label' => ['nullable', 'string', 'max:255'],
            'edit_address_phone' => ['nullable', 'string', 'max:255'],
            'edit_address_line1' => ['nullable', 'string'],
            'edit_address_line2' => ['nullable', 'string'],
            'edit_address_line3' => ['nullable', 'string'],
            'edit_address_locality' => ['nullable', 'string', 'max:255'],
            'edit_address_postcode' => ['nullable', 'string', 'max:255'],
            'edit_address_country_iso' => ['nullable', 'string', 'size:2'],
            'edit_address_admin1_code' => ['nullable', 'string', 'max:20'],
        ]);

        $address = Address::query()->findOrFail($this->edit_address_id);
        $address->update([
            'label' => $validated['edit_address_label'],
            'phone' => $validated['edit_address_phone'],
            'line1' => $validated['edit_address_line1'],
            'line2' => $validated['edit_address_line2'],
            'line3' => $validated['edit_address_line3'],
            'locality' => $validated['edit_address_locality'],
            'postcode' => $validated['edit_address_postcode'],
            'country_iso' => $validated['edit_address_country_iso'] ? strtoupper($validated['edit_address_country_iso']) : null,
            'admin1_code' => $validated['edit_address_admin1_code'],
        ]);

        $this->company->load('addresses');
        $this->showEditAddressModal = false;
        $this->reset([
            'edit_address_id', 'edit_address_label', 'edit_address_phone',
            'edit_address_line1', 'edit_address_line2', 'edit_address_line3',
            'edit_address_locality', 'edit_address_postcode', 'edit_address_country_iso',
            'edit_address_admin1_code', 'edit_address_admin1_options',
            'edit_address_postcode_options', 'edit_address_locality_options',
            'edit_address_admin1_is_auto', 'edit_address_locality_is_auto',
        ]);
        Session::flash('success', __('Address updated.'));
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
            'legalEntityTypes' => LegalEntityType::query()->active()->orderBy('name')->get(['id', 'code', 'name']),
            'countries' => Country::query()->orderBy('country')->get(['iso', 'country']),
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

        @if ($company->isLicensee())
            <x-ui.alert variant="info">{{ __('This is the licensee company operating this Belimbing instance.') }}</x-ui.alert>
        @endif

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
                    <div x-data="{ editing: false, val: '{{ addslashes($company->code ?? '') }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Code') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span class="font-mono" x-text="val || '-'"></span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <input
                                x-show="editing"
                                x-ref="input"
                                x-model="val"
                                @keydown.enter="editing = false; $wire.saveField('code', val)"
                                @keydown.escape="editing = false; val = '{{ addslashes($company->code ?? '') }}'"
                                @blur="editing = false; $wire.saveField('code', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm font-mono border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
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
                    <div x-data="{ editing: false, val: '{{ $company->legal_entity_type_id ?? '' }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Legal Entity Type') }}</dt>
                        <dd class="mt-0.5">
                            <div x-show="!editing" @click="editing = true" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span class="text-sm text-ink">{{ $company->legalEntityType?->name ?? '-' }}</span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <select
                                x-show="editing"
                                x-model="val"
                                @change="editing = false; $wire.saveField('legal_entity_type_id', val || null)"
                                @keydown.escape="editing = false; val = '{{ $company->legal_entity_type_id ?? '' }}'"
                                @blur="editing = false"
                                class="px-2 py-1 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            >
                                <option value="">{{ __('None') }}</option>
                                @foreach($legalEntityTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
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
                    <div x-data="{ editing: false, val: '{{ $company->jurisdiction ?? '' }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Jurisdiction') }}</dt>
                        <dd class="mt-0.5">
                            <div x-show="!editing" @click="editing = true" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span class="text-sm text-ink">
                                    @if($company->jurisdiction)
                                        {{ $countries->firstWhere('iso', $company->jurisdiction)?->country ?? $company->jurisdiction }}
                                        <span class="text-muted">({{ $company->jurisdiction }})</span>
                                    @else
                                        -
                                    @endif
                                </span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <select
                                x-show="editing"
                                x-model="val"
                                @change="editing = false; $wire.saveField('jurisdiction', val || null)"
                                @keydown.escape="editing = false; val = '{{ $company->jurisdiction ?? '' }}'"
                                @blur="editing = false"
                                class="px-2 py-1 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            >
                                <option value="">{{ __('None') }}</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->iso }}">{{ $country->country }} ({{ $country->iso }})</option>
                                @endforeach
                            </select>
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
                <div class="flex items-center gap-2">
                    <x-ui.button variant="primary" size="sm" wire:click="$set('showCreateAddressModal', true)">
                        <x-icon name="heroicon-o-plus" class="w-4 h-4" />
                        {{ __('Create & Attach') }}
                    </x-ui.button>
                    <x-ui.button variant="ghost" size="sm" wire:click="$set('showAttachModal', true)">
                        <x-icon name="heroicon-o-link" class="w-4 h-4" />
                        {{ __('Attach Existing') }}
                    </x-ui.button>
                </div>
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
                                    <button wire:click="editAddress({{ $address->id }})" class="text-link hover:underline cursor-pointer">{{ $address->label ?? '-' }}</button>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ collect([$address->line1, $address->locality, $address->country_iso])->filter()->implode(', ') }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted"
                                    x-data="{ editing: false, selected: @js($address->pivot->kind ?? []) }"
                                >
                                    <div x-show="!editing" @click="editing = true" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                        <div class="flex flex-wrap gap-1">
                                            <template x-for="k in selected" :key="k">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-surface-subtle text-ink border border-border-default" x-text="k.charAt(0).toUpperCase() + k.slice(1)"></span>
                                            </template>
                                            <span x-show="selected.length === 0" class="text-muted">-</span>
                                        </div>
                                        <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity shrink-0" />
                                    </div>
                                    <div x-show="editing" class="space-y-1">
                                        @foreach(['headquarters', 'billing', 'shipping', 'branch', 'other'] as $kindOption)
                                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                                <input type="checkbox" value="{{ $kindOption }}" x-model="selected" class="rounded border-border-input text-accent focus:ring-accent" />
                                                {{ __(ucfirst($kindOption)) }}
                                            </label>
                                        @endforeach
                                        <div class="flex items-center gap-2 mt-1">
                                            <button @click="$wire.saveAddressKinds({{ $address->id }}, selected); editing = false" class="px-2 py-0.5 text-xs font-medium rounded bg-accent text-accent-on hover:bg-accent-hover transition-colors">{{ __('Save') }}</button>
                                            <button @click="editing = false; selected = @js($address->pivot->kind ?? [])" class="px-2 py-0.5 text-xs font-medium rounded hover:bg-surface-subtle text-muted transition-colors">{{ __('Cancel') }}</button>
                                        </div>
                                    </div>
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
                                    <div class="inline-flex flex-col items-end gap-1">
                                        <a
                                            href="{{ route('admin.addresses.show', $address) }}"
                                            wire:navigate
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg hover:bg-surface-subtle text-link transition-colors"
                                        >
                                            <x-icon name="heroicon-o-arrow-top-right-on-square" class="w-4 h-4" />
                                            {{ __('Open') }}
                                        </a>
                                        <button
                                            wire:click="unlinkAddress({{ $address->id }})"
                                            wire:confirm="{{ __('Are you sure you want to unlink this address?') }}"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg hover:bg-status-danger-subtle text-status-danger transition-colors"
                                        >
                                            <x-icon name="heroicon-o-link-slash" class="w-4 h-4" />
                                            {{ __('Unlink') }}
                                        </button>
                                    </div>
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
                    <div class="flex flex-wrap gap-x-4 gap-y-1">
                        @foreach(['headquarters', 'billing', 'shipping', 'branch', 'other'] as $kindOption)
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" value="{{ $kindOption }}" wire:model="attach_kind" class="rounded border-border-input text-accent focus:ring-accent" />
                                {{ __(ucfirst($kindOption)) }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <x-ui.checkbox wire:model="attach_is_primary" label="{{ __('Primary Address') }}" />

                <div>
                    <x-ui.input wire:model="attach_priority" label="{{ __('Priority') }}" type="number" />
                    <p class="text-xs text-muted mt-1">{{ __('Lower number = higher priority. Used to order addresses of the same kind (0 = top).') }}</p>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <x-ui.button variant="primary" wire:click="attachAddress">{{ __('Attach') }}</x-ui.button>
                    <x-ui.button type="button" variant="ghost" wire:click="$set('showAttachModal', false)">{{ __('Cancel') }}</x-ui.button>
                </div>
            </div>
        </x-ui.modal>

        <x-ui.modal wire:model="showCreateAddressModal" class="max-w-lg">
            <div class="p-6 space-y-4">
                <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Create & Attach Address') }}</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.input wire:model="new_address_label" label="{{ __('Label') }}" type="text" placeholder="{{ __('HQ, Warehouse, etc.') }}" :error="$errors->first('new_address_label')" />
                    <x-ui.input wire:model="new_address_phone" label="{{ __('Phone') }}" type="text" placeholder="{{ __('Contact number') }}" :error="$errors->first('new_address_phone')" />
                </div>

                <x-ui.input wire:model="new_address_line1" label="{{ __('Address Line 1') }}" type="text" placeholder="{{ __('Street and number') }}" :error="$errors->first('new_address_line1')" />
                <x-ui.input wire:model="new_address_line2" label="{{ __('Address Line 2') }}" type="text" placeholder="{{ __('Building, suite (optional)') }}" :error="$errors->first('new_address_line2')" />
                <x-ui.input wire:model="new_address_line3" label="{{ __('Address Line 3') }}" type="text" placeholder="{{ __('Additional detail (optional)') }}" :error="$errors->first('new_address_line3')" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.combobox
                        wire:model.live="new_address_country_iso"
                        label="{{ __('Country') }}"
                        placeholder="{{ __('Search country...') }}"
                        :options="$countries->map(fn($c) => ['value' => $c->iso, 'label' => $c->country])->all()"
                        :error="$errors->first('new_address_country_iso')"
                    />

                    <x-ui.combobox
                        wire:model.live="new_address_admin1_code"
                        wire:key="modal-admin1-{{ $new_address_country_iso ?? 'none' }}"
                        label="{{ __('State / Province') }}"
                        :hint="$new_address_admin1_is_auto ? __('(from postcode)') : null"
                        placeholder="{{ __('Search state...') }}"
                        :options="$new_address_admin1_options"
                        :error="$errors->first('new_address_admin1_code')"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.combobox
                        wire:model.live="new_address_postcode"
                        wire:key="new-postcode-{{ $new_address_country_iso ?? 'none' }}"
                        label="{{ __('Postcode') }}"
                        placeholder="{{ __('Search postcode...') }}"
                        :options="$new_address_postcode_options"
                        :editable="true"
                        search-url="{{ route('admin.addresses.postcodes.search') }}?country={{ $new_address_country_iso ?? '' }}"
                        :error="$errors->first('new_address_postcode')"
                    />

                    <x-ui.combobox
                        wire:model.live="new_address_locality"
                        label="{{ __('Locality') }}"
                        :hint="$new_address_locality_is_auto ? __('(from postcode)') : null"
                        placeholder="{{ __('City / town') }}"
                        :options="$new_address_locality_options"
                        :editable="true"
                        :error="$errors->first('new_address_locality')"
                    />
                </div>

                <div class="border-t border-border-default pt-4">
                    <h4 class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-3">{{ __('Link Settings') }}</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="block text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Kind') }}</label>
                            <div class="flex flex-wrap gap-x-4 gap-y-1">
                                @foreach(['headquarters', 'billing', 'shipping', 'branch', 'other'] as $kindOption)
                                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                                        <input type="checkbox" value="{{ $kindOption }}" wire:model="new_address_kind" class="rounded border-border-input text-accent focus:ring-accent" />
                                        {{ __(ucfirst($kindOption)) }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <x-ui.input wire:model="new_address_priority" label="{{ __('Priority') }}" type="number" />
                            <p class="text-xs text-muted mt-1">{{ __('Lower number = higher priority. Used to order addresses of the same kind (0 = top).') }}</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <x-ui.checkbox wire:model="new_address_is_primary" label="{{ __('Primary Address') }}" />
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <x-ui.button variant="primary" wire:click="createAndAttachAddress">{{ __('Create & Attach') }}</x-ui.button>
                    <x-ui.button type="button" variant="ghost" wire:click="$set('showCreateAddressModal', false)">{{ __('Cancel') }}</x-ui.button>
                </div>
            </div>
        </x-ui.modal>

        <x-ui.modal wire:model="showEditAddressModal" class="max-w-lg">
            <div class="p-6 space-y-4">
                <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Edit Address') }}</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.input wire:model="edit_address_label" label="{{ __('Label') }}" type="text" placeholder="{{ __('HQ, Warehouse, etc.') }}" :error="$errors->first('edit_address_label')" />
                    <x-ui.input wire:model="edit_address_phone" label="{{ __('Phone') }}" type="text" placeholder="{{ __('Contact number') }}" :error="$errors->first('edit_address_phone')" />
                </div>

                <x-ui.input wire:model="edit_address_line1" label="{{ __('Address Line 1') }}" type="text" placeholder="{{ __('Street and number') }}" :error="$errors->first('edit_address_line1')" />
                <x-ui.input wire:model="edit_address_line2" label="{{ __('Address Line 2') }}" type="text" placeholder="{{ __('Building, suite (optional)') }}" :error="$errors->first('edit_address_line2')" />
                <x-ui.input wire:model="edit_address_line3" label="{{ __('Address Line 3') }}" type="text" placeholder="{{ __('Additional detail (optional)') }}" :error="$errors->first('edit_address_line3')" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.combobox
                        wire:model.live="edit_address_country_iso"
                        label="{{ __('Country') }}"
                        placeholder="{{ __('Search country...') }}"
                        :options="$countries->map(fn($c) => ['value' => $c->iso, 'label' => $c->country])->all()"
                        :error="$errors->first('edit_address_country_iso')"
                    />

                    <x-ui.combobox
                        wire:model.live="edit_address_admin1_code"
                        wire:key="edit-admin1-{{ $edit_address_country_iso ?? 'none' }}"
                        label="{{ __('State / Province') }}"
                        :hint="$edit_address_admin1_is_auto ? __('(from postcode)') : null"
                        placeholder="{{ __('Search state...') }}"
                        :options="$edit_address_admin1_options"
                        :error="$errors->first('edit_address_admin1_code')"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.combobox
                        wire:model.live="edit_address_postcode"
                        wire:key="edit-postcode-{{ $edit_address_country_iso ?? 'none' }}"
                        label="{{ __('Postcode') }}"
                        placeholder="{{ __('Search postcode...') }}"
                        :options="$edit_address_postcode_options"
                        :editable="true"
                        search-url="{{ route('admin.addresses.postcodes.search') }}?country={{ $edit_address_country_iso ?? '' }}"
                        :error="$errors->first('edit_address_postcode')"
                    />

                    <x-ui.combobox
                        wire:model.live="edit_address_locality"
                        label="{{ __('Locality') }}"
                        :hint="$edit_address_locality_is_auto ? __('(from postcode)') : null"
                        placeholder="{{ __('City / town') }}"
                        :options="$edit_address_locality_options"
                        :editable="true"
                        :error="$errors->first('edit_address_locality')"
                    />
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <x-ui.button variant="primary" wire:click="updateAddress">{{ __('Save') }}</x-ui.button>
                    <x-ui.button type="button" variant="ghost" wire:click="$set('showEditAddressModal', false)">{{ __('Cancel') }}</x-ui.button>
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
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $child->legalEntityType?->name ?? '-' }}</td>
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

        <x-ui.card>
            <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted">
                {{ __('External Accesses') }}
                <x-ui.badge>{{ $company->externalAccesses->count() }}</x-ui.badge>
            </h3>
            <p class="text-xs text-muted mt-0.5 mb-4">{{ __('Portal access granted by this company to external users. Allows customers or suppliers to view shared data.') }}</p>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('User') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Permissions') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Granted At') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Expires At') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($company->externalAccesses as $access)
                            <tr wire:key="access-{{ $access->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    @if($access->user)
                                        <a href="{{ route('admin.users.show', $access->user) }}" wire:navigate class="text-link hover:underline">{{ $access->user->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    @if($access->permissions)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($access->permissions as $permission)
                                                <x-ui.badge variant="default">{{ $permission }}</x-ui.badge>
                                            @endforeach
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    @if($access->isValid())
                                        <x-ui.badge variant="success">{{ __('Valid') }}</x-ui.badge>
                                    @elseif($access->hasExpired())
                                        <x-ui.badge variant="danger">{{ __('Expired') }}</x-ui.badge>
                                    @elseif($access->isPending())
                                        <x-ui.badge variant="warning">{{ __('Pending') }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="default">{{ __('Inactive') }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $access->access_granted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $access->access_expires_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No external accesses.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</div>
