@props(['menuTree'])

<aside class="flex-shrink-0 w-64 bg-zinc-100 dark:bg-zinc-900 h-screen flex flex-col border-r border-zinc-300 dark:border-zinc-700">
    {{-- Menu Tree (scrollable) --}}
    <nav class="flex-1 overflow-y-auto p-4" aria-label="Main navigation">
        <x-menu.tree :items="$menuTree" />
    </nav>

    {{-- Footer Section --}}
    <div class="p-4 border-t border-zinc-300 dark:border-zinc-700 space-y-2">
        {{-- Quick Actions --}}
        <div class="space-y-1">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg text-zinc-900 dark:text-zinc-100 hover:bg-zinc-200 dark:hover:bg-zinc-800 transition">
                <span>⚙️</span>
                <span>Settings</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-sm rounded-lg text-zinc-900 dark:text-zinc-100 hover:bg-zinc-200 dark:hover:bg-zinc-800 transition">
                    <span>🚪</span>
                    <span>Logout</span>
                </button>
            </form>
        </div>

        {{-- User Profile --}}
        <div class="pt-2 border-t border-zinc-300 dark:border-zinc-700">
            <div class="flex items-center gap-3 px-3 py-2">
                <div class="avatar placeholder">
                    <div class="bg-primary text-primary-content rounded-full w-8">
                        <span class="text-xs">{{ auth()->user()->initials() }}</span>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-zinc-600 dark:text-zinc-400 truncate">{{ auth()->user()->email }}</div>
                </div>
            </div>
        </div>
    </div>
</aside>
