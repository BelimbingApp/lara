# Menu System — Implementation Plan

**Document Type:** Implementation Plan
**Status:** Ready for Implementation
**Last Updated:** 2026-02-09
**Based On:** PRD.md (Phase 1 MVP)

---

## 1. Objectives

**Goal:** Build auto-discovered menu system that enables modules to self-register navigation items.

**Deliverables:**
1. Menu discovery service (scans `Config/menu.php` files)
2. Menu registry (validates, merges, caches)
3. Menu builder (resolves tree, marks active)
4. Blade components (sidebar with collapsible tree)
5. Example implementation (Geonames module menu)

**Success Criteria:**
- ✅ Geonames module adds `Config/menu.php` → menu appears
- ✅ Development: refresh page = see changes (no cache)
- ✅ Production: cached for performance
- ✅ Active item highlighted, parent chain expanded
- ✅ Zero hardcoded menu items in Blade

---

## 2. Implementation Order

### Step 1: Data Structures (30 min)
**File:** `app/Base/Menu/MenuItem.php`

Value object representing a single menu item:
```php
readonly class MenuItem {
    public function __construct(
        public string $id,
        public string $label,
        public ?string $icon,
        public ?string $route,
        public ?string $url,
        public ?string $parent,
        public int $position,
        public ?string $permission,
    ) {}
    
    public static function fromArray(array $data): self;
    public function hasRoute(): bool;
}
```

---

### Step 2: Discovery Service (1 hour)
**File:** `app/Base/Menu/Services/MenuDiscoveryService.php`

Scans filesystem for menu definition files:

**Responsibilities:**
- Glob pattern: `app/Modules/*/*/Config/menu.php`
- Glob pattern: `extensions/*/*/Config/menu.php`
- Load each file, validate structure
- Extract metadata (module name, path)
- Return Collection of raw arrays

**Error handling:**
- Invalid files → log warning, skip file
- Missing `items` key → log warning, skip
- PHP syntax error → catch, log, skip

**Validation:**
- Each item must have `id` and `label`
- `position` defaults to 1000 if missing
- Other fields optional

---

### Step 3: Registry (1 hour)
**File:** `app/Base/Menu/MenuRegistry.php`

Central registry for menu items:

**Responsibilities:**
- Accept items from discovery
- Normalize to MenuItem objects
- Validate (circular parents, duplicate IDs)
- Store in cache (registry cache)
- Provide access to all items

**Cache key:** `blb.menu.registry`

**Methods:**
```php
public function registerFromDiscovery(Collection $items): void;
public function validate(): array;  // Returns array of errors
public function getAll(): Collection;
public function loadFromCache(): bool;
public function persist(): void;
public function clear(): void;
```

**Validation rules:**
- Detect circular parent references
- Duplicate IDs: last wins (log warning)
- Parent doesn't exist: log warning, item becomes root

---

### Step 4: Builder (1 hour)
**File:** `app/Base/Menu/MenuBuilder.php`

Builds hierarchical tree from flat items:

**Responsibilities:**
- Accept flat items from registry
- Resolve parent-child relationships
- Sort siblings by position
- Mark active item (based on current route)
- Mark parent chain of active item
- Cache built tree

**Cache key:** `blb.menu.tree`

**Output structure:**
```php
[
    [
        'item' => MenuItem,
        'is_active' => bool,
        'has_active_child' => bool,
        'children' => [
            // Recursive structure
        ],
    ],
]
```

**Methods:**
```php
public function build(?string $currentRoute = null): array;
protected function buildTree(Collection $items, ?string $parentId = null): array;
protected function markActive(array $tree, ?string $currentRoute): array;
```

---

### Step 5: Service Provider (30 min)
**File:** `app/Base/Menu/MenuServiceProvider.php`

Registers services and provides menu to views:

**Responsibilities:**
- Register MenuDiscoveryService, MenuRegistry, MenuBuilder as singletons
- View composer for layouts
- Environment-aware caching logic

**View Composer:**
```php
View::composer('components.layouts.app', function ($view) {
    $registry = app(MenuRegistry::class);
    
    // Environment-aware caching
    if (app()->environment('local')) {
        // Always discover fresh
        $discovery = app(MenuDiscoveryService::class);
        $registry->registerFromDiscovery($discovery->discover());
    } else {
        // Load from cache or discover
        if (!$registry->loadFromCache()) {
            $discovery = app(MenuDiscoveryService::class);
            $registry->registerFromDiscovery($discovery->discover());
            $registry->persist();
        }
    }
    
    $builder = app(MenuBuilder::class);
    $view->with('menuTree', $builder->build(request()->route()?->getName()));
});
```

**Register in:** `bootstrap/providers.php`

---

### Step 6: Blade Components (2 hours)

#### 6.1 Sidebar Component
**File:** `resources/views/components/menu/sidebar.blade.php`

