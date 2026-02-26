<?php

use Illuminate\Support\Facades\DB;
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
        $query = DB::table('migrations')
            ->when($this->search, function ($q, $search): void {
                $q->where('migration', 'like', '%' . $search . '%');
            })
            ->orderByDesc('batch')
            ->orderByDesc('id');

        $totalCount = DB::table('migrations')->count();
        $latestBatch = DB::table('migrations')->max('batch');

        return [
            'migrations' => $query->paginate(25),
            'totalCount' => $totalCount,
            'latestBatch' => $latestBatch,
        ];
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Migrations') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Migrations')" :subtitle="__('Database migration status and history')" />

        <x-ui.card>
            <div class="mb-2 flex items-center justify-between gap-4">
                <x-ui.search-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search migrations...') }}"
                />
                <div class="flex items-center gap-3 text-sm text-muted whitespace-nowrap">
                    <span>{{ __('Total:') }} <strong class="tabular-nums">{{ $totalCount }}</strong></span>
                    <span>{{ __('Latest Batch:') }} <strong class="tabular-nums">{{ $latestBatch ?? '—' }}</strong></span>
                </div>
            </div>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('ID') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Migration') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Batch') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($migrations as $migration)
                            <tr wire:key="migration-{{ $migration->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $migration->id }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-ink font-mono">{{ $migration->migration }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $migration->batch }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No migrations found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $migrations->links() }}
            </div>
        </x-ui.card>
    </div>
</div>
