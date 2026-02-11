@props(['menuTree'])

<aside class="flex-shrink-0 w-64 bg-arid-surface dark:bg-zinc-950 h-full flex flex-col border-r border-arid-taupe/40 dark:border-zinc-800">
    {{-- Menu Tree (scrollable) — compact padding like VS Code explorer --}}
    <nav class="flex-1 overflow-y-auto px-2 py-1" aria-label="Main navigation">
        <x-menu.tree :items="$menuTree" />
    </nav>

    {{-- Footer: User + Logout — same compact line treatment --}}
    <div class="px-2 py-1 border-t border-arid-taupe/40 dark:border-zinc-800 space-y-0">
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 w-full px-2 py-0.5 rounded-none text-sm transition text-arid-ink dark:text-zinc-300 font-normal hover:bg-arid-taupe/30 dark:hover:bg-zinc-800/70">
            <div class="w-7 h-7 rounded-full bg-arid-brown dark:bg-zinc-600 text-arid-cream dark:text-white flex items-center justify-center text-xs font-medium flex-shrink-0">
                {{ auth()->user()->initials() }}
            </div>
            <div class="min-w-0 flex-1 text-left">
                <div class="truncate">{{ auth()->user()->name }}</div>
                <div class="text-xs text-zinc-600 dark:text-zinc-400 truncate">{{ auth()->user()->email }}</div>
            </div>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full px-2 py-0.5 text-sm rounded-none text-arid-ink dark:text-zinc-300 font-normal hover:bg-arid-taupe/30 dark:hover:bg-zinc-800/70 transition text-left">
                Logout
            </button>
        </form>
    </div>
</aside>
