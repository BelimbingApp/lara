@props(['item', 'isActive', 'hasActiveChild', 'children'])

<li 
    x-data="{ expanded: {{ $hasActiveChild ? 'true' : 'false' }} }"
    class="relative"
>
    @if($item->hasRoute())
        {{-- Link item --}}
        <a 
            href="{{ $item->route ? route($item->route) : $item->url }}" 
            class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition text-zinc-900 dark:text-zinc-100 {{ $isActive ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-zinc-200 dark:hover:bg-zinc-800' }}"
        >
            @if(count($children) > 0)
                <span class="text-xs flex-shrink-0">
                    <span x-show="!expanded">▶</span>
                    <span x-show="expanded">▼</span>
                </span>
            @endif
            
            <span>{{ $item->label }}</span>
        </a>
    @else
        {{-- Container item (no route) --}}
        <div 
            @click="expanded = !expanded" 
            class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg cursor-pointer transition text-zinc-900 dark:text-zinc-100 {{ $hasActiveChild ? 'font-semibold' : 'hover:bg-zinc-200 dark:hover:bg-zinc-800' }}"
        >
            <span class="text-xs flex-shrink-0">
                <span x-show="!expanded">▶</span>
                <span x-show="expanded">▼</span>
            </span>
            
            <span>{{ $item->label }}</span>
        </div>
    @endif

    {{-- Children (recursive) --}}
    @if(count($children) > 0)
        <ul 
            x-show="expanded"
            x-transition
            class="ml-6 mt-1 space-y-1"
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
