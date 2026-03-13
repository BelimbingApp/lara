<div>
    <x-slot name="title">{{ __('Table Registry') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Table Registry')" :subtitle="__('Manage table stability — stable tables survive migrate:fresh')" />

        <x-ui.card>
            <div class="mb-2">
                <x-ui.search-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search by table name or module...') }}"
                />
            </div>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Table') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Module') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Migration') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Stable') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Stabilized At') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($tables as $table)
                            <tr wire:key="table-{{ $table->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-ink font-mono">{{ $table->table_name }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $table->module_name ?? '—' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted font-mono text-xs" title="{{ $table->migration_file }}">{{ $table->migration_file ? Str::limit($table->migration_file, 50) : '—' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <button
                                        wire:click="toggleStability({{ $table->id }})"
                                        class="cursor-pointer"
                                        title="{{ $table->is_stable ? __('Click to mark unstable') : __('Click to mark stable') }}"
                                    >
                                        <x-ui.badge :variant="$this->stabilityVariant($table->is_stable)">
                                            {{ $table->is_stable ? __('Stable') : __('Unstable') }}
                                        </x-ui.badge>
                                    </button>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $table->stabilized_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No tables registered.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $tables->links() }}
            </div>
        </x-ui.card>
    </div>
</div>
