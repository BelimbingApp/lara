@props(['items'])

<ul class="menu menu-compact space-y-1">
    @foreach($items as $node)
        <x-menu.item 
            :item="$node['item']"
            :isActive="$node['is_active']"
            :hasActiveChild="$node['has_active_child']"
            :children="$node['children']"
        />
    @endforeach
</ul>
