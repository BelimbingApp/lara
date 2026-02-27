<?php

use App\Modules\Core\Address\Livewire\AbstractAddressForm;
use App\Modules\Core\Address\Models\Address;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends AbstractAddressForm
{
    public ?string $label = null;

    public ?string $phone = null;

    public ?string $line1 = null;

    public ?string $line2 = null;

    public ?string $line3 = null;

    public ?string $raw_input = null;

    public ?string $source = 'manual';

    public ?string $source_ref = null;

    public ?string $parser_version = null;

    public ?string $parse_confidence = null;

    public string $verification_status = 'unverified';

    public function with(): array
    {
        return [
            'countryOptions' => $this->loadCountryOptionsForCombobox(),
        ];
    }

    public function store(): void
    {
        $validated = $this->validate($this->rules());

        if ($validated['country_iso']) {
            $validated['country_iso'] = strtoupper($validated['country_iso']);
        }

        $validated['parse_confidence'] = $validated['parse_confidence'] !== null
            ? (float) $validated['parse_confidence']
            : null;

        Address::query()->create($validated);

        Session::flash('success', __('Address created successfully.'));

        $this->redirect(route('admin.addresses.index'), navigate: true);
    }

    protected function rules(): array
    {
        return array_merge(Address::fieldRules(), [
            'country_iso' => ['nullable', 'string', 'size:2'],
            'admin1_code' => ['nullable', 'string', 'max:20'],
            'parser_version' => ['nullable', 'string', 'max:255'],
            'parse_confidence' => ['nullable', 'numeric', 'between:0,1'],
            'verification_status' => ['required', 'in:unverified,suggested,verified'],
        ]);
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Create Address') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Create Address')" :subtitle="__('Add a structured address record')">
            <x-slot name="actions">
                <a href="{{ route('admin.addresses.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl hover:bg-surface-subtle text-link transition-colors">
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back') }}
                </a>
            </x-slot>
        </x-ui.page-header>

        <x-ui.card>
            <form wire:submit="store" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.input
                        wire:model="label"
                        label="{{ __('Label') }}"
                        type="text"
                        placeholder="{{ __('HQ, Warehouse, Billing, etc.') }}"
                        :error="$errors->first('label')"
                    />

                    <x-ui.input
                        wire:model="phone"
                        label="{{ __('Phone') }}"
                        type="text"
                        placeholder="{{ __('Contact number for this location') }}"
                        :error="$errors->first('phone')"
                    />
                </div>

                <div class="space-y-4">
                    <x-ui.input
                        wire:model="line1"
                        label="{{ __('Address Line 1') }}"
                        type="text"
                        placeholder="{{ __('Street and number') }}"
                        :error="$errors->first('line1')"
                    />

                    <x-ui.input
                        wire:model="line2"
                        label="{{ __('Address Line 2') }}"
                        type="text"
                        placeholder="{{ __('Building, suite, floor (optional)') }}"
                        :error="$errors->first('line2')"
                    />

                    <x-ui.input
                        wire:model="line3"
                        label="{{ __('Address Line 3') }}"
                        type="text"
                        placeholder="{{ __('Additional address detail (optional)') }}"
                        :error="$errors->first('line3')"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.combobox
                        wire:model.live="country_iso"
                        label="{{ __('Country') }}"
                        placeholder="{{ __('Search country...') }}"
                        :options="$countryOptions"
                        :error="$errors->first('country_iso')"
                    />

                    <x-ui.combobox
                        wire:model.live="admin1_code"
                        wire:key="create-admin1-{{ $country_iso ?? 'none' }}"
                        label="{{ __('State / Province') }}"
                        :hint="$admin1IsAuto ? __('(from postcode)') : null"
                        placeholder="{{ __('Search state...') }}"
                        :options="$admin1Options"
                        :error="$errors->first('admin1_code')"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.combobox
                        wire:model.live="postcode"
                        wire:key="create-postcode-{{ $country_iso ?? 'none' }}"
                        label="{{ __('Postcode') }}"
                        placeholder="{{ __('Search postcode...') }}"
                        :options="$postcodeOptions"
                        :editable="true"
                        search-url="{{ route('admin.addresses.postcodes.search') }}?country={{ $country_iso ?? '' }}"
                        :error="$errors->first('postcode')"
                    />

                    <x-ui.combobox
                        wire:model.live="locality"
                        label="{{ __('Locality') }}"
                        :hint="$localityIsAuto ? __('(from postcode)') : null"
                        placeholder="{{ __('City / town') }}"
                        :options="$localityOptions"
                        :editable="true"
                        :error="$errors->first('locality')"
                    />
                </div>

                <div class="border-t border-border-input pt-6">
                    <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-1">{{ __('Provenance and Verification') }}</h3>
                    <p class="text-xs text-muted mb-4">{{ __('Tracks where this address came from and how it was processed — useful for auditing data quality and imports.') }}</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui.input
                            wire:model="source"
                            label="{{ __('Source') }}"
                            type="text"
                            placeholder="{{ __('manual, scan, paste, import_api') }}"
                            :error="$errors->first('source')"
                        />

                        <x-ui.input
                            wire:model="source_ref"
                            label="{{ __('Source Reference') }}"
                            type="text"
                            placeholder="{{ __('External reference ID (optional)') }}"
                            :error="$errors->first('source_ref')"
                        />

                        <x-ui.input
                            wire:model="parser_version"
                            label="{{ __('Parser Version') }}"
                            type="text"
                            placeholder="{{ __('Parser version (optional)') }}"
                            :error="$errors->first('parser_version')"
                        />

                        <x-ui.input
                            wire:model="parse_confidence"
                            label="{{ __('Parse Confidence') }}"
                            type="number"
                            step="0.0001"
                            min="0"
                            max="1"
                            placeholder="{{ __('0.0000 to 1.0000') }}"
                            :error="$errors->first('parse_confidence')"
                        />

                        <div class="md:col-span-2">
                            <x-ui.select wire:model="verification_status" label="{{ __('Verification Status') }}" :error="$errors->first('verification_status')">
                                <option value="unverified">{{ __('Unverified') }}</option>
                                <option value="suggested">{{ __('Suggested') }}</option>
                                <option value="verified">{{ __('Verified') }}</option>
                            </x-ui.select>
                        </div>
                    </div>
                </div>

                <x-ui.textarea
                    wire:model="raw_input"
                    label="{{ __('Raw Input') }}"
                    rows="4"
                    placeholder="{{ __('Original pasted or scanned address block (optional)') }}"
                    :error="$errors->first('raw_input')"
                />

                <div class="flex items-center gap-4">
                    <x-ui.button type="submit" variant="primary">
                        {{ __('Create Address') }}
                    </x-ui.button>
                    <a href="{{ route('admin.addresses.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl hover:bg-surface-subtle text-link transition-colors">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
