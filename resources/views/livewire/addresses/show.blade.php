<?php

use App\Modules\Core\Address\Concerns\HasAddressGeoLookups;
use App\Modules\Core\Address\Models\Address;
use App\Modules\Core\Geonames\Models\Admin1;
use App\Modules\Core\Geonames\Models\Country;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new class extends Component
{
    use HasAddressGeoLookups;

    public Address $address;

    public ?string $country_iso = null;

    public ?string $admin1_code = null;

    public array $admin1Options = [];

    public ?string $postcode = null;

    public array $postcodeOptions = [];

    public ?string $locality = null;

    public array $localityOptions = [];

    public function mount(Address $address): void
    {
        $this->address = $address->load(['country', 'admin1']);
        $this->country_iso = $address->country_iso;
        $this->admin1_code = $address->admin1_code;
        $this->postcode = $address->postcode;
        $this->locality = $address->locality;

        if ($this->country_iso) {
            $this->admin1Options = $this->loadAdmin1ForCountry($this->country_iso);
        }
    }

    public function saveField(string $field, mixed $value): void
    {
        $rules = [
            'label' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'line1' => ['nullable', 'string'],
            'line2' => ['nullable', 'string'],
            'line3' => ['nullable', 'string'],
            'locality' => ['nullable', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'source_ref' => ['nullable', 'string', 'max:255'],
            'raw_input' => ['nullable', 'string'],
        ];

        if (! isset($rules[$field])) {
            return;
        }

        $validated = validator([$field => $value], [$field => $rules[$field]])->validate();

        $this->address->$field = $validated[$field];
        $this->address->save();
    }

    public function saveCountry(string $iso): void
    {
        if ($iso === '') {
            $this->address->country_iso = null;
        } else {
            $validated = validator(
                ['country_iso' => $iso],
                ['country_iso' => ['string', 'size:2']]
            )->validate();

            $this->address->country_iso = strtoupper($validated['country_iso']);
        }

        $this->address->save();
        $this->address->load(['country']);
    }

    public function updatedCountryIso($value): void
    {
        $this->saveCountry($value ?? '');
        $this->admin1_code = null;
        $this->postcode = null;
        $this->postcodeOptions = [];
        $this->locality = null;
        $this->localityOptions = [];

        if ($value) {
            $this->ensurePostcodesImported(strtoupper($value));
        }

        $this->admin1Options = $value ? $this->loadAdmin1ForCountry($value) : [];

        $this->address->admin1_code = null;
        $this->address->postcode = null;
        $this->address->locality = null;
        $this->address->save();
    }

    public function updatedPostcode($value): void
    {
        $this->address->postcode = $value;
        $this->address->save();

        if (! $this->address->country_iso || ! $value) {
            $this->localityOptions = [];

            return;
        }

        $result = $this->lookupLocalitiesByPostcode($this->address->country_iso, $value);

        if (! $result) {
            $this->localityOptions = [];

            return;
        }

        $this->localityOptions = $result['localities'];

        if (count($result['localities']) === 1) {
            $this->address->locality = $result['localities'][0]['value'];
            $this->locality = $result['localities'][0]['value'];
        }

        if (! $this->address->admin1_code && $result['admin1_code']) {
            $this->address->admin1_code = $result['admin1_code'];
            $this->admin1_code = $result['admin1_code'];
        }

        $this->address->save();
    }

    public function updatedAdmin1Code($value): void
    {
        $this->address->admin1_code = $value;
        $this->address->save();
    }

    public function updatedLocality($value): void
    {
        $this->address->locality = $value;
        $this->address->save();
    }

    public function saveVerificationStatus(string $status): void
    {
        if (! in_array($status, ['unverified', 'suggested', 'verified'])) {
            return;
        }

        $this->address->verification_status = $status;
        $this->address->save();
    }

    public function with(): array
    {
        $linkedEntities = DB::table('addressables')
            ->where('address_id', $this->address->id)
            ->get();

        $entities = $linkedEntities->map(function ($row) {
            $model = $row->addressable_type::find($row->addressable_id);

            return (object) [
                'model' => $model,
                'type' => class_basename($row->addressable_type),
                'kind' => json_decode($row->kind, true) ?? [],
                'is_primary' => $row->is_primary,
                'priority' => $row->priority,
                'valid_from' => $row->valid_from,
                'valid_to' => $row->valid_to,
            ];
        })->filter(fn ($e) => $e->model !== null);

        return [
            'linkedEntities' => $entities,
            'countryOptions' => Country::query()
                ->orderBy('country')
                ->get(['iso', 'country'])
                ->map(fn ($c) => ['value' => $c->iso, 'label' => $c->country])
                ->all(),
        ];
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Address Details') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Address Details')">
            <x-slot name="actions">
                <a href="{{ route('admin.addresses.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl hover:bg-surface-subtle text-link transition-colors">
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back to List') }}
                </a>
            </x-slot>
        </x-ui.page-header>

        <x-ui.card>
            <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-4">{{ __('Address Details') }}</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div x-data="{ editing: false, val: '{{ addslashes($address->label ?? '') }}' }">
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Label') }}</dt>
                    <dd class="text-sm text-ink">
                        <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                            <span x-text="val || '-'"></span>
                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                        <input
                            x-show="editing"
                            x-ref="input"
                            x-model="val"
                            @keydown.enter="editing = false; $wire.saveField('label', val)"
                            @keydown.escape="editing = false; val = '{{ addslashes($address->label ?? '') }}'"
                            @blur="editing = false; $wire.saveField('label', val)"
                            type="text"
                            class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                        />
                    </dd>
                </div>
                <div x-data="{ editing: false, val: '{{ addslashes($address->phone ?? '') }}' }">
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Phone') }}</dt>
                    <dd class="text-sm text-ink">
                        <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                            <span x-text="val || '-'"></span>
                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                        <input
                            x-show="editing"
                            x-ref="input"
                            x-model="val"
                            @keydown.enter="editing = false; $wire.saveField('phone', val)"
                            @keydown.escape="editing = false; val = '{{ addslashes($address->phone ?? '') }}'"
                            @blur="editing = false; $wire.saveField('phone', val)"
                            type="text"
                            class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                        />
                    </dd>
                </div>
                <div x-data="{ editing: false, val: '{{ addslashes($address->line1 ?? '') }}' }">
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Address Line 1') }}</dt>
                    <dd class="text-sm text-ink">
                        <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                            <span x-text="val || '-'"></span>
                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                        <input
                            x-show="editing"
                            x-ref="input"
                            x-model="val"
                            @keydown.enter="editing = false; $wire.saveField('line1', val)"
                            @keydown.escape="editing = false; val = '{{ addslashes($address->line1 ?? '') }}'"
                            @blur="editing = false; $wire.saveField('line1', val)"
                            type="text"
                            class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                        />
                    </dd>
                </div>
                <div x-data="{ editing: false, val: '{{ addslashes($address->line2 ?? '') }}' }">
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Address Line 2') }}</dt>
                    <dd class="text-sm text-ink">
                        <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                            <span x-text="val || '-'"></span>
                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                        <input
                            x-show="editing"
                            x-ref="input"
                            x-model="val"
                            @keydown.enter="editing = false; $wire.saveField('line2', val)"
                            @keydown.escape="editing = false; val = '{{ addslashes($address->line2 ?? '') }}'"
                            @blur="editing = false; $wire.saveField('line2', val)"
                            type="text"
                            class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                        />
                    </dd>
                </div>
                <div x-data="{ editing: false, val: '{{ addslashes($address->line3 ?? '') }}' }">
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Address Line 3') }}</dt>
                    <dd class="text-sm text-ink">
                        <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                            <span x-text="val || '-'"></span>
                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                        <input
                            x-show="editing"
                            x-ref="input"
                            x-model="val"
                            @keydown.enter="editing = false; $wire.saveField('line3', val)"
                            @keydown.escape="editing = false; val = '{{ addslashes($address->line3 ?? '') }}'"
                            @blur="editing = false; $wire.saveField('line3', val)"
                            type="text"
                            class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                        />
                    </dd>
                </div>
                <div>
                    <x-ui.combobox
                        wire:model.live="country_iso"
                        label="{{ __('Country') }}"
                        placeholder="{{ __('Search country...') }}"
                        :options="$countryOptions"
                    />
                </div>
                <div>
                    <x-ui.combobox
                        wire:model.live="admin1_code"
                        wire:key="show-admin1-{{ $country_iso ?? 'none' }}"
                        label="{{ __('State / Province') }}"
                        placeholder="{{ __('Search state...') }}"
                        :options="$admin1Options"
                    />
                </div>
                <div>
                    <x-ui.combobox
                        wire:model.live="postcode"
                        wire:key="show-postcode-{{ $country_iso ?? 'none' }}"
                        label="{{ __('Postcode') }}"
                        placeholder="{{ __('Search postcode...') }}"
                        :options="$postcodeOptions"
                        :editable="true"
                        search-url="{{ route('admin.addresses.postcodes.search') }}?country={{ $country_iso ?? '' }}"
                    />
                </div>
                <div>
                    <x-ui.combobox
                        wire:model.live="locality"
                        label="{{ __('Locality') }}"
                        placeholder="{{ __('City / town') }}"
                        :options="$localityOptions"
                        :editable="true"
                    />
                </div>
                <div x-data="{ editing: false, val: '{{ $address->verification_status }}' }">
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Verification Status') }}</dt>
                    <dd class="mt-0.5">
                        <div x-show="!editing" @click="editing = true" class="group flex items-center gap-1.5 cursor-pointer">
                            <x-ui.badge :variant="match($address->verification_status) {
                                'verified' => 'success',
                                'suggested' => 'warning',
                                default => 'default',
                            }">{{ ucfirst($address->verification_status) }}</x-ui.badge>
                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                        <select
                            x-show="editing"
                            x-model="val"
                            @change="editing = false; $wire.saveVerificationStatus(val)"
                            @keydown.escape="editing = false; val = '{{ $address->verification_status }}'"
                            @blur="editing = false"
                            class="px-2 py-1 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                        >
                            <option value="unverified">{{ __('Unverified') }}</option>
                            <option value="suggested">{{ __('Suggested') }}</option>
                            <option value="verified">{{ __('Verified') }}</option>
                        </select>
                    </dd>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-1">{{ __('Provenance') }}</h3>
            <p class="text-xs text-muted mb-4">{{ __('Tracks where this address came from and how it was processed — useful for auditing data quality and imports.') }}</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div x-data="{ editing: false, val: '{{ addslashes($address->source ?? '') }}' }">
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Source') }}</dt>
                    <dd class="text-sm text-ink">
                        <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                            <span x-text="val || '-'"></span>
                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                        <input
                            x-show="editing"
                            x-ref="input"
                            x-model="val"
                            @keydown.enter="editing = false; $wire.saveField('source', val)"
                            @keydown.escape="editing = false; val = '{{ addslashes($address->source ?? '') }}'"
                            @blur="editing = false; $wire.saveField('source', val)"
                            type="text"
                            placeholder="{{ __('manual, scan, paste, import_api') }}"
                            class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                        />
                    </dd>
                </div>
                <div x-data="{ editing: false, val: '{{ addslashes($address->source_ref ?? '') }}' }">
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Source Reference') }}</dt>
                    <dd class="text-sm text-ink">
                        <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                            <span x-text="val || '-'"></span>
                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                        <input
                            x-show="editing"
                            x-ref="input"
                            x-model="val"
                            @keydown.enter="editing = false; $wire.saveField('source_ref', val)"
                            @keydown.escape="editing = false; val = '{{ addslashes($address->source_ref ?? '') }}'"
                            @blur="editing = false; $wire.saveField('source_ref', val)"
                            type="text"
                            class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                        />
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Parser Version') }}</dt>
                    <dd class="text-sm text-ink">{{ $address->parser_version ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Parse Confidence') }}</dt>
                    <dd class="text-sm text-ink">{{ $address->parse_confidence !== null ? $address->parse_confidence : '-' }}</dd>
                </div>
            </div>

            @if($address->raw_input)
                <div class="mt-4">
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Raw Input') }}</dt>
                    <dd class="mt-1">
                        <pre class="text-sm text-ink bg-surface-subtle rounded-2xl p-4 overflow-x-auto">{{ $address->raw_input }}</pre>
                    </dd>
                </div>
            @endif
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-1">{{ __('Linked Entities') }}</h3>
            <p class="text-xs text-muted mb-4">{{ __('Companies, employees, or other records that use this address. One address can be shared by multiple entities with different roles (e.g., billing, shipping).') }}</p>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Entity Type') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Name') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Kind') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Primary') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Priority') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Valid From') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Valid To') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($linkedEntities as $entity)
                            <tr wire:key="entity-{{ $entity->type }}-{{ $entity->model->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $entity->type }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    @if($entity->type === 'Company')
                                        <a href="{{ route('admin.companies.show', $entity->model) }}" wire:navigate class="text-link hover:underline">{{ $entity->model->name }}</a>
                                    @elseif($entity->type === 'Employee')
                                        <a href="{{ route('admin.employees.show', $entity->model) }}" wire:navigate class="text-link hover:underline">{{ $entity->model->full_name ?? $entity->model->id }}</a>
                                    @else
                                        {{ $entity->model->name ?? $entity->model->id }}
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    @if(is_array($entity->kind) && count($entity->kind) > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($entity->kind as $k)
                                                <x-ui.badge variant="default">{{ ucfirst($k) }}</x-ui.badge>
                                            @endforeach
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $entity->is_primary ? __('Yes') : __('No') }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $entity->priority ?? '-' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $entity->valid_from ?? '-' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $entity->valid_to ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No linked entities.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</div>
