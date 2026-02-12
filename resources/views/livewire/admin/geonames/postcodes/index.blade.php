<?php

use App\Modules\Core\Geonames\Jobs\ImportPostcodes;
use App\Modules\Core\Geonames\Models\Country;
use App\Modules\Core\Geonames\Models\Postcode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var array<int, string> */
    public array $selectedCountries = [];

    public bool $showCountryPicker = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Postcode::query()->orderBy('country_iso')->orderBy('postcode');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('postcode', 'like', '%'.$this->search.'%')
                    ->orWhere('place_name', 'like', '%'.$this->search.'%')
                    ->orWhere('country_iso', 'like', '%'.$this->search.'%');
            });
        }

        $importedIsos = DB::table('geonames_postcodes')
            ->distinct()
            ->pluck('country_iso')
            ->all();

        $allCountries = Country::query()
            ->orderBy('country')
            ->pluck('country', 'iso');

        $hasData = ! empty($importedIsos);

        return [
            'postcodes' => $query->paginate(20),
            'allCountries' => $allCountries,
            'importedIsos' => $importedIsos,
            'hasData' => $hasData,
        ];
    }

    public function import(): void
    {
        if (empty($this->selectedCountries)) {
            Session::flash('error', __('Please select at least one country to import.'));

            return;
        }

        $count = count($this->selectedCountries);

        ImportPostcodes::dispatchSync($this->selectedCountries);

        $this->selectedCountries = [];
        $this->showCountryPicker = false;

        Session::flash('success', __(':count country(s) imported successfully.', ['count' => $count]));
    }

    public function update(): void
    {
        $importedIsos = DB::table('geonames_postcodes')
            ->distinct()
            ->pluck('country_iso')
            ->all();

        if (empty($importedIsos)) {
            return;
        }

        ImportPostcodes::dispatchSync($importedIsos);

        Session::flash('success', __(':count country(s) updated successfully.', ['count' => count($importedIsos)]));
    }

    public function toggleCountryPicker(): void
    {
        $this->showCountryPicker = ! $this->showCountryPicker;
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Geonames Postcodes') }}</x-slot>

    <div class="space-y-section-gap">
        <div class="flex items-center justify-between gap-2">
            <h1 class="text-2xl font-bold text-ink">{{ __('Geonames Postcodes') }}</h1>
            <div class="flex items-center gap-2">
                <button
                    wire:click="{{ $showCountryPicker ? 'import' : 'toggleCountryPicker' }}"
                    wire:loading.attr="disabled"
                    wire:target="import"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-accent hover:bg-accent-hover text-accent-on rounded-lg font-medium transition-colors disabled:opacity-50"
                >
                    <x-icon name="heroicon-o-arrow-down-tray" class="w-5 h-5 shrink-0" />
                    @if ($showCountryPicker && count($selectedCountries) > 0)
                        <span wire:loading.remove wire:target="import">{{ __('Import') }} ({{ count($selectedCountries) }})</span>
                        <span wire:loading wire:target="import">{{ __('Dispatching...') }}</span>
                    @else
                        {{ __('Import') }}
                    @endif
                </button>
                @if ($hasData)
                    <button wire:click="update" wire:loading.attr="disabled" wire:target="update" class="inline-flex items-center gap-2 px-4 py-2 bg-accent hover:bg-accent-hover text-accent-on rounded-lg font-medium transition-colors disabled:opacity-50">
                        <x-icon name="heroicon-o-arrow-path" class="w-5 h-5 shrink-0" wire:loading.class="animate-spin" wire:target="update" />
                        <span wire:loading.remove wire:target="update">{{ __('Update') }}</span>
                        <span wire:loading wire:target="update">{{ __('Updating...') }}</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Country Picker --}}
        @if ($showCountryPicker)
            <div class="bg-surface-card border border-border-default shadow-sm rounded-lg p-card-inner">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-sm font-semibold text-ink">{{ __('Select countries to import') }}</h2>
                        <p class="text-xs text-muted mt-0.5">{{ __('Already imported countries are marked. Use the Update button to refresh their data.') }}</p>
                    </div>
                    <button wire:click="toggleCountryPicker" class="text-muted hover:text-ink shrink-0">
                        <x-icon name="heroicon-o-x-mark" class="w-5 h-5" />
                    </button>
                </div>
                <div x-data="{ countryFilter: '' }">
                    <input
                        type="text"
                        x-model="countryFilter"
                        placeholder="{{ __('Search countries...') }}"
                        class="w-full mb-2 px-3 py-1.5 text-sm border border-border-input rounded-lg bg-surface-card text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                    />
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-1 max-h-64 overflow-y-auto">
                        @foreach ($allCountries as $iso => $name)
                            @php $imported = in_array($iso, $importedIsos); @endphp
                            <label
                                x-show="!countryFilter || '{{ strtolower($name) }}'.includes(countryFilter.toLowerCase()) || '{{ strtolower($iso) }}'.includes(countryFilter.toLowerCase())"
                                class="flex items-center gap-2 px-2 py-1 rounded text-sm {{ $imported ? 'opacity-50' : 'hover:bg-surface-subtle cursor-pointer' }}"
                            >
                                @if ($imported)
                                    <x-icon name="heroicon-o-check-circle" class="w-4 h-4 text-green-600 shrink-0" />
                                    <span class="text-muted truncate" title="{{ $name }} ({{ $iso }}) — already imported">{{ $name }}</span>
                                    <span class="text-muted text-xs shrink-0">{{ $iso }}</span>
                                @else
                                    <input type="checkbox" wire:model.live="selectedCountries" value="{{ $iso }}" class="rounded border-border-input text-accent focus:ring-accent">
                                    <span class="text-ink truncate" title="{{ $name }} ({{ $iso }})">{{ $name }}</span>
                                    <span class="text-muted text-xs shrink-0">{{ $iso }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Progress --}}
        <div
            x-data="{
                progress: null,
                init() {
                    if (window.Echo) {
                        window.Echo.channel('postcode-import')
                            .listen('.App\\Modules\\Core\\Geonames\\Events\\PostcodeImportProgress', (e) => {
                                this.progress = e
                                if (e.status === 'completed' && e.current === e.total) {
                                    setTimeout(() => {
                                        this.progress = null
                                        $wire.$refresh()
                                    }, 3000)
                                }
                            })
                    }
                }
            }"
            x-show="progress"
            x-cloak
            class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-blue-800 dark:text-blue-200"
        >
            <template x-if="progress && progress.status !== 'failed'">
                <div class="flex items-center gap-3 w-full">
                    <x-icon name="heroicon-o-arrow-path" class="w-5 h-5 shrink-0 animate-spin" />
                    <div class="flex-1">
                        <div class="text-sm font-medium" x-text="progress?.message"></div>
                        <div class="mt-1 w-full bg-blue-200 dark:bg-blue-800 rounded-full h-1.5">
                            <div class="bg-blue-600 dark:bg-blue-400 h-1.5 rounded-full transition-all duration-300" :style="'width: ' + (progress ? Math.round((progress.current / progress.total) * 100) : 0) + '%'"></div>
                        </div>
                        <div class="text-xs text-blue-600 dark:text-blue-400 mt-1" x-text="progress ? progress.current + ' / ' + progress.total + ' countries' : ''"></div>
                    </div>
                </div>
            </template>
            <template x-if="progress && progress.status === 'failed'">
                <div class="flex items-center gap-3">
                    <x-icon name="heroicon-o-exclamation-circle" class="w-5 h-5 shrink-0 text-red-600" />
                    <span class="text-sm text-red-800 dark:text-red-200" x-text="progress?.message"></span>
                </div>
            </template>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-200">
                <x-icon name="heroicon-o-check-circle" class="w-6 h-6 shrink-0" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200">
                <x-icon name="heroicon-o-exclamation-circle" class="w-6 h-6 shrink-0" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Table --}}
        <div class="bg-surface-card border border-border-default shadow-sm rounded-lg">
            <div class="p-card-inner">
                <div class="mb-2">
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="{{ __('Search by postcode, place name, or country...') }}"
                        class="w-full px-3 py-1.5 text-sm border border-border-input rounded-lg bg-surface-card text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                    />
                </div>

                <div class="overflow-x-auto -mx-card-inner px-card-inner">
                    <table class="min-w-full divide-y divide-border-default text-sm">
                        <thead class="bg-surface-subtle/80">
                            <tr>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Country') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Postcode') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Place Name') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Admin1 Code') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Latitude') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Longitude') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Updated') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface-card divide-y divide-border-default">
                            @forelse($postcodes as $postcode)
                                <tr class="hover:bg-surface-subtle/50 transition-colors">
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap font-medium text-ink tabular-nums">{{ $postcode->country_iso }}</td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-muted tabular-nums">{{ $postcode->postcode }}</td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-muted">{{ $postcode->place_name }}</td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-muted tabular-nums">{{ $postcode->admin1_code }}</td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-muted tabular-nums">{{ $postcode->latitude }}</td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-muted tabular-nums">{{ $postcode->longitude }}</td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-muted tabular-nums">{{ $postcode->updated_at?->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-table-cell-x py-8 text-center text-muted">{{ __('No postcodes found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-2">
                    {{ $postcodes->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
