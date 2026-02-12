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

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-ink">{{ __('User Management') }}</h1>
            <a href="{{ route('users.create') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 bg-accent hover:bg-accent-hover text-accent-on rounded-lg font-medium transition-colors">
                <x-icon name="heroicon-o-plus" class="w-5 h-5" />
                {{ __('Create User') }}
            </a>
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
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-default">
                        <thead class="bg-surface-subtle/80">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">{{ __('Name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">{{ __('Email') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">{{ __('Created') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-muted uppercase tracking-wider">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface-card divide-y divide-border-default">
                            @forelse($users as $user)
                                <tr class="hover:bg-surface-subtle/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-surface-subtle flex items-center justify-center">
                                                <span class="text-sm font-semibold text-ink">
                                                    {{ $user->initials() }}
                                                </span>
                                            </div>
                                            <span class="text-sm font-medium text-ink">{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted">{{ $user->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted">{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('users.edit', $user) }}" wire:navigate class="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg hover:bg-surface-subtle text-link transition-colors">
                                                <x-icon name="heroicon-o-pencil" class="w-4 h-4" />
                                                {{ __('Edit') }}
                                            </a>
                                            <button
                                                wire:click="delete({{ $user->id }})"
                                                wire:confirm="{{ __('Are you sure you want to delete this user?') }}"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
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
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-muted">{{ __('No users found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

