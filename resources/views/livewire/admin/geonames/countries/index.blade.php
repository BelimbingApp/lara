<?php

use App\Modules\Core\Geonames\Database\Seeders\CountrySeeder;
use App\Modules\Core\Geonames\Models\Country;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'countries' => Country::query()
                ->when($this->search, function ($query, $search) {
                    $query->where('country', 'like', '%'.$search.'%')
                        ->orWhere('iso', 'like', '%'.$search.'%');
                })
                ->orderBy('country')
                ->paginate(20),
        ];
    }

    public function saveName(int $id, string $name): void
    {
        $country = Country::query()->findOrFail($id);
        $country->country = trim($name);
        $country->save();
    }

    public function update(): void
    {
        app(CountrySeeder::class)->run();
        Session::flash('success', __('Countries updated from Geonames.'));
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Countries') }}</x-slot>

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ __('Countries') }}</h1>
            <div class="flex items-center gap-2">
                <button wire:click="update" wire:loading.attr="disabled" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50">
                    <x-icon name="heroicon-o-arrow-path" class="w-5 h-5" wire:loading.class="animate-spin" wire:target="update" />
                    <span wire:loading.remove wire:target="update">{{ __('Update') }}</span>
                    <span wire:loading wire:target="update">{{ __('Updating...') }}</span>
                </button>
            </div>
        </div>

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

        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-lg">
            <div class="p-6">
                <div class="mb-4">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search by country name or ISO code...') }}"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('ISO') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Country') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Capital') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Phone') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Currency') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Population') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">{{ __('Updated') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse($countries as $country)
                                <tr wire:key="country-{{ $country->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $country->iso }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100"
                                        x-data="{ editing: false, name: '{{ addslashes($country->country) }}' }"
                                    >
                                        <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                            <span x-text="name"></span>
                                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </div>
                                        <input
                                            x-show="editing"
                                            x-ref="input"
                                            x-model="name"
                                            @keydown.enter="editing = false; $wire.saveName({{ $country->id }}, name)"
                                            @keydown.escape="editing = false; name = '{{ addslashes($country->country) }}'"
                                            @blur="editing = false; $wire.saveName({{ $country->id }}, name)"
                                            type="text"
                                            class="w-full px-1 -mx-1 py-0.5 text-sm border border-blue-400 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-400">{{ $country->capital }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-400">{{ $country->phone }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-400">{{ $country->currency_code }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-400 text-right">{{ number_format($country->population) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-400">{{ $country->updated_at?->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No countries found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $countries->links() }}
                </div>
            </div>
        </div>
    </div>
</div>