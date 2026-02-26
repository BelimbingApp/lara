<?php

use App\Base\Database\Models\SeederRegistry;
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

    /**
     * Extract the short class name (basename) from a fully qualified class name.
     */
    public function shortClass(string $fqcn): string
    {
        return class_basename($fqcn);
    }

    /**
     * Map a seeder status to a badge variant.
     */
    public function statusVariant(string $status): string
    {
        return match ($status) {
            SeederRegistry::STATUS_COMPLETED => 'success',
            SeederRegistry::STATUS_FAILED    => 'danger',
            SeederRegistry::STATUS_RUNNING   => 'warning',
            default                          => 'default',
        };
    }

    public function with(): array
    {
        return [
            'seeders' => SeederRegistry::query()
                ->when($this->search, function ($query, $search): void {
                    $query->where(function ($q) use ($search): void {
                        $q->where('seeder_class', 'like', '%' . $search . '%')
                            ->orWhere('module_name', 'like', '%' . $search . '%');
                    });
                })
                ->orderBy('migration_file')
                ->orderBy('seeder_class')
                ->paginate(25),
        ];
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Database Seeders') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Database Seeders')" :subtitle="__('Seeder registry status and execution history')" />

        <x-ui.card>
            <div class="mb-2">
                <x-ui.search-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search by seeder class or module...') }}"
                />
            </div>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Seeder') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Module') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Ran At') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Error') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($seeders as $seeder)
                            <tr wire:key="seeder-{{ $seeder->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-ink font-mono" title="{{ $seeder->seeder_class }}">{{ $this->shortClass($seeder->seeder_class) }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $seeder->module_name ?? '—' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <x-ui.badge :variant="$this->statusVariant($seeder->status)">{{ __(ucfirst($seeder->status)) }}</x-ui.badge>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $seeder->ran_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                                <td class="px-table-cell-x py-table-cell-y text-sm text-danger max-w-xs truncate" title="{{ $seeder->error_message }}">{{ $seeder->error_message ? Str::limit($seeder->error_message, 60) : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No seeders found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $seeders->links() }}
            </div>
        </x-ui.card>
    </div>
</div>
