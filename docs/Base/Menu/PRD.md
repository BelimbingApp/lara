# Menu System — Consolidated PRD

**Document Type:** PRD (single source of truth)
**Status:** Consolidated v1
**Last Updated:** 2026-02-09
**Author:** BLB Team

---

## 1. Executive Summary

**What:** A dynamic, auto-discovered, hierarchical menu system where modules self-register navigation via convention. Supports two contexts (Business, Admin), VS Code-inspired UI, and cache-only storage.

**Why:** Replace hardcoded Blade sidebar to scale cleanly to 100–500+ items without merge conflicts, enable module isolation, and align with BLB's strategic programming philosophy.

---

## 2. Goals and Scope

### Phase Goals (Now)
- Auto-discovery of module and extension menus via convention
- Two-context design: Business (default) and Admin
- Hierarchical tree with position-based ordering
- VS Code-inspired UI (Activity Bar + Sidebar tree)
- Cache-only storage; rebuild via Artisan

### Non-Goals (Deferred)
- Permission-based visibility
- Drag-and-drop reordering
- `before`/`after` positioning
- AI panel, badges, favorites

### Success Targets
- 100% of core modules use discovery; 0 hardcoded items
- Cached render < 10ms; discovery < 500ms
- ≤ 3 clicks to any item; search to item < 5 seconds (when search ships)

---

## 3. Users and Stories

### Administrators
- Switch between Business and Admin menus
- Navigate via collapsible tree; parent of active auto-expands
- Search to quickly locate items (P1)

### Developers (Modules, Extensions)
- Define items in a `menu.php` with parent-child and position
- Items appear in correct context and order
- Extend or override existing items (ID-based, last-wins)

---

## 4. Functional Requirements

### Registration and Discovery
- System scans menu definition files by convention:
  - `app/Modules/{Layer}/{Module}/Menu/menu.php`
  - `extensions/{vendor}/{extension}/Menu/menu.php`
- Discovery runs on cache clear or explicit command; invalid files log warnings (non-fatal)

### Structure and Schema
- Each item has:
  - `id` (unique, e.g., `admin.geonames.postcodes`)
  - `label` (string or translation key)
  - `context` (`'admin'` or `'business'`)
  - Optional: `icon`, `route` or `url`, `parent`, `position` (integer, default: 1000)
- ID naming: `{context}.{module}[.{submodule}...]`
- Duplicate IDs: last definition wins (enables extension override)
- Items without route are containers (toggle only)
- Items may have both route and children only if UX affordance is clear; default: container OR link

### Context Switching
- UI toggle (Activity Bar) between Business and Admin
- Default context: Business
- Context persisted (session); UI state (expanded nodes) persisted locally (localStorage)

### Rendering and Interaction
- Collapsible tree; active item highlighted; parent chain auto-expands
- Optional search box filters visible items (P1)
- Responsive: desktop always visible; tablet collapsible; mobile overlay

### Validation and Safety
- Detect and fail-fast on circular parents
- Log and skip items with missing parents or invalid routes (graceful)

---

## 5. Non-Functional Requirements

### Performance
- Cached menu build/render < 10ms
- Discovery and cache rebuild < 500ms for typical repo
- Support 500+ items without UX degradation (search and collapse mitigate)

### Reliability and Ergonomics
- Discovery tolerant to partial errors; logs are actionable
- Simple local development: optional cache bypass in local

---

## 6. Architecture and Key Decisions

### Decisions (Confirmed)

**Build custom** (not third-party packages); align to BLB module structure and SeederRegistry precedent

**Position-based ordering only** (integer); suggested ranges:
- 0–99: core framework items
- 100–999: core modules
- 1000–9999: business modules
- 10000+: extensions

**Cache-only storage;** source of truth in code (`menu.php`)

**VS Code-inspired UI:** Activity Bar + Sidebar tree

**Context state via session** (not URL)

**Blade + Alpine** for UI state; Livewire not required for menu shell

### Essential Technical Patterns

**Auto-Discovery Service:**
- Scans module and extension paths for `menu.php`
- Collects raw arrays; logs invalid files

**Registry:**
- Normalizes and validates items; merges with last-wins by ID
- Stores raw normalized items in cache (registry cache)

**Builder:**
- Filters by context; resolves parent-child tree
- Sorts siblings by position; computes active and has-active-child flags
- Caches per-context built tree

### Directory Conventions

**Framework code:**
- `app/Base/Menu/`: MenuServiceProvider, MenuRegistry, MenuBuilder, Services/MenuDiscoveryService
- Console/Commands: `menu:discover`, `menu:clear`, `menu:list`
- Blade components: `resources/views/components/menu/{activity-bar,sidebar,tree,item,search,user-profile}.blade.php`

