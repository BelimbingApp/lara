@props([
    'label' => null,
    'error' => null,
])

<div class="flex items-center gap-2">
    <input 
        type="checkbox"
        {{ $attributes->class([
            'w-4 h-4 rounded border transition-colors',
            'border-zinc-300 dark:border-zinc-700',
            'bg-white dark:bg-zinc-900',
            'text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
            'disabled:opacity-50 disabled:cursor-not-allowed',
            'border-red-500' => $error,
        ]) }}
    >
    
    @if($label)
        <label {{ $attributes->only('for')->class(['text-sm font-medium text-zinc-900 dark:text-zinc-100']) }}>
            {{ $label }}
        </label>
    @endif
    
    @if($error)
        <p class="text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
