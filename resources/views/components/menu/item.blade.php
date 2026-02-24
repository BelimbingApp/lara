@props(['item', 'isActive', 'hasActiveChild', 'children', 'collapsed' => false])

<li
    x-data="{ expanded: {{ $hasActiveChild ? 'true' : 'false' }} }"
    class="relative"
>
    @php
        $iconName = $item->icon ?? 'heroicon-o-squares-2x2';
    @endphp

    @if($item->hasRoute())
        {{-- Link item: full-width line, subtle active highlight (VS Code style) --}}
        @if($collapsed)
            <a
                href="{{ $item->route ? route($item->route) : $item->url }}"
                class="flex items-center justify-center w-full h-8 rounded-none transition text-link {{ $isActive ? 'bg-surface-card text-ink' : 'hover:bg-surface-subtle' }}"
                aria-label="{{ __($item->label) }}"
                title="{{ __($item->label) }}"
            >
                <x-icon :name="$iconName" class="w-[1.125rem] h-[1.125rem]" />
            </a>
        @else
            <div class="flex items-center w-full px-1 py-px text-sm rounded-none transition text-link {{ $isActive ? 'bg-surface-card text-ink font-medium' : 'hover:bg-surface-subtle font-normal' }}">
                @if(count($children) > 0)
                    <span
                        @click.prevent="expanded = !expanded"
                        class="text-[12px] shrink-0 text-accent w-3 text-center cursor-pointer mr-0.5"
                        aria-hidden="true"
                    >
                        <span x-show="!expanded">⮞</span>
                        <span x-show="expanded">⮟</span>
                    </span>
                @else
                    <span
                        class="text-[12px] shrink-0 w-3 text-center mr-0.5"
                        aria-hidden="true"
                    >&#8199;</span>
                @endif

                <a
                    href="{{ $item->route ? route($item->route) : $item->url }}"
                    class="truncate flex-1"
                >{{ __($item->label) }}</a>
            </div>
        @endif
    @else
        {{-- Container item (no route) --}}
        @if($collapsed)
            <button
                type="button"
                @click="expanded = !expanded"
                class="flex items-center justify-center w-full h-8 rounded-none transition text-link {{ $hasActiveChild ? 'text-ink' : 'hover:bg-surface-subtle' }}"
                aria-label="{{ __($item->label) }}"
                title="{{ __($item->label) }}"
            >
                <x-icon :name="$iconName" class="w-[1.125rem] h-[1.125rem]" />
            </button>
        @else
            <div
                @click="expanded = !expanded"
                class="flex items-center gap-0.5 w-full px-1 py-px text-sm rounded-none cursor-pointer transition text-link {{ $hasActiveChild ? 'font-medium' : 'font-normal hover:bg-surface-subtle' }}"
            >
                <span class="text-[12px] shrink-0 text-accent w-3 text-center" aria-hidden="true">
                    <span x-show="!expanded">⮞</span>
                    <span x-show="expanded">⮟</span>
                </span>

                <span class="truncate">{{ __($item->label) }}</span>
            </div>
        @endif
    @endif

    {{-- Children (recursive) --}}
    @if(count($children) > 0)
        <ul
            x-show="expanded"
            x-transition
            class="{{ $collapsed ? 'ml-0 mt-0 space-y-0' : 'ml-3 mt-0 space-y-0' }}"
        >
            @foreach($children as $child)
                <x-menu.item
                    :item="$child['item']"
                    :isActive="$child['is_active']"
                    :hasActiveChild="$child['has_active_child']"
                    :children="$child['children']"
                    :collapsed="$collapsed"
                />
            @endforeach
        </ul>
    @endif
</li>
