{{--
    Tab panel: individual tab content within an <x-ui.tabs> container.

    Props:
        id — Must match a tab ID in the parent <x-ui.tabs :tabs="..."> array.

    The panel reads Alpine state (activeTab, tabId, panelId) from the parent
    <x-ui.tabs> x-data scope. Do not use this component outside of <x-ui.tabs>.

    Usage: See <x-ui.tabs> for full example.
--}}
@props(['id'])

<div
    x-show="isActive('{{ $id }}')"
    x-cloak
    role="tabpanel"
    :id="panelId('{{ $id }}')"
    :aria-labelledby="tabId('{{ $id }}')"
    :tabindex="isActive('{{ $id }}') ? '0' : '-1'"
    {{ $attributes->class([]) }}
>
    {{ $slot }}
</div>
