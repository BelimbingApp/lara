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

    <div class="space-y-section-gap">
        <div class="flex items-center justify-between gap-2">
            <h1 class="text-2xl font-bold text-ink">{{ __('Countries') }}</h1>
            <div class="flex items-center gap-2">
                <button wire:click="update" wire:loading.attr="disabled" class="inline-flex items-center gap-2 px-4 py-2 bg-accent hover:bg-accent-hover text-accent-on rounded-lg font-medium transition-colors disabled:opacity-50 shrink-0">
                    <x-icon name="heroicon-o-arrow-path" class="w-5 h-5 shrink-0" wire:loading.class="animate-spin" wire:target="update" />
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

        <div class="bg-surface-card border border-border-default shadow-sm rounded-lg">
            <div class="p-card-inner">
                <div class="mb-2">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search by country name or ISO code...') }}"
                        class="w-full px-3 py-1.5 text-sm border border-border-input rounded-lg bg-surface-card text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                    />
                </div>

                <div class="overflow-x-auto -mx-card-inner px-card-inner">
                    <table class="min-w-full divide-y divide-border-default text-sm">
                        <colgroup>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col style="width: 7rem;">
                            <col style="width: 6rem;">
                        </colgroup>
                        <thead class="bg-surface-subtle/80">
                            <tr>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('ISO') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Country') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Capital') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Phone') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Currency') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-right text-[11px] font-semibold text-muted uppercase tracking-wider pr-3">{{ __('Population') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider pl-3">{{ __('Updated') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface-card divide-y divide-border-default">
                            @forelse($countries as $country)
                                <tr wire:key="country-{{ $country->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap font-medium text-ink tabular-nums">{{ $country->iso }}</td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-ink"
                                        x-data="{ editing: false, name: '{{ addslashes($country->country) }}' }"
                                    >
                                        <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                            <span x-text="name"></span>
                                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </div>
                                        <input
                                            x-show="editing"
                                            x-ref="input"
                                            x-model="name"
                                            @keydown.enter="editing = false; $wire.saveName({{ $country->id }}, name)"
                                            @keydown.escape="editing = false; name = '{{ addslashes($country->country) }}'"
                                            @blur="editing = false; $wire.saveName({{ $country->id }}, name)"
                                            type="text"
                                            class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                                        />
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-muted tabular-nums">{{ $country->capital }}</td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-muted tabular-nums">{{ $country->phone }}</td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-muted">{{ $country->currency_code }}</td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-muted text-right tabular-nums pr-3">{{ number_format($country->population) }}</td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-muted tabular-nums pl-3">{{ $country->updated_at?->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-table-cell-x py-8 text-center text-muted">{{ __('No countries found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-2">
                    {{ $countries->links() }}
                </div>
            </div>
        </div>
    </div>
</div>