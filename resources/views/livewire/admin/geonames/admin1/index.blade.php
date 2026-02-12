<?php

use App\Modules\Core\Geonames\Database\Seeders\Admin1Seeder;
use App\Modules\Core\Geonames\Models\Admin1;
use App\Modules\Core\Geonames\Models\Country;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterCountryIso = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCountryIso(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Admin1::query()
            ->withCountryName()
            ->orderBy('country_name')
            ->orderBy('name');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('geonames_admin1.name', 'like', '%'.$this->search.'%')
                    ->orWhere('geonames_admin1.code', 'like', '%'.$this->search.'%')
                    ->orWhere('geonames_countries.country', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->filterCountryIso) {
            $query->forCountry($this->filterCountryIso);
        }

        $importedCountries = DB::table('geonames_admin1')
            ->selectRaw("SPLIT_PART(code, '.', 1) as iso")
            ->distinct()
            ->pluck('iso')
            ->sort()
            ->values();

        $countryNames = Country::query()
            ->whereIn('iso', $importedCountries)
            ->orderBy('country')
            ->pluck('country', 'iso');

        return [
            'admin1s' => $query->paginate(20),
            'importedCountries' => $countryNames,
        ];
    }

    public function saveName(int $id, string $name): void
    {
        $admin1 = Admin1::query()->findOrFail($id);
        $admin1->name = trim($name);
        $admin1->save();
    }

    public function update(): void
    {
        $importedIsos = DB::table('geonames_admin1')
            ->selectRaw("SPLIT_PART(code, '.', 1) as iso")
            ->distinct()
            ->pluck('iso')
            ->all();

        if (empty($importedIsos)) {
            Session::flash('error', __('No admin1 data to update. Run seeder first.'));

            return;
        }

        app(Admin1Seeder::class)->run();

        Session::flash('success', __('Admin1 divisions updated from Geonames.'));
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Admin1 Divisions') }}</x-slot>

    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ __('Admin1 Divisions') }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ __('States, provinces, and top-level administrative divisions') }}</p>
            </div>
            <button wire:click="update" wire:loading.attr="disabled" wire:target="update" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50">
                <x-icon name="heroicon-o-arrow-path" class="w-5 h-5" wire:loading.class="animate-spin" wire:target="update" />
                <span wire:loading.remove wire:target="update">{{ __('Update') }}</span>
                <span wire:loading wire:target="update">{{ __('Updating...') }}</span>
            </button>
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

        {{-- Filters --}}
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search by name, code, or country...') }}"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>
                    <div class="sm:w-64">
                        <select
                            wire:model.live="filterCountryIso"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">{{ __('All Countries') }}</option>
                            @foreach($importedCountries as $iso => $name)
                                <option value="{{ $iso }}">{{ $name }} ({{ $iso }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Country') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Code') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Alt Name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Updated') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse($admin1s as $admin1)
                                <tr wire:key="admin1-{{ $admin1->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-400">
                                        <span class="font-mono text-xs text-zinc-400 dark:text-zinc-500">{{ $admin1->country_iso }}</span>
                                        <span class="ml-1">{{ $admin1->country_name }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-zinc-900 dark:text-zinc-100">{{ $admin1->code }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100"
                                        x-data="{ editing: false, name: '{{ addslashes($admin1->name) }}' }"
                                    >
                                        <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                            <span x-text="name"></span>
                                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </div>
                                        <input
                                            x-show="editing"
                                            x-ref="input"
                                            x-model="name"
                                            @keydown.enter="editing = false; $wire.saveName({{ $admin1->id }}, name)"
                                            @keydown.escape="editing = false; name = '{{ addslashes($admin1->name) }}'"
                                            @blur="editing = false; $wire.saveName({{ $admin1->id }}, name)"
                                            type="text"
                                            class="w-full px-1 -mx-1 py-0.5 text-sm border border-blue-400 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-400">{{ $admin1->alt_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-400">{{ $admin1->updated_at?->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No admin1 divisions found.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $admin1s->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