---

## 7. UI/UX Specifications

### Activity Bar (Left)
- Icons: Business (building), Admin (cog)
- Active has left border and highlight
- Click switches context; preference persisted

### Sidebar
- **Sections:** Search (P1), Menu Tree (scrollable), Quick actions (Settings, Logout), User profile
- **Interaction:** Click link navigates; click container toggles expand/collapse; chevron toggles
- **Accessibility:** Tree roles and ARIA; keyboard navigation (arrows, Enter/Space, Home/End)

### Responsive Behavior
- **Desktop (lg+):** Activity Bar + Sidebar visible
- **Tablet (md):** Activity Bar visible; Sidebar collapsible
- **Mobile (sm):** Hamburger triggers full-screen overlay; focus management and backdrop

### Visual Design
- **VS Code-inspired layout:** Fixed Activity Bar (48px) + Sidebar (256px) + Main content
- **Color tokens:** Base colors from daisyUI/MaryUI; active uses primary
- **Typography:** Menu items text-sm; active font-medium
- **Icons:** Heroicons outline (menu items w-4 h-4, Activity Bar w-6 h-6)
- **Animation:** Expand/collapse 200ms ease-out; hover 150ms; context switch 150ms crossfade

---

## 8. Caching Strategy

### Layers and Keys
- **Registry cache:** Raw normalized items (`blb.menu.registry`)
- **Built tree cache:** Per-context resolved trees (`blb.menu.tree.admin`, `blb.menu.tree.business`)

### Invalidation Triggers
- `php artisan menu:discover` — scan + rebuild registry; clear built trees
- `php artisan menu:clear` — clear all menu caches
- Deploy hook runs `menu:discover`

### Local Development
- Optional config to bypass cache in local for instant feedback

---

## 9. Menu Definition Format

**File:** `app/Modules/{Layer}/{Module}/Menu/menu.php`

**Structure (minimal example):**

```php
return [
    'items' => [
        [
            'id' => 'admin.geonames',
            'label' => 'Geonames',
            'context' => 'admin',
            'icon' => 'heroicon-o-globe-alt',
            'position' => 100,
        ],
        [
            'id' => 'admin.geonames.postcodes',
            'label' => 'Postcodes',
            'context' => 'admin',
            'route' => 'admin.geonames.postcodes.index',
            'parent' => 'admin.geonames',
            'position' => 20,
        ],
    ],
];
```

**Field Notes:**
- `label` accepts translation key or plain string
- `route` or `url` optional; container if neither provided
- `permission` field may exist but is ignored until permission system ships (deferred)

---

## 10. Implementation Phases

### Phase 1 (MVP) — S (1–2 days)
- Auto-discovery (modules + extensions), registry, validation (duplicate IDs, circular parents), builder (context filter, position sort, active chain)
- Cache-only storage and Artisan commands (`menu:discover`, `menu:clear`, `menu:list`)
- VS Code-inspired shell: Activity Bar (Business/Admin), Sidebar tree (collapsible), active highlighting
- Context in session; default Business
- **Deliverable:** Functional menu system with Geonames example

### Phase 2 (UX Polish) — M (1–3 days)
- Search/filter in sidebar
- Persist expanded/collapsed state (localStorage)
- Auto-expand to active item on load
- Responsive mobile overlay with focus management
- Performance hardening and UX accessibility passes
- **Deliverable:** Production-ready menu with search and mobile support

### Phase 3 (Extensions and Hardening) — M (1–3 days)
- Robust extension override tests
- Edge-case validations (missing parents/routes) with clear logs
- Benchmarks at 500+ items; profiling
- **Deliverable:** Battle-tested menu system ready for extension ecosystem

---

## 11. Risks and Mitigations

| Risk | Mitigation |
|------|------------|
| Cache invalidation bugs | Simple, explicit commands; deploy hook; local bypass option |
| Over-nesting UX complexity | Recommend practical max ~3 levels; rely on search; review large trees |
| Alpine state drift | Keep UI state minimal; persist only expansion and context; avoid complex coupling |
| Scope creep | Adhere to non-goals; phase gates |

---

## 12. Alignment with BLB Strategy

- **Deep modules, simple interfaces:** Complex resolution hidden behind discovery/registry/builder; modules only write `menu.php`
- **Auto-discovery by convention:** Zero manual registration
- **YAGNI and KISS:** Position integers only; cache-only; Blade + Alpine; defer permissions and advanced UI
- **Strategic programming:** Invest in quality design now; enables scalable module ecosystem

---

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 0.1 | 2026-01-20 | AI + Kiat | Initial multi-file draft |
| 1.0 | 2026-02-09 | AI + Kiat | Consolidated PRD; removed redundancy and YAGNI features |
