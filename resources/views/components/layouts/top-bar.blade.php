<div class="h-14 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between px-6 flex-shrink-0 z-10">
    {{-- Left: App Name --}}
    <div class="flex items-center gap-4">
        <h1 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
            Belimbing
        </h1>
    </div>

    {{-- Right: Theme Toggle + Search --}}
    <div class="flex items-center gap-4" x-data="{
        theme: localStorage.getItem('theme') || 'dark',
        init() {
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }">
        {{-- Theme Toggle --}}
        <button
            @click="
                theme = (theme === 'dark' ? 'light' : 'dark');
                localStorage.setItem('theme', theme);
                document.documentElement.classList.toggle('dark');
            "
            class="px-3 py-1.5 rounded-lg text-sm bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition"
            title="Toggle theme"
        >
            <span x-show="theme === 'dark'">☀️</span>
            <span x-show="theme === 'light'" style="display: none;">🌙</span>
        </button>

        {{-- Search (placeholder) --}}
        <div class="hidden md:block">
            <input
                type="search"
                placeholder="Search..."
                class="px-3 py-1.5 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500 w-64"
            />
        </div>
    </div>
</div>
