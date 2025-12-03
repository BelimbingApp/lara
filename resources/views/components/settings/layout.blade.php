<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <x-mary-menu class="menu-vertical">
            <x-mary-menu-item :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</x-mary-menu-item>
            <x-mary-menu-item :href="route('password.edit')" wire:navigate>{{ __('Password') }}</x-mary-menu-item>
            <x-mary-menu-item :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</x-mary-menu-item>
        </x-mary-menu>
    </div>

    <hr class="md:hidden border-zinc-200 dark:border-zinc-700" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <h2 class="text-2xl font-semibold">{{ $heading ?? '' }}</h2>
        <p class="text-zinc-600 dark:text-zinc-400">{{ $subheading ?? '' }}</p>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