Container for menu tree + footer:
```blade
<aside class="w-64 bg-base-200 h-screen flex flex-col">
    <!-- Menu Tree (scrollable) -->
    <nav class="flex-1 overflow-y-auto p-4">
        <x-menu.tree :items="$menuTree" />
    </nav>
    
    <!-- Footer: Settings, Logout, User Profile -->
    <div class="p-4 border-t border-base-300">
        <a href="{{ route('settings') }}" class="...">Settings</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button>Logout</button>
        </form>
        <div class="mt-4">
            {{ auth()->user()->name }}
        </div>
    </div>
</aside>
```

#### 6.2 Tree Component (Recursive)
**File:** `resources/views/components/menu/tree.blade.php`

Renders hierarchical menu items:
```blade
@props(['items'])

<ul class="menu menu-compact">
    @foreach($items as $node)
        <x-menu.item 
            :item="$node['item']"
            :isActive="$node['is_active']"
            :hasActiveChild="$node['has_active_child']"
            :children="$node['children']"
        />
    @endforeach
</ul>
```

#### 6.3 Item Component
**File:** `resources/views/components/menu/item.blade.php`

Single menu item with Alpine.js state:

**Features:**
- Chevron for containers (▶/▼)
- Icon display (if provided)
- Active highlighting
- Recursive children rendering
- localStorage for expanded state

```blade
@props(['item', 'isActive', 'hasActiveChild', 'children'])

<li x-data="{
    expanded: $persist({{ $hasActiveChild ? 'true' : 'false' }})
        .as('menu_{{ $item->id }}')
}">
    @if($item->hasRoute())
        <!-- Link item -->
        <a href="{{ route($item->route) }}" 
           class="{{ $isActive ? 'active bg-primary/10 text-primary' : '' }}">
            @if($item->icon)
                <x-icon name="{{ $item->icon }}" class="w-4 h-4" />
            @endif
            {{ $item->label }}
        </a>
    @else
        <!-- Container item -->
        <div @click="expanded = !expanded" class="cursor-pointer">
            <span x-show="!expanded">▶</span>
            <span x-show="expanded">▼</span>
            @if($item->icon)
                <x-icon name="{{ $item->icon }}" class="w-4 h-4" />
            @endif
            {{ $item->label }}
        </div>
    @endif
    
    @if(count($children) > 0)
        <ul x-show="expanded" x-collapse class="ml-4">
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
```

---

### Step 7: Framework Menu Roots (15 min)
**File:** `app/Base/Menu/Config/menu.php`

Provide root sections (Administration, Business Operations):
```php
<?php

return [
    'items' => [
        [
            'id' => 'admin',
            'label' => 'Administration',
            'icon' => 'heroicon-o-cog-6-tooth',
            'position' => 0,
        ],
        [
            'id' => 'business',
            'label' => 'Business Operations',
            'icon' => 'heroicon-o-building-office',
            'position' => 100,
        ],
    ],
];
```

**Discovery includes Base modules:**
- `app/Base/*/Config/menu.php`

---

### Step 8: Example Implementation (30 min)
**File:** `app/Modules/Core/Geonames/Config/menu.php`

Example module menu:
```php
<?php

return [
    'items' => [
        [
            'id' => 'admin.geonames',
            'label' => 'Geonames',
            'icon' => 'heroicon-o-globe-alt',
            'parent' => 'admin',
            'position' => 200,
        ],
        [
            'id' => 'admin.geonames.countries',
            'label' => 'Countries',
            'icon' => 'heroicon-o-flag',
            'route' => 'admin.geonames.countries.index',
            'parent' => 'admin.geonames',
            'position' => 10,
        ],
        [
            'id' => 'admin.geonames.postcodes',
            'label' => 'Postcodes',
            'icon' => 'heroicon-o-map-pin',
            'route' => 'admin.geonames.postcodes.index',
            'parent' => 'admin.geonames',
            'position' => 20,
        ],
    ],
];
```

**Create placeholder routes/controllers** for testing.

---

### Step 9: Update Layout (15 min)
**File:** `resources/views/components/layouts/app.blade.php`

Replace hardcoded sidebar with menu component:
```blade
<div class="flex">
    <x-menu.sidebar :menuTree="$menuTree" />
    
    <main class="flex-1">
        {{ $slot }}
    </main>
</div>
```

---

## 3. File Structure Summary

```
app/Base/Menu/
├── MenuServiceProvider.php       # Service provider, view composer
├── MenuItem.php                   # Value object
├── MenuRegistry.php               # Registry with caching
├── MenuBuilder.php                # Tree builder
└── Services/
    └── MenuDiscoveryService.php   # File scanner

app/Base/Menu/Config/
└── menu.php                       # Root sections (admin, business)

app/Modules/Core/Geonames/Config/
└── menu.php                       # Example module menu

resources/views/components/menu/
├── sidebar.blade.php              # Sidebar container
├── tree.blade.php                 # Recursive tree
└── item.blade.php                 # Single item with Alpine

bootstrap/providers.php
└── Add MenuServiceProvider
```

