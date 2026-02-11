@props(['menuTree'])

<aside class="flex-shrink-0 w-64 bg-zinc-50 dark:bg-zinc-950 h-full flex flex-col border-r border-zinc-200 dark:border-zinc-800">
    {{-- Menu Tree (scrollable) — compact padding like VS Code explorer --}}
    <nav class="flex-1 overflow-y-auto px-2 py-2" aria-label="Main navigation">
        <x-menu.tree :items="$menuTree" />
    </nav>

    {{-- Footer: User + Logout — same compact line treatment --}}
    <div class="px-2 py-2 border-t border-zinc-200 dark:border-zinc-800 space-y-0.5">
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 w-full px-2 py-1.5 rounded-sm text-sm transition text-zinc-700 dark:text-zinc-300 font-normal hover:bg-zinc-200/70 dark:hover:bg-zinc-800/70">
            <div class="w-7 h-7 rounded-full bg-zinc-400 dark:bg-zinc-600 text-white flex items-center justify-center text-xs font-medium flex-shrink-0">
                {{ auth()->user()->initials() }}
            </div>
            <div class="min-w-0 flex-1 text-left">
                <div class="truncate">{{ auth()->user()->name }}</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ auth()->user()->email }}</div>
            </div>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-2 px-2 py-1 text-sm rounded-sm text-zinc-700 dark:text-zinc-300 font-normal hover:bg-zinc-200/70 dark:hover:bg-zinc-800/70 transition text-left">
                <span class="w-3.5 text-center" aria-hidden="true">🚪</span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>
