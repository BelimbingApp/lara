@props(['title' => null])

<div class="drawer lg:drawer-open">
    <input id="sidebar-drawer" type="checkbox" class="drawer-toggle" />

    <div class="drawer-content flex flex-col">
        <!-- Page content here -->
        {{ $slot }}
    </div>

    <div class="drawer-side">
        <label for="sidebar-drawer" class="drawer-overlay"></label>
        <aside class="w-64 min-h-full bg-base-200 border-r border-base-300 dark:border-base-700">
            {{ $sidebar ?? '' }}
        </aside>
    </div>
</div>

