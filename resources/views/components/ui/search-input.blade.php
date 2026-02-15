@props([
    'placeholder' => 'Search...',
])

<div class="relative">
    <x-icon
        name="heroicon-o-magnifying-glass"
        class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted pointer-events-none"
    />
    <input
        type="search"
        placeholder="{{ $placeholder }}"
        {{ $attributes->class([
            'w-full pl-8 pr-input-x py-input-y text-sm',
            'border border-border-input rounded-2xl',
            'bg-surface-card text-ink placeholder:text-muted',
            'focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent',
            '[&::-webkit-search-cancel-button]:appearance-none',
        ]) }}
    >
</div>
