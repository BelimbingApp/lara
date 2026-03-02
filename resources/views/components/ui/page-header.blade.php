<?php
// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>
?>

@props(['title', 'subtitle' => null, 'actions' => null, 'help' => null])

<div @if($help) x-data="{ helpOpen: false }" @endif>
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-medium tracking-tight text-ink">{{ $title }}</h1>
                @if($help)
                    <x-ui.help @click="helpOpen = !helpOpen" ::aria-expanded="helpOpen" />
                @endif
            </div>
            @if($subtitle)
                <p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>
            @endif
        </div>
        @if($actions)
            <div class="flex items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>

    @if($help)
        <div
            x-cloak
            x-show="helpOpen"
            x-transition:enter="transition ease-out duration-200 motion-reduce:duration-0"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150 motion-reduce:duration-0"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="mt-3 rounded-2xl border border-border-default bg-surface-card p-card-inner text-sm text-muted cursor-pointer"
            @click="helpOpen = false"
            role="note"
            aria-label="{{ __('Click to dismiss') }}"
        >
            {{ $help }}
        </div>
    @endif
</div>
