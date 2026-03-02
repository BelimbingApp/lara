<?php
// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

/**
 * Help toggle button ("?" icon).
 *
 * A small circular button for triggering contextual help. Typically used
 * inside x-ui.page-header (which handles the panel), but can also be
 * used standalone with your own Alpine toggle logic.
 *
 * Usage (standalone):
 *   <div x-data="{ show: false }">
 *       <x-ui.help @click="show = !show" />
 *       <div x-show="show">Help content...</div>
 *   </div>
 *
 * Usage (via page-header — just provide the help slot content):
 *   <x-ui.page-header title="..." subtitle="...">
 *       <x-slot name="help">Help content here...</x-slot>
 *   </x-ui.page-header>
 */
?>

@props([])

<button
    type="button"
    {{ $attributes->class([
        'inline-flex items-center justify-center text-muted hover:text-ink transition-colors',
        'focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 rounded-full',
    ]) }}
    aria-label="{{ __('Help') }}"
>
    <x-icon name="heroicon-o-question-mark-circle" class="w-5 h-5" />
</button>
