<div class="h-6 bg-zinc-100 dark:bg-zinc-950 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between px-4 text-xs text-zinc-600 dark:text-zinc-400 flex-shrink-0">
    {{-- Left: Environment Info --}}
    <div class="flex items-center gap-4">
        <span>{{ config('app.env') }}</span>
        @if(config('app.debug'))
            <span class="text-yellow-600">Debug Mode</span>
        @endif
    </div>

    {{-- Right: Version/Time --}}
    <div class="flex items-center gap-4">
        <span>{{ now()->format('H:i') }}</span>
        <span>v1.0.0</span>
    </div>
</div>
