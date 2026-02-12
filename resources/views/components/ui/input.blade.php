@props([
    'label' => null,
    'error' => null,
    'required' => false,
    'type' => 'text',
])

<div class="space-y-1">
    @if($label)
        <label {{ $attributes->only('for')->class(['block text-sm font-medium text-ink']) }}>
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
            'border-border-input',
            'bg-surface-card',
            'text-ink',
            'placeholder:text-muted',
            'focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent',
            'disabled:opacity-50 disabled:cursor-not-allowed',
            'border-red-500 focus:ring-red-500' => $error,
        ]) }}
    >
    
    @if($error)
        <p class="text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
