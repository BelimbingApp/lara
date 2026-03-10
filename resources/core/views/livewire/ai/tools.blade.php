<div>
    <x-slot name="title">{{ $toolName ? __('Tools') . ' — ' . $toolName : __('Tools') }}</x-slot>

    @if($toolName)
        <livewire:ai.tools.workspace :tool-name="$toolName" :key="'workspace-' . $toolName" />
    @else
        <livewire:ai.tools.catalog />
    @endif
</div>
