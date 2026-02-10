@props(['variant' => 'default'])

@php
$variantClasses = match($variant) {
    'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    'danger' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$variantClasses}"]) }}>
    {{ $slot }}
</span>
