@props([
    'label' => null,
    'error' => null,
    'id' => 'select-' . \Illuminate\Support\Str::random(8),
    'name' => null,
])

<div class="space-y-1">
    @if($label)
        <label for="{{ $id }}" class="block text-[11px] uppercase tracking-wider font-semibold text-muted">
            {{ $label }}
        </label>
    @endif

    <select
        id="{{ $id }}"
        @if($name) name="{{ $name }}" @endif
        {{ $attributes->except(['label', 'error'])->class([
            'w-full px-input-x py-input-y text-sm',
            'border border-border-input rounded-2xl',
            'bg-surface-card text-ink',
            'focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent',
            'border-status-danger focus:ring-status-danger' => $error,
        ]) }}
    >
        {{ $slot }}
    </select>

    @if($error)
        <p class="text-sm text-status-danger">{{ $error }}</p>
    @endif
</div>