---

## 4. Testing Strategy

### Manual Testing (MVP)
1. Create Geonames menu file
2. Refresh page in local environment
3. Verify menu appears in sidebar
4. Click items, verify navigation
5. Check active highlighting
6. Collapse/expand, verify state persists
7. Test in production mode (caching)

### Automated Testing (Phase 2)
- Discovery finds menu files
- Registry validates circular parents
- Builder creates correct tree structure
- Duplicate IDs: last wins

---

## 5. Implementation Checklist

**Core Infrastructure:**
- [ ] MenuItem value object
- [ ] MenuDiscoveryService
- [ ] MenuRegistry with caching
- [ ] MenuBuilder with tree resolution
- [ ] MenuServiceProvider

**UI Components:**
- [ ] sidebar.blade.php
- [ ] tree.blade.php (recursive)
- [ ] item.blade.php (with Alpine state)

**Configuration:**
- [ ] Base/Menu/Config/menu.php (root sections)
- [ ] Update discovery to scan Base modules
- [ ] Register MenuServiceProvider

**Example:**
- [ ] Geonames/Config/menu.php
- [ ] Placeholder routes/controllers for Geonames

**Integration:**
- [ ] Update app.blade.php layout
- [ ] Test in local (no cache)
- [ ] Test in production mode (cached)

**Documentation:**
- [ ] Update AGENTS.md with menu conventions
- [ ] Developer guide: how to add menu items

---

## 6. Estimated Effort

| Task | Effort | Notes |
|------|--------|-------|
| Data structures | 30 min | MenuItem class |
| Discovery service | 1 hour | File scanning, error handling |
| Registry | 1 hour | Validation, caching |
| Builder | 1 hour | Tree resolution, active marking |
| Service provider | 30 min | View composer, registration |
| Blade components | 2 hours | Sidebar, tree, item with Alpine |
| Framework roots | 15 min | Base menu config |
| Example (Geonames) | 30 min | Menu + placeholder routes |
| Integration | 15 min | Update layout |
| Testing | 30 min | Manual verification |

**Total:** ~7.5 hours (1 day)

---

## 7. Dependencies & Prerequisites

**Required:**
- ✅ Laravel 12+ installed
- ✅ MaryUI/daisyUI configured
- ✅ Alpine.js available
- ✅ Heroicons available

**Routes needed (placeholders):**
- `admin.geonames.countries.index`
- `admin.geonames.postcodes.index`

**Can create minimal controllers returning views** for testing.

---

## 8. Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Circular parent detection fails | High | Write explicit test case before implementation |
| Cache not clearing in local | Medium | Add environment check early, test immediately |
| Alpine state persistence breaks | Low | Test localStorage early, fallback to no persistence |
| Discovery too slow (>500ms) | Low | Profile early, optimize glob patterns if needed |

---

## 9. Post-Implementation

**Immediate next steps:**
1. Add menu to Company module (`Config/menu.php`)
2. Add menu to Employee module
3. Add menu to User module
4. Build actual CRUD screens for these modules

**Future enhancements (Phase 2+):**
- Search/filter (if > 30 items)
- Context switching (if > 50 items)
- Permission filtering (when permission system exists)
- `menu:list` command (debugging)

---

## 10. Open Questions for Implementation

| Question | Options | Recommendation |
|----------|---------|----------------|
| Should MenuItem be a class or array? | Class = type safety<br>Array = simple | Class (readonly) |
| Store cache forever or TTL? | Forever until cleared<br>24 hours | Forever (invalidate on deploy) |
| What if route doesn't exist? | Skip item<br>Show disabled<br>Allow | Show disabled (future permission filter handles) |
| Root sections hardcoded or discoverable? | Hardcoded<br>Discovered | Hardcoded in Base/Menu/Config for now |

---

## 11. Code Conventions

**Follow AGENTS.md:**
- Single quotes for strings
- Return type declarations
- No magic methods (use `query()`)
- Double-space PHPDoc alignment
- Avoid comments unless complex logic

**Alpine.js:**
- Use `x-data`, `x-show`, `x-collapse`
- Use `$persist().as('key')` for localStorage
- Keep state minimal

**Blade:**
- Component props via `@props()`
- Use MaryUI/daisyUI classes
- Follow existing component patterns

---

## 12. Success Definition

**MVP is complete when:**
1. Geonames module has `Config/menu.php`
2. Menu appears in sidebar automatically
3. Clicking "Countries" navigates to route
4. Active item is highlighted
5. Expand/collapse works and persists
6. Local environment: edit menu.php → refresh → see changes
7. Production mode: menu is cached

**Ready to start implementation!**
