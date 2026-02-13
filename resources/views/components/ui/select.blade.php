@props([])

<select
    {{ $attributes->class([
        'w-full px-3 py-1.5 text-sm',
        'border border-border-input rounded-2xl',
        'bg-surface-card text-ink',
        'focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent',
    ]) }}
>
    {{ $slot }}
</select>
