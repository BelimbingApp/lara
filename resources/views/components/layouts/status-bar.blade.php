@php
    $licenseeExists = \App\Modules\Core\Company\Models\Company::query()->where('id', \App\Modules\Core\Company\Models\Company::LICENSEE_ID)->exists();
@endphp

<div class="h-6 bg-surface-bar border-t border-border-default flex items-center justify-between px-4 text-xs text-muted shrink-0">
    {{-- Left: Environment Info + Warnings --}}
    <div class="flex items-center gap-4">
        <span>{{ config('app.env') }}</span>
        @if(config('app.debug'))
            <span>Debug Mode</span>
        @endif
        @auth
            @if (!$licenseeExists)
                <a href="{{ route('admin.setup.licensee') }}" wire:navigate class="text-status-danger hover:underline flex items-center gap-1">
                    <x-icon name="heroicon-o-exclamation-triangle" class="w-3.5 h-3.5" />
                    {{ __('Licensee not set') }}
                </a>
            @endif
        @endauth
    </div>

    {{-- Right: Version/Time --}}
    <div class="flex items-center gap-4">
        <span>{{ now()->format('H:i') }}</span>
        <span>v1.0.0</span>
    </div>
</div>
