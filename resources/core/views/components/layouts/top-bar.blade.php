<div class="h-11 bg-surface-bar border-b border-border-default flex items-center justify-between px-4 shrink-0 z-10">
    {{-- Left: Sidebar toggle + App title --}}
    <div class="flex items-center gap-4">
        <button
            type="button"
            @click="$dispatch('toggle-sidebar')"
            class="inline-flex items-center justify-center w-8 h-8 rounded-sm text-accent hover:bg-surface-subtle transition"
            aria-label="{{ __('Toggle sidebar') }}"
            title="{{ __('Toggle sidebar') }}"
        >
            <x-icon name="heroicon-o-bars-3" class="w-5 h-5" />
        </button>
        <h1 class="text-base font-semibold text-ink">
            Belimbing
        </h1>
    </div>

    {{-- Right: Theme toggle + Lara trigger --}}
    <div class="flex items-center gap-3" x-data="{
        theme: localStorage.getItem('theme') || 'light',
        init() {
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }">
        {{-- Theme Toggle: minimal pill switch --}}
        <button
            @click="
                theme = (theme === 'dark' ? 'light' : 'dark');
                localStorage.setItem('theme', theme);
                document.documentElement.classList.toggle('dark');
            "
            class="relative w-9 h-5 rounded-full bg-border-input dark:bg-zinc-700 transition-colors hover:bg-muted/50 dark:hover:bg-zinc-600 shadow-inner"
            :aria-label="theme === 'dark' ? '{{ __('Switch to light mode') }}' : '{{ __('Switch to dark mode') }}'"
            title="{{ __('Toggle theme') }}"
            :aria-pressed="theme === 'light'"
        >
            <span
                class="absolute top-0.5 w-4 h-4 rounded-full bg-white dark:bg-white shadow transition-transform duration-200"
                :class="theme === 'dark' ? 'left-0.5' : 'left-4'"
            ></span>
        </button>

        {{-- Lara Chat trigger --}}
        @auth
            <button
                type="button"
                @click="$dispatch('open-lara-chat')"
                class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-sm text-muted hover:text-ink hover:bg-surface-subtle transition"
                title="{{ __('Open Lara chat (Ctrl+K)') }}"
                aria-label="{{ __('Open Lara chat') }}"
            >
                <x-ai.lara-identity compact :show-role="false" />
                <kbd class="hidden sm:inline-flex items-center gap-0.5 px-1.5 py-0.5 text-[10px] font-medium text-muted bg-surface-subtle border border-border-default rounded">
                    <span class="text-[9px]">⌘</span>K
                </kbd>
            </button>
        @endauth
    </div>
</div>
