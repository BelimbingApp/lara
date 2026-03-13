@props([
    'compact' => false,
    'showRole' => true,
    'showShortcut' => false,
])

<x-ai.agent-identity
    name="Lara"
    badge-label="System Agent"
    icon="heroicon-o-sparkles"
    :shortcut="$showShortcut ? 'Ctrl+K' : null"
    :compact="$compact"
    :show-role="$showRole"
    {{ $attributes }}
/>
