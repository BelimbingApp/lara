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
                <button 
                    @click.prevent="expanded = !expanded" 
                    class="w-4 h-4 flex items-center justify-center hover:bg-base-300 rounded"
                >
                    <x-icon x-show="!expanded" name="heroicon-m-chevron-right" class="w-3 h-3" />
                    <x-icon x-show="expanded" name="heroicon-m-chevron-down" class="w-3 h-3" />
                </button>
            @endif
            
            @if($item->icon)
                <x-icon name="{{ $item->icon }}" class="w-4 h-4" />
            @endif
            
            <span class="flex-1">{{ $item->label }}</span>
        </a>
    @else
        {{-- Container item (no route) --}}
        <div 
            @click="expanded = !expanded" 
            class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg cursor-pointer transition text-zinc-900 dark:text-zinc-100 {{ $hasActiveChild ? 'font-semibold' : 'hover:bg-zinc-200 dark:hover:bg-zinc-800' }}"
        >
            <div class="w-4 h-4 flex items-center justify-center">
                <x-icon x-show="!expanded" name="heroicon-m-chevron-right" class="w-3 h-3" />
                <x-icon x-show="expanded" name="heroicon-m-chevron-down" class="w-3 h-3" />
            </div>
            
            @if($item->icon)
                <x-icon name="{{ $item->icon }}" class="w-4 h-4" />
            @endif
            
            <span class="flex-1">{{ $item->label }}</span>
        </div>
    @endif

    {{-- Children (recursive) --}}
    @if(count($children) > 0)
        <ul 
            x-show="expanded"
            x-transition
            class="ml-4 mt-1 space-y-1"
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
