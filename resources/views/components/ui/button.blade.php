@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button'
])

@php
$variantClasses = match($variant) {
    'primary' => 'bg-accent hover:bg-accent-hover text-accent-on',
    'secondary' => 'bg-zinc-600 hover:bg-zinc-700 text-white',
    'danger' => 'bg-red-600 hover:bg-red-700 text-white',
    'ghost' => 'hover:bg-surface-subtle text-ink',
    'outline' => 'border-2 border-border-input hover:bg-surface-subtle text-ink',
    default => 'bg-accent hover:bg-accent-hover text-accent-on',
};

$sizeClasses = match($size) {
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-base',
    'lg' => 'px-6 py-3 text-lg',
    default => 'px-4 py-2 text-base',
};
@endphp

<button 
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed {$variantClasses} {$sizeClasses}"]) }}
>
    {{ $slot }}
</button>
