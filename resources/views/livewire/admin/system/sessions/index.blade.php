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

    public function terminate(string $sessionId): void
    {
        if ($sessionId === session()->getId()) {
            return;
        }

        DB::table('sessions')->where('id', $sessionId)->delete();
    }

    public function with(): array
    {
        $currentSessionId = session()->getId();

        $sessions = DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->select('sessions.id', 'sessions.user_id', 'sessions.ip_address', 'sessions.user_agent', 'sessions.last_activity', 'users.name as user_name')
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('sessions.ip_address', 'like', '%' . $search . '%')
                        ->orWhere('sessions.user_agent', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('sessions.last_activity')
            ->paginate(25);

        return [
            'sessions' => $sessions,
            'currentSessionId' => $currentSessionId,
        ];
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Sessions') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Sessions')" :subtitle="__('View and manage active sessions')" />

        @if (session('success'))
            <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
        @endif

        <x-ui.card>
            <div class="mb-2">
                <x-ui.search-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search by IP address or user agent...') }}"
                />
            </div>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('User') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('IP Address') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('User Agent') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Last Activity') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($sessions as $s)
                            <tr wire:key="session-{{ $s->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-ink">{{ $s->user_name ?? __('Guest') }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $s->ip_address ?? '—' }}</td>
                                <td class="px-table-cell-x py-table-cell-y text-sm text-muted max-w-xs truncate" title="{{ $s->user_agent }}">{{ Str::limit($s->user_agent, 80) }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    @if ($s->id === $currentSessionId)
                                        <x-ui.badge variant="success">{{ __('Current') }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    @if ($s->id !== $currentSessionId)
                                        <x-ui.button
                                            variant="danger"
                                            size="sm"
                                            wire:click="terminate('{{ $s->id }}')"
                                            wire:confirm="{{ __('Are you sure you want to terminate this session?') }}"
                                        >
                                            {{ __('Terminate') }}
                                        </x-ui.button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No sessions found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $sessions->links() }}
            </div>
        </x-ui.card>
    </div>
</div>
