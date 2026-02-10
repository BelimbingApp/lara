@props(['title' => null])

<div class="drawer lg:drawer-open">
    <input id="sidebar-drawer" type="checkbox" class="drawer-toggle" />

    <div class="drawer-content flex flex-col">
        <!-- Page content here -->
        {{ $slot }}
    </div>

    <div class="drawer-side">
        <label for="sidebar-drawer" class="drawer-overlay"></label>
        <aside class="w-64 min-h-full bg-zinc-50 dark:bg-zinc-800 border-r border-zinc-200 dark:border-zinc-800 dark:border-zinc-700">
            {{ $sidebar ?? '' }}
        </aside>
    </div>
</div>

