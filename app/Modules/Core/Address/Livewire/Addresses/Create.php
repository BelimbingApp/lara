<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Address\Livewire\Addresses;

use App\Modules\Core\Address\Livewire\AbstractAddressForm;
use App\Modules\Core\Address\Models\Address;
use Illuminate\Support\Facades\Session;

class Create extends AbstractAddressForm
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

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.addresses.create', $this->with());
    }
}
