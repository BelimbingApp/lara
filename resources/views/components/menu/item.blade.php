@props(['item', 'isActive', 'hasActiveChild', 'children'])

<li
    x-data="{ expanded: {{ $hasActiveChild ? 'true' : 'false' }} }"
    class="relative"
>
    @if($item->hasRoute())
        {{-- Link item: full-width line, subtle active highlight (VS Code style) --}}
        <a
            href="{{ $item->route ? route($item->route) : $item->url }}"
            class="flex items-center gap-2 w-full px-2 py-px text-sm rounded-none transition text-link {{ $isActive ? 'bg-surface-subtle text-ink font-medium' : 'hover:bg-surface-subtle font-normal' }}"
        >
            @if(count($children) > 0)
                <span class="text-[12px] shrink-0 text-accent w-3.5 text-center" aria-hidden="true">
                    <span x-show="!expanded">⮞</span>
                    <span x-show="expanded">⮟</span>
                </span>
            @endif

            <span class="truncate">{{ $item->label }}</span>
        </a>
    @else
        {{-- Container item (no route) --}}
        <div
            @click="expanded = !expanded"
            class="flex items-center gap-2 w-full px-2 py-px text-sm rounded-none cursor-pointer transition text-link {{ $hasActiveChild ? 'font-medium' : 'font-normal hover:bg-surface-subtle' }}"
        >
            <span class="text-[12px] shrink-0 text-accent w-3.5 text-center" aria-hidden="true">
                <span x-show="!expanded">⮞</span>
                <span x-show="expanded">⮟</span>
            </span>

            <span class="truncate">{{ $item->label }}</span>
        </div>
    @endif

    {{-- Children (recursive) --}}
    @if(count($children) > 0)
        <ul
            x-show="expanded"
            x-transition
            class="ml-4 mt-0 space-y-0"
        >
            @foreach($children as $child)
                <x-menu.item
                    :item="$child['item']"
                    :isActive="$child['is_active']"
                    :hasActiveChild="$child['has_active_child']"
                    :children="$child['children']"
                />
            @endforeach
        </ul>
    @endif
</li>
