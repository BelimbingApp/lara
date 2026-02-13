@props(['variant' => 'success'])

@php
$config = match($variant) {
    'success' => [
        'bg' => 'bg-status-success-subtle',
        'border' => 'border-status-success-border',
        'text' => 'text-status-success',
        'icon' => 'heroicon-o-check-circle',
    ],
    'danger', 'error' => [
        'bg' => 'bg-status-danger-subtle',
        'border' => 'border-status-danger-border',
        'text' => 'text-status-danger',
        'icon' => 'heroicon-o-exclamation-circle',
    ],
    'warning' => [
        'bg' => 'bg-status-warning-subtle',
        'border' => 'border-status-warning-border',
        'text' => 'text-status-warning',
        'icon' => 'heroicon-o-exclamation-triangle',
    ],
    'info' => [
        'bg' => 'bg-status-info-subtle',
        'border' => 'border-status-info-border',
        'text' => 'text-status-info',
        'icon' => 'heroicon-o-information-circle',
    ],
    default => [
        'bg' => 'bg-status-success-subtle',
        'border' => 'border-status-success-border',
        'text' => 'text-status-success',
        'icon' => 'heroicon-o-check-circle',
    ],
};
@endphp

<div {{ $attributes->class(["flex items-center gap-3 p-4 border rounded-2xl {$config['bg']} {$config['border']} {$config['text']}"]) }}>
    <x-icon :name="$config['icon']" class="w-5 h-5 shrink-0" />
    <span class="text-sm">{{ $slot }}</span>
</div>
