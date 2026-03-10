@props([
    'label' => null,
    'error' => null,
    'required' => false,
    'rows' => 4,
])

<div class="space-y-1">
    @if($label)
        <label {{ $attributes->only('for')->class(['block text-[11px] uppercase tracking-wider font-semibold text-muted']) }}>
            {{ $label }}
            @if($required)
                <span class="text-status-danger">*</span>
            @endif
        </label>
    @endif

    <textarea
        rows="{{ $rows }}"
        {{ $attributes->except(['label', 'error', 'required', 'rows'])->class([
            'w-full px-input-x py-input-y text-sm border rounded-2xl transition-colors',
            'border-border-input',
            'bg-surface-card',
            'text-ink',
            'placeholder:text-muted',
            'focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent',
            'disabled:opacity-50 disabled:cursor-not-allowed',
            'border-status-danger focus:ring-status-danger' => $error,
        ]) }}
    >{{ $slot }}</textarea>

    @if($error)
        <p class="text-sm text-status-danger">{{ $error }}</p>
    @endif
</div>
