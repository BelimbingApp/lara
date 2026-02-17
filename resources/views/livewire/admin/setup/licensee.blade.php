<?php

use App\Modules\Core\Company\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component
{
    public string $mode = 'select';

    public ?int $selectedCompanyId = null;

    public string $name = '';

    public ?string $legal_name = null;

    public ?string $registration_number = null;

    public ?string $tax_id = null;

    public ?string $legal_entity_type = null;

    public ?string $jurisdiction = null;

    public ?string $email = null;

    public ?string $website = null;

    public function mount(): void
    {
        if (Company::query()->find(Company::LICENSEE_ID)) {
            $this->redirect(route('admin.companies.show', Company::LICENSEE_ID), navigate: true);
        }

        if (! Company::query()->exists()) {
            $this->mode = 'create';
        }
    }

    /**
     * Promote an existing company to licensee by reassigning its id to 1.
     */
    public function promoteExisting(): void
    {
        $this->validate([
            'selectedCompanyId' => ['required', 'integer', 'exists:companies,id'],
        ]);

        $company = Company::query()->findOrFail($this->selectedCompanyId);
        $oldId = $company->id;

        DB::transaction(function () use ($oldId): void {
            $row = (array) DB::table('companies')->where('id', $oldId)->first();
            unset($row['id']);

            DB::table('companies')->where('id', $oldId)->update(['slug' => $row['slug'].'-reassigning']);
            DB::table('companies')->insert(array_merge($row, ['id' => Company::LICENSEE_ID]));

            $fkTables = [
                ['companies', 'parent_id'],
                ['users', 'company_id'],
                ['employees', 'company_id'],
                ['departments', 'company_id'],
                ['company_departments', 'company_id'],
                ['company_relationships', 'company_id'],
                ['company_relationships', 'related_company_id'],
                ['external_accesses', 'company_id'],
            ];

            foreach ($fkTables as [$table, $column]) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->where($column, $oldId)->update([$column => Company::LICENSEE_ID]);
                }
            }

            if (Schema::hasTable('addressables')) {
                DB::table('addressables')
                    ->where('addressable_id', $oldId)
                    ->where('addressable_type', Company::class)
                    ->update(['addressable_id' => Company::LICENSEE_ID]);
            }

            DB::table('companies')->where('id', $oldId)->delete();
        });

        Session::flash('success', __('Licensee set successfully.'));
        $this->redirect(route('admin.companies.show', Company::LICENSEE_ID), navigate: true);
    }

    /**
     * Create a new company as the licensee with id=1.
     */
    public function createLicensee(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'legal_entity_type' => ['nullable', 'string', 'max:255'],
            'jurisdiction' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
        ]);

        DB::table('companies')->insert(array_merge($validated, [
            'id' => Company::LICENSEE_ID,
            'slug' => \Illuminate\Support\Str::slug($validated['name']),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        Session::flash('success', __('Licensee company created successfully.'));
        $this->redirect(route('admin.companies.show', Company::LICENSEE_ID), navigate: true);
    }

    public function with(): array
    {
        return [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name', 'legal_name', 'status']),
            'hasCompanies' => Company::query()->exists(),
        ];
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Set Licensee') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Set Licensee')" :subtitle="__('Designate the company operating this Belimbing instance')">
            <x-slot name="actions">
                <a href="{{ route('admin.companies.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl hover:bg-surface-subtle text-link transition-colors">
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back') }}
                </a>
            </x-slot>
        </x-ui.page-header>

        <x-ui.alert variant="info">
            {{ __('Belimbing is open-source software (AGPL-3.0). The licensee is the company operating this instance. It will be assigned id=1 and cannot be deleted.') }}
            <br><br>
            {{ __('As the licensee, you may use, modify, and distribute Belimbing (including modified versions). If you offer the software to others over a network (e.g. as a hosted service), you must make the corresponding source code available to those users under the same license.') }}
        </x-ui.alert>

        @if ($mode === 'select' && $hasCompanies)
            <x-ui.card>
                <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-4">{{ __('Select Existing Company') }}</h3>
                <p class="text-xs text-muted mb-4">{{ __('Choose a company to designate as the licensee. Its internal ID will be reassigned to 1.') }}</p>

                <form wire:submit="promoteExisting" class="space-y-4 max-w-md">
                    <x-ui.select wire:model="selectedCompanyId" label="{{ __('Company') }}" :error="$errors->first('selectedCompanyId')">
                        <option value="">{{ __('Select a company...') }}</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}{{ $company->legal_name ? ' ('.$company->legal_name.')' : '' }}</option>
                        @endforeach
                    </x-ui.select>

                    <div class="flex items-center gap-4">
                        <x-ui.button type="submit" variant="primary">
                            {{ __('Set as Licensee') }}
                        </x-ui.button>
                    </div>

                    <p class="text-xs text-muted">
                        {{ __('Or') }}
                        <button type="button" wire:click="$set('mode', 'create')" class="text-link hover:underline">{{ __('create a new company') }}</button>
                    </p>
                </form>
            </x-ui.card>
        @else
            <x-ui.card>
                <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-4">{{ __('Create Licensee Company') }}</h3>

                <form wire:submit="createLicensee" class="space-y-6">
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

                    <x-ui.button type="submit" variant="primary">
                        {{ __('Create Licensee Company') }}
                    </x-ui.button>

                    @if ($hasCompanies)
                        <p class="text-xs text-muted">
                            {{ __('Or') }}
                            <button type="button" wire:click="$set('mode', 'select')" class="text-link hover:underline">{{ __('select an existing company') }}</button>
                        </p>
                    @endif
                </form>
            </x-ui.card>
        @endif
    </div>
</div>
