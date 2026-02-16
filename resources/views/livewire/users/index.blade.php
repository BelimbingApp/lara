<?php

use App\Modules\Core\User\Models\User;
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
            'users' => User::query()
                ->with('company')
                ->when($this->search, function ($query, $search): void {
                    $query->where(function ($q) use ($search): void {
                        $q->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
                })
                ->latest()
                ->paginate(10),
        ];
    }

    public function delete(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            Session::flash('error', __('You cannot delete your own account.'));

            return;
        }

        $user->delete();
        Session::flash('success', __('User deleted successfully.'));
    }
}; ?>

<div>
    <x-slot name="title">{{ __('User Management') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('User Management')">
            <x-slot name="actions">
                <x-ui.button
                    variant="primary"
                    as="a"
                    href="{{ route('admin.users.create') }}"
                    wire:navigate
                >
                    <x-icon name="heroicon-o-plus" class="w-4 h-4" />
                    {{ __('Create User') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        @if (session('success'))
            <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert variant="error">{{ session('error') }}</x-ui.alert>
        @endif

        <x-ui.card>
            <div class="mb-2">
                <x-ui.search-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search by name or email...') }}"
                />
            </div>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Name') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Email') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Company') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Created') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-right text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($users as $user)
                            <tr wire:key="user-{{ $user->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-surface-subtle flex items-center justify-center shrink-0">
                                            <span class="text-xs font-semibold text-ink">
                                                {{ $user->initials() }}
                                            </span>
                                        </div>
                                        <a href="{{ route('admin.users.show', $user) }}" wire:navigate class="text-sm font-medium text-link hover:underline">{{ $user->name }}</a>
                                    </div>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $user->email }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    {{ $user->company?->name ?? '—' }}
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui.button
                                            variant="danger"
                                            size="sm"
                                            wire:click="delete({{ $user->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this user?') }}"
                                            :disabled="$user->id === auth()->id()"
                                            :title="$user->id === auth()->id() ? __('You cannot delete your own account') : null"
                                        >
                                            <x-icon name="heroicon-o-trash" class="w-4 h-4" />
                                            {{ __('Delete') }}
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No users found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $users->links() }}
            </div>
        </x-ui.card>
    </div>
</div>
