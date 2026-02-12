<?php
// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>
?>

@props(['title', 'subtitle' => null, 'actions' => null])

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-ink">{{ $title }}</h1>
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
</div>

