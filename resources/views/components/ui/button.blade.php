@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button'
])

@php
$variantClasses = match($variant) {
    'primary' => 'bg-accent hover:bg-accent-hover text-accent-on',
    'secondary' => 'bg-surface-secondary hover:bg-surface-secondary-hover text-accent-on',
    'danger' => 'bg-status-danger hover:bg-status-danger/90 text-accent-on',
    'ghost' => 'hover:bg-surface-subtle text-ink',
    'outline' => 'border-2 border-border-input hover:bg-surface-subtle text-ink',
    default => 'bg-accent hover:bg-accent-hover text-accent-on',
};

$sizeClasses = match($size) {
    'sm' => 'px-2.5 py-1 text-xs',
    'md' => 'px-3.5 py-1.5 text-sm',
    'lg' => 'px-5 py-2.5 text-base',
    default => 'px-3.5 py-1.5 text-sm',
};
@endphp

<button 
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-2xl font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed {$variantClasses} {$sizeClasses}"]) }}
>
    {{ $slot }}
</button>
