<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <nav class="flex flex-col space-y-1">
            <a href="{{ route('profile.edit') }}" wire:navigate class="px-4 py-2 text-sm rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors {{ request()->routeIs('profile.edit') ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}">
                {{ __('Profile') }}
            </a>
            <a href="{{ route('password.edit') }}" wire:navigate class="px-4 py-2 text-sm rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors {{ request()->routeIs('password.edit') ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}">
                {{ __('Password') }}
            </a>
            <a href="{{ route('appearance.edit') }}" wire:navigate class="px-4 py-2 text-sm rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition-colors {{ request()->routeIs('appearance.edit') ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}">
                {{ __('Appearance') }}
            </a>
        </nav>
    </div>

    <hr class="md:hidden border-zinc-200 dark:border-zinc-700" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $heading ?? '' }}</h2>
        <p class="text-zinc-600 dark:text-zinc-400">{{ $subheading ?? '' }}</p>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
