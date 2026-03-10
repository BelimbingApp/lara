<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>
//
// Tool Workspace orchestrator — routes between catalog and per-tool workspace.

use Livewire\Volt\Component;

new class extends Component
{
    /** @var string|null null = catalog view, tool name = workspace view */
    public ?string $toolName = null;
}; ?>

<div>
    <x-slot name="title">{{ $toolName ? __('Tools') . ' — ' . $toolName : __('Tools') }}</x-slot>

    @if($toolName)
        <livewire:ai.tools.workspace :tool-name="$toolName" :key="'workspace-' . $toolName" />
    @else
        <livewire:ai.tools.catalog />
    @endif
</div>
