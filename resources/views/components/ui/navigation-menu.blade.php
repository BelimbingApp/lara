@props(['items' => []])

<ul class="menu menu-vertical bg-base-200 w-full p-2 rounded-box">
    @foreach($items as $item)
        <li>
            <a
                href="{{ $item['href'] ?? '#' }}"
                wire:navigate="{{ $item['wireNavigate'] ?? true }}"
                class="{{ request()->routeIs($item['route'] ?? '') ? 'active' : '' }}"
            >
                @if(isset($item['icon']))
                    <x-icon :name="$item['icon']" class="w-5 h-5" />
                @endif
                {{ $item['label'] ?? '' }}
            </a>
        </li>
    @endforeach
</ul>

