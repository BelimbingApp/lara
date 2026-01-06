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
            <h1 class="text-2xl font-bold">{{ __('User Management') }}</h1>
            <a href="{{ route('users.create') }}" wire:navigate class="btn btn-primary">
                <x-icon name="heroicon-o-plus" class="w-5 h-5" />
                {{ __('Create User') }}
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                <x-icon name="heroicon-o-check-circle" class="w-6 h-6" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                <x-icon name="heroicon-o-exclamation-circle" class="w-6 h-6" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th class="text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-neutral-200 dark:bg-neutral-700 flex items-center justify-center">
                                                <span class="text-sm font-semibold text-neutral-800 dark:text-white">
                                                    {{ $user->initials() }}
                                                </span>
                                            </div>
                                            <span>{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('users.edit', $user) }}" wire:navigate class="btn btn-sm btn-ghost">
                                                <x-icon name="heroicon-o-pencil" class="w-4 h-4" />
                                                {{ __('Edit') }}
                                            </a>
                                            <button
                                                wire:click="delete({{ $user->id }})"
                                                wire:confirm="{{ __('Are you sure you want to delete this user?') }}"
                                                class="btn btn-sm btn-ghost text-error"
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
                                    <td colspan="4" class="text-center">{{ __('No users found.') }}</td>
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

