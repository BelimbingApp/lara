<div class="h-14 bg-surface-bar border-b border-border-default flex items-center justify-between px-6 shrink-0 z-10">
    {{-- Left: App Name --}}
    <div class="flex items-center gap-4">
        <h1 class="text-lg font-semibold text-ink">
            Belimbing
        </h1>
    </div>

    {{-- Right: Theme Toggle + Search --}}
    <div class="flex items-center gap-4" x-data="{
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
            class="relative w-9 h-5 rounded-full bg-accent/60 dark:bg-zinc-700 transition-colors hover:bg-accent-hover/80 dark:hover:bg-zinc-600 shadow-inner"
            title="Toggle theme"
            :aria-pressed="theme === 'light'"
        >
            <span
                class="absolute top-0.5 w-4 h-4 rounded-full bg-white dark:bg-white shadow transition-transform duration-200"
                :class="theme === 'dark' ? 'left-0.5' : 'left-4'"
            ></span>
        </button>

        {{-- Search (placeholder) --}}
        <div class="hidden md:block">
            <input
                type="search"
                placeholder="Search..."
                class="px-3 py-1.5 text-sm border border-border-input rounded-lg bg-surface-card text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent w-64"
            />
        </div>
    </div>
</div>
