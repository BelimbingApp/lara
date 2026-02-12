@props(['menuTree'])

<aside class="shrink-0 w-64 bg-surface-sidebar h-full flex flex-col border-r border-border-default">
    {{-- Menu Tree (scrollable) — compact padding like VS Code explorer --}}
    <nav class="flex-1 overflow-y-auto px-2 py-1" aria-label="Main navigation">
        <x-menu.tree :items="$menuTree" />
    </nav>

    {{-- Footer: User + Logout — same compact line treatment --}}
    <div class="px-2 py-1 border-t border-border-default space-y-0">
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 w-full px-2 py-0.5 rounded-none text-sm transition text-link font-normal hover:bg-surface-subtle">
            <div class="w-7 h-7 rounded-full bg-accent text-accent-on flex items-center justify-center text-xs font-medium shrink-0">
                {{ auth()->user()->initials() }}
            </div>
            <div class="min-w-0 flex-1 text-left">
                <div class="truncate text-ink">{{ auth()->user()->name }}</div>
                <div class="text-xs text-muted truncate">{{ auth()->user()->email }}</div>
            </div>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full px-2 py-0.5 text-sm rounded-none text-link font-normal hover:bg-surface-subtle transition text-left">
                Logout
            </button>
        </form>
    </div>
</aside>
