@props(['title' => null])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-sm']) }}>
    @if($title)
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h3>
        </div>
        <div class="p-6">
            {{ $slot }}
        </div>
    @else
        <div class="p-6">
            {{ $slot }}
        </div>
    @endif
</div>
