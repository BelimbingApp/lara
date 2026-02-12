@props(['title' => null])

<div {{ $attributes->merge(['class' => 'bg-surface-card border border-border-default rounded-lg shadow-sm']) }}>
    @if($title)
        <div class="px-6 py-4 border-b border-border-default">
            <h3 class="text-lg font-semibold text-ink">{{ $title }}</h3>
        </div>
        <div class="p-6">
            {{ $slot }}
        </div>
    @else
        <div class="p-6">
            {{ $slot }}
        </div>
    @endif
</div>
