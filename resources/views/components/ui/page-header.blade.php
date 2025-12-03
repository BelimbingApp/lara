<?php
// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2025 Ng Kiat Siong
?>

@props(['title', 'subtitle' => null, 'actions' => null])

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-base-content">{{ $title }}</h1>
            @if($subtitle)
                <p class="mt-1 text-sm text-base-content/60">{{ $subtitle }}</p>
            @endif
        </div>
        @if($actions)
            <div class="flex items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>

