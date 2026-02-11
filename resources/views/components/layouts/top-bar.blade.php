<div class="h-14 bg-arid-surface dark:bg-zinc-900 border-b border-arid-taupe/40 dark:border-zinc-800 flex items-center justify-between px-6 flex-shrink-0 z-10">
    {{-- Left: App Name --}}
    <div class="flex items-center gap-4">
        <h1 class="text-lg font-semibold text-arid-ink dark:text-zinc-100">
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
            class="relative w-9 h-5 rounded-full bg-arid-dark-brown/60 dark:bg-zinc-700 transition-colors hover:bg-arid-dark-brown/80 dark:hover:bg-zinc-600 shadow-inner"
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
                class="px-3 py-1.5 text-sm border border-arid-taupe/50 dark:border-zinc-700 rounded-lg bg-arid-cream dark:bg-zinc-900 text-arid-ink dark:text-zinc-100 placeholder:text-zinc-600 dark:placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-arid-dark-brown focus:border-arid-dark-brown dark:focus:ring-zinc-500 dark:focus:border-zinc-500 w-64"
            />
        </div>
    </div>
</div>
