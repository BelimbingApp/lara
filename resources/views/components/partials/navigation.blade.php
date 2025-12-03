<?php
// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2025 Ng Kiat Siong
?>

@props(['items' => []])

<ul class="menu menu-vertical w-full gap-1">
    @foreach($items as $item)
        <li>
            <a
                href="{{ $item['href'] ?? '#' }}"
                wire:navigate="{{ $item['wireNavigate'] ?? true }}"
                class="{{ ($item['route'] ?? null) && request()->routeIs($item['route']) ? 'active' : '' }} nav-link"
                @if(isset($item['target'])) target="{{ $item['target'] }}" @endif
            >
                @if(isset($item['icon']))
                    <x-icon :name="$item['icon']" class="w-5 h-5" />
                @endif
                <span>{{ $item['label'] ?? '' }}</span>
            </a>
        </li>
    @endforeach
</ul>

