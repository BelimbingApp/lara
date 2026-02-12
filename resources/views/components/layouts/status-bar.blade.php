<div class="h-6 bg-surface-bar border-t border-border-default flex items-center justify-between px-4 text-xs text-muted shrink-0">
    {{-- Left: Environment Info --}}
    <div class="flex items-center gap-4">
        <span>{{ config('app.env') }}</span>
        @if(config('app.debug'))
            <span>Debug Mode</span>
        @endif
    </div>

    {{-- Right: Version/Time --}}
    <div class="flex items-center gap-4">
        <span>{{ now()->format('H:i') }}</span>
        <span>v1.0.0</span>
    </div>
</div>
