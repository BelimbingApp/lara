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
                <span class="text-status-danger">*</span>
            @endif
        </label>
    @endif
    
    <input 
        type="{{ $type }}"
        {{ $attributes->except(['label', 'error', 'required'])->class([
            'w-full px-3 py-2 border rounded-2xl transition-colors',
            'border-border-input',
            'bg-surface-card',
            'text-ink',
            'placeholder:text-muted',
            'focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent',
            'disabled:opacity-50 disabled:cursor-not-allowed',
            'border-status-danger focus:ring-status-danger' => $error,
        ]) }}
    >
    
    @if($error)
        <p class="text-sm text-status-danger">{{ $error }}</p>
    @endif
</div>
