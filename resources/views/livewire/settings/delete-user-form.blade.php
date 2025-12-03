<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';
    public bool $showDeleteModal = false;

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h2 class="text-2xl font-semibold">{{ __('Delete account') }}</h2>
        <p class="text-zinc-600 dark:text-zinc-400">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <x-mary-button wire:click="$set('showDeleteModal', true)" class="btn-error" data-test="delete-user-button">
        {{ __('Delete account') }}
    </x-mary-button>

    <x-mary-modal wire:model="showDeleteModal" class="max-w-lg">
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <div>
                <h3 class="text-xl font-semibold">{{ __('Are you sure you want to delete your account?') }}</h3>

                <p class="text-zinc-600 dark:text-zinc-400 mt-2">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </p>
            </div>

            <x-mary-input wire:model="password" label="{{ __('Password') }}" type="password" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <x-mary-button wire:click="$set('showDeleteModal', false)" class="btn-ghost">
                    {{ __('Cancel') }}
                </x-mary-button>

                <x-mary-button type="submit" class="btn-error" data-test="confirm-delete-user-button">
                    {{ __('Delete account') }}
                </x-mary-button>
            </div>
        </form>
    </x-mary-modal>
</section>
