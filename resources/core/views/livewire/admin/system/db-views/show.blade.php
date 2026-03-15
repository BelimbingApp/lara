<?php
// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

/** @var \App\Base\Database\Livewire\DbViews\Show $this */
?>

<div>
    <x-slot name="title">{{ $this->dbView->name }}</x-slot>

    <div class="space-y-section-gap">
        {{-- Breadcrumb --}}
        <a href="{{ route('admin.system.db-views.index') }}" class="text-accent hover:underline text-sm" wire:navigate>
            ← {{ __('Back to DB Views') }}
        </a>

        {{-- Page Header --}}
        <x-ui.page-header
            :title="$this->dbView->name"
            :subtitle="trans_choice(':count row|:count rows', $total, ['count' => number_format($total)])"
            :pinnable="[
                'label' => $this->dbView->name,
                'url' => request()->url(),
                'icon' => $this->dbView->icon ?? 'heroicon-o-circle-stack',
            ]"
        >
            <x-slot name="actions">
                <x-ui.button variant="ghost" size="sm" wire:click="toggleEdit">
                    <x-icon name="heroicon-o-pencil-square" class="w-4 h-4" />
                    {{ $this->editing ? __('Cancel') : __('Edit') }}
                </x-ui.button>
                <x-ui.button variant="ghost" size="sm" href="{{ route('admin.system.db-views.index') }}" wire:navigate>
                    <x-icon name="heroicon-o-arrow-left" class="w-4 h-4" />
                    {{ __('Back to DB Views') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        {{-- Error --}}
        @if($error)
            <x-ui.alert variant="danger">{{ $error }}</x-ui.alert>
        @endif

        {{-- Edit Form --}}
        @if($this->editing)
            <x-ui.card>
                <div class="space-y-4">
                    <x-ui.input
                        label="{{ __('Name') }}"
                        wire:model="editName"
                        required
                    />
                    <x-ui.textarea
                        label="{{ __('SQL Query') }}"
                        wire:model="editSql"
                        rows="6"
                        class="font-mono"
                        required
                    />
                    <x-ui.textarea
                        label="{{ __('Description') }}"
                        wire:model="editDescription"
                        rows="3"
                    />
                    <div class="flex items-center gap-2">
                        <x-ui.button variant="primary" size="sm" wire:click="saveEdit">
                            <x-icon name="heroicon-o-check" class="w-4 h-4" />
                            {{ __('Save') }}
                        </x-ui.button>
                        <x-ui.button variant="ghost" size="sm" wire:click="toggleEdit">
                            {{ __('Cancel') }}
                        </x-ui.button>
                    </div>
                </div>
            </x-ui.card>
        @endif

        {{-- Description --}}
        @if($this->dbView->description && ! $this->editing)
            <p class="text-sm text-muted">{{ $this->dbView->description }}</p>
        @endif

        {{-- Results Table --}}
        <x-ui.card>
            <div class="mb-2 flex items-center justify-between gap-4">
                <span class="text-xs text-muted whitespace-nowrap tabular-nums">
                    {{ trans_choice(':count column|:count columns', count($columns), ['count' => count($columns)]) }}
                </span>
            </div>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            @foreach($columns as $col)
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">
                                    {{ $col }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($rows as $index => $row)
                            <tr wire:key="row-{{ $index }}" class="hover:bg-surface-subtle/50 transition-colors">
                                @foreach($columns as $col)
                                    @php
                                        $value = $row[$col] ?? null;
                                        $isLong = $value !== null && mb_strlen((string) $value) > 120;
                                    @endphp
                                    <td
                                        class="px-table-cell-x py-table-cell-y font-mono text-sm whitespace-nowrap {{ $value === null ? 'text-muted' : 'text-ink' }}"
                                        @if($isLong) title="{{ Str::limit((string) $value, 500) }}" @endif
                                    >
                                        @if($value === null)
                                            —
                                        @elseif($isLong)
                                            {{ Str::limit((string) $value, 120) }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) }}" class="px-table-cell-x py-8 text-center text-sm text-muted">
                                    {{ __('No rows returned.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Manual Pagination --}}
            @if($lastPage > 1)
                <div class="mt-2 flex items-center justify-between text-sm text-muted">
                    <span class="tabular-nums">
                        {{ __('Showing :from to :to of :total results', [
                            'from' => number_format(($currentPage - 1) * $perPage + 1),
                            'to' => number_format(min($currentPage * $perPage, $total)),
                            'total' => number_format($total),
                        ]) }}
                    </span>
                    <div class="flex items-center gap-2">
                        <x-ui.button
                            variant="ghost"
                            size="sm"
                            wire:click="previousPage"
                            :disabled="$currentPage <= 1"
                        >
                            <x-icon name="heroicon-o-chevron-left" class="w-4 h-4" />
                            {{ __('Previous') }}
                        </x-ui.button>
                        <span class="tabular-nums text-xs">
                            {{ $currentPage }} / {{ $lastPage }}
                        </span>
                        <x-ui.button
                            variant="ghost"
                            size="sm"
                            wire:click="nextPage"
                            :disabled="$currentPage >= $lastPage"
                        >
                            {{ __('Next') }}
                            <x-icon name="heroicon-o-chevron-right" class="w-4 h-4" />
                        </x-ui.button>
                    </div>
                </div>
            @endif
        </x-ui.card>

        {{-- SQL Preview --}}
        <details class="mt-4">
            <summary class="text-sm text-muted cursor-pointer hover:text-ink">{{ __('SQL Query') }}</summary>
            <pre class="mt-2 p-3 rounded-lg bg-surface-subtle text-xs font-mono text-ink whitespace-pre-wrap break-words">{{ $this->dbView->sql_query }}</pre>
        </details>
    </div>
</div>
