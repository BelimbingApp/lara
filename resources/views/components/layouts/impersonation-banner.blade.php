@if(session('impersonation.original_user_id'))
    <div class="h-8 bg-status-warning-subtle border-b border-status-warning-border flex items-center justify-between px-4 shrink-0">
        <div class="flex items-center gap-2 text-xs text-status-warning">
            <x-icon name="heroicon-o-eye" class="w-3.5 h-3.5" />
            <span>{{ __('Viewing as :name', ['name' => auth()->user()->name]) }}</span>
        </div>

        <form method="POST" action="{{ route('admin.impersonate.stop') }}">
            @csrf
            <button type="submit" class="text-xs font-medium text-status-warning hover:underline">
                {{ __('Stop Impersonation') }}
            </button>
        </form>
    </div>
@endif
