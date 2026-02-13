<?php

use App\Modules\Core\User\Models\User;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        return [
            'users' => User::latest()->paginate(10),
        ];
    }

    public function delete(int $userId): void
    {
        $user = User::findOrFail($userId);

        // Prevent deleting yourself
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
                <a href="{{ route('users.create') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-2xl font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 bg-accent hover:bg-accent-hover text-accent-on px-4 py-2 text-base">
                    <x-icon name="heroicon-o-plus" class="w-5 h-5" />
                    {{ __('Create User') }}
                </a>
            </x-slot>
        </x-ui.page-header>

        @if (session('success'))
            <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert variant="error">{{ session('error') }}</x-ui.alert>
        @endif

        <x-ui.card>
            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Name') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Email') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Created') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-right text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($users as $user)
                            <tr class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-surface-subtle flex items-center justify-center">
                                            <span class="text-xs font-semibold text-ink">
                                                {{ $user->initials() }}
                                            </span>
                                        </div>
                                        <span class="text-sm font-medium text-ink">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $user->email }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('users.edit', $user) }}" wire:navigate class="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg hover:bg-surface-subtle text-link transition-colors">
                                            <x-icon name="heroicon-o-pencil" class="w-4 h-4" />
                                            {{ __('Edit') }}
                                        </a>
                                        <button
                                            wire:click="delete({{ $user->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this user?') }}"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg hover:bg-status-danger-subtle text-status-danger transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            @if($user->id === auth()->id()) disabled title="{{ __('You cannot delete your own account') }}" @endif
                                        >
                                            <x-icon name="heroicon-o-trash" class="w-4 h-4" />
                                            {{ __('Delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No users found.') }}</td>
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

