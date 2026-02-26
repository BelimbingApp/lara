<?php

use Carbon\Carbon;
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

    public function cancelBatch(string $id): void
    {
        DB::table('job_batches')
            ->where('id', $id)
            ->whereNull('cancelled_at')
            ->whereNull('finished_at')
            ->update(['cancelled_at' => now()->timestamp]);
    }

    public function pruneCompleted(): void
    {
        DB::table('job_batches')
            ->whereNotNull('finished_at')
            ->delete();
    }

    public function with(): array
    {
        return [
            'batches' => DB::table('job_batches')
                ->when($this->search, function ($query, $search): void {
                    $query->where(function ($q) use ($search): void {
                        $q->where('name', 'like', '%'.$search.'%')
                            ->orWhere('id', 'like', '%'.$search.'%');
                    });
                })
                ->orderByDesc('created_at')
                ->paginate(25),
        ];
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Job Batches') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Job Batches')" :subtitle="__('Batched job groups and their progress')">
            <x-slot name="actions">
                <x-ui.button
                    wire:click="pruneCompleted"
                    wire:confirm="{{ __('Are you sure you want to prune all completed batches?') }}"
                >
                    <x-icon name="heroicon-o-trash" class="w-4 h-4" />
                    {{ __('Prune Completed') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <x-ui.card>
            <div class="mb-2">
                <x-ui.search-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search by batch name or ID...') }}"
                />
            </div>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Name') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Progress') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Failed') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Created At') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($batches as $batch)
                            @php
                                $completed = $batch->total_jobs - $batch->pending_jobs - $batch->failed_jobs;
                                $percentage = $batch->total_jobs > 0 ? round(($completed / $batch->total_jobs) * 100) : 0;
                            @endphp
                            <tr wire:key="batch-{{ $batch->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-ink">{{ $batch->name }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $completed }}/{{ $batch->total_jobs }} ({{ $percentage }}%)</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $batch->failed_jobs }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    @if($batch->cancelled_at)
                                        <x-ui.badge variant="danger">{{ __('Cancelled') }}</x-ui.badge>
                                    @elseif($batch->finished_at)
                                        <x-ui.badge variant="success">{{ __('Finished') }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="warning">{{ __('In Progress') }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ Carbon::createFromTimestamp($batch->created_at)->format('Y-m-d H:i:s') }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-right">
                                    @if(!$batch->cancelled_at && !$batch->finished_at)
                                        <button
                                            wire:click="cancelBatch('{{ $batch->id }}')"
                                            wire:confirm="{{ __('Are you sure you want to cancel this batch?') }}"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg hover:bg-status-danger-subtle text-status-danger transition-colors"
                                        >
                                            <x-icon name="heroicon-o-x-circle" class="w-4 h-4" />
                                            {{ __('Cancel') }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No job batches found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $batches->links() }}
            </div>
        </x-ui.card>
    </div>
</div>
