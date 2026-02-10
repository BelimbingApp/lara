@props([
    'label' => null,
    'error' => null,
    'required' => false,
    'type' => 'text',
])

<div class="space-y-1">
    @if($label)
        <label {{ $attributes->only('for')->class(['block text-sm font-medium text-zinc-900 dark:text-zinc-100']) }}>
            {{ $label }}
            @if($required)
                <span class="text-red-600">*</span>
            @endif
        </label>
    @endif
    
    <input 
        type="{{ $type }}"
        {{ $attributes->except(['label', 'error', 'required'])->class([
            'w-full px-3 py-2 border rounded-lg transition-colors',
            'border-zinc-300 dark:border-zinc-700',
            'bg-white dark:bg-zinc-900',
            'text-zinc-900 dark:text-zinc-100',
            'placeholder:text-zinc-400 dark:placeholder:text-zinc-600',
            'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
            'disabled:opacity-50 disabled:cursor-not-allowed',
            'border-red-500 focus:ring-red-500' => $error,
        ]) }}
    >
    
    @if($error)
        <p class="text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
