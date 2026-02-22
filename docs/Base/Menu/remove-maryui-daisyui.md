# Remove MaryUI & daisyUI — Branch Documentation

**Branch:** `remove-maryui-daisyui`
**Status:** In Progress
**Last Updated:** 2026-02-09

---

## Why Remove MaryUI & daisyUI?

### Problem Statement

MaryUI and daisyUI were initially adopted for rapid prototyping, but they create friction for BLB's AI-native vision:

**1. AI Coding Challenges:**
- AI models need library-specific documentation to use MaryUI/daisyUI correctly
- Component APIs change between versions (breaking AI-generated code)
- Universal Tailwind knowledge works across all AI models
- Less context needed in prompts (no library docs)

**2. Framework Philosophy Misalignment:**
- BLB is a **framework** for adopters to customize
- Component libraries impose opinionated designs
- Adopters must learn library conventions on top of Laravel
- Plain Tailwind = full control, no library constraints

**3. Maintenance Burden:**
- Extra dependencies to track and update
- Library bugs/issues outside our control
- Larger bundle size
- More abstraction layers to debug

**4. Strategic Programming Principle:**
- "Deep modules, simple interfaces" - Tailwind is simpler than component libraries
- Investment in custom components pays off long-term
- Full control over UI patterns and conventions

### Decision

**Remove MaryUI/daisyUI, adopt plain Tailwind CSS.**

**Benefits:**
- ✅ AI can generate any UI without library docs
- ✅ Simpler stack for adopters to learn
- ✅ Full styling control
- ✅ Smaller dependencies
- ✅ Future-proof (Tailwind is stable, widely adopted)

---

## What Changed

Implemented auto-discovered menu system while removing UI library dependencies.

---

## Major Changes

### 1. Menu System Implemented (New Feature)

**Files Created:**
- `app/Base/Menu/MenuItem.php` - Value object for menu items
- `app/Base/Menu/MenuRegistry.php` - Registry with caching and validation
- `app/Base/Menu/MenuBuilder.php` - Tree builder with active marking
- `app/Base/Menu/Services/MenuDiscoveryService.php` - Auto-discovery from Config/menu.php
- `app/Base/Menu/MenuServiceProvider.php` - Service registration and view composer
- `app/Base/Menu/Config/menu.php` - Root sections (Administration, Business Operations)

**Features:**
- ✅ Auto-discovery from `app/Modules/{Layer}/{Module}/Config/menu.php`
- ✅ Hierarchical collapsible tree with Alpine.js
- ✅ Environment-aware caching (local = no cache, production = cached)
- ✅ Active item highlighting with parent chain expansion
- ✅ Position-based ordering
- ✅ Circular parent detection
- ✅ Extension override support (last wins)

**Example Implementation:**
- `app/Modules/Core/Geonames/Config/menu.php` - Geonames admin menu

---

### 2. MaryUI & daisyUI Removed

**Dependencies Removed:**
- `daisyui` (package.json)
- `robsontenorio/mary` (composer.json)

**Replaced With:**
- Plain Tailwind CSS
- Alpine.js (interactivity)
- Custom `<x-ui.*>` components

**Stats:**
- 41 files changed
- 761 insertions, 1016 deletions (net -255 lines)
- 0 MaryUI/daisyUI classes remaining
- 0 MaryUI components remaining

---

### 3. New UI Component Library

**Created:** `resources/views/components/ui/`

- `button.blade.php` - Variants: primary, secondary, danger, ghost, outline
- `card.blade.php` - Card containers with optional title
- `input.blade.php` - Form inputs with label and error display
- `badge.blade.php` - Status badges (success, danger, warning, info)
- `checkbox.blade.php` - Checkboxes with labels
- `radio.blade.php` - Radio buttons
- `modal.blade.php` - Modal dialogs with Alpine.js
- `icon.blade.php` - Heroicon wrapper (currently simplified to emoji)

---

### 4. Layout Redesign (VS Code-Inspired)

**Structure:**
```
┌────────────────────────────────────────────┐
│  Top Bar (56px)                            │
│  - App name, search, user info             │
├─────────────┬──────────────────────────────┤
│  Sidebar    │  Main Content                │
│  (256px)    │  (flex-1)                    │
│             │                              │
│  [Menu]     │  [Page content]              │
│             │                              │
│  [Settings] │                              │
│  [Logout]   │                              │
├─────────────┴──────────────────────────────┤
│  Status Bar (24px)                         │
│  - Environment, debug mode, time, version  │
└────────────────────────────────────────────┘
```

**Files Created:**
- `resources/views/components/layouts/top-bar.blade.php`
- `resources/views/components/layouts/status-bar.blade.php`

**Files Modified:**
- `resources/views/components/layouts/app.blade.php` - Complete restructure

---

### 5. Menu Components

**Created:** `resources/views/components/menu/`

- `sidebar.blade.php` - Sidebar container with menu tree and footer
- `tree.blade.php` - Recursive tree renderer
- `item.blade.php` - Individual menu item with expand/collapse

**Features:**
- Collapsible tree with text arrows (▶/▼)
- Active item highlighting (blue background)
- Hover states
- Recursive rendering for unlimited depth
- Alpine.js state management

---

### 6. Color Scheme

**Standardized on explicit Tailwind colors:**

| Element | Light Mode | Dark Mode |
|---------|------------|-----------|
| **Backgrounds** |
| Page background | white | zinc-900 |
| Sidebar | zinc-50 | zinc-950 |
| Content area | zinc-50 | zinc-900 |
| Top bar | white | zinc-900 |
| Status bar | zinc-100 | zinc-950 |
| **Text** |
| Primary text | zinc-900 | zinc-100 |
| Secondary text | zinc-600 | zinc-400 |
| **Interactive** |
| Hover | zinc-200 | zinc-800 |
| Active | blue-600 | blue-600 |
| **Borders** |
| Light | zinc-200 | zinc-800 |

---

### 7. Views Updated

**Layouts:**
- `resources/views/components/layouts/app.blade.php` - Full restructure
- `resources/views/components/layouts/auth.blade.php` - Tailwind classes

**Pages:**
- `resources/views/dashboard.blade.php` - Plain Tailwind cards
- `resources/views/placeholder.blade.php` - Created for menu testing

**Livewire Components:**
- `resources/views/livewire/users/*.blade.php` - All user management views
- `resources/views/livewire/settings/*.blade.php` - All settings views
- `resources/views/livewire/auth/*.blade.php` - All auth views

**All replaced:** MaryUI components → plain Tailwind or `<x-ui.*>` components

---

### 8. CSS Cleanup

**File:** `resources/css/app.css`

**Removed:**
- `@plugin "daisyui"`
- MaryUI source references
- All daisyUI/MaryUI-specific styles

**Kept:**
- Tailwind import
- Custom theme (zinc colors, fonts)
- Minimal base styles

---

### 9. Documentation Updated

**Files Modified:**
- `AGENTS.md` - Updated tech stack (removed MaryUI mention)
- `docs/brief.md` - Updated platform description
- `docs/Base/Menu/PRD.md` - Removed MaryUI/daisyUI references

**Files Created:**
- `docs/Base/Menu/implementation-plan.md` - Menu system implementation guide
- `docs/development/remove-maryui-daisyui-plan.md` - Removal strategy document

---

## Known Issues

### Icons
- Currently using emoji icons (⚙️, 🚪, ▶, ▼)
- SVG icons (blade-heroicons) attempted but caused rendering issues
- **Todo:** Implement proper icon system or keep emojis for simplicity

### Mobile Layout
- Mobile sidebar not yet updated to new layout
- Still uses old structure
- **Todo:** Update mobile drawer to match desktop structure

---

## Testing Status

**Tested:**
- ✅ Dashboard loads with menu
- ✅ Menu navigation (Countries, Postcodes)
- ✅ Active item highlighting
- ✅ Expand/collapse works
- ✅ Dark mode rendering
- ✅ Top bar, sidebar, status bar visible
- ❌ Mobile responsive (not tested)
- ❌ User management pages (not tested)
- ❌ Settings pages (not tested)
- ❌ Auth pages (not tested)

---

## Benefits Achieved

1. **Simpler Stack:** Plain Tailwind (no library-specific knowledge needed)
2. **AI-Friendly:** Universal Tailwind classes (all AI models understand)
3. **Auto-Discovery:** Modules add `Config/menu.php` → menu appears
4. **Module Isolation:** Each module owns its menu definition
5. **Professional UI:** VS Code-inspired layout
6. **Better Performance:** Fewer dependencies, smaller bundle size

---

## Next Steps

### Before Merging to Main:

1. **Fix text contrast** - Ensure all text visible in dark mode
2. **Test all pages** - Dashboard, users, settings, auth
3. **Mobile layout** - Update mobile drawer structure
4. **Icon decision** - Keep emojis or implement proper SVG icons
5. **Final polish** - Spacing, typography, hover states

### Future Enhancements:

1. Search/filter in menu (if > 30 items)
2. Context switching (if > 50 items)
3. Permission-based filtering (when permission system exists)
4. Proper icon system (blade-heroicons or inline SVG library)

---

## Rollback Plan

If issues arise:
```bash
git checkout main
```

This discards all changes on `remove-maryui-daisyui` branch.

---

## Files Summary

**Created:** 19 new files
- Menu system (7 files)
- UI components (8 files)
- Layout components (2 files)
- Documentation (2 files)

**Modified:** 32 existing files
- All views updated to Tailwind
- Dependencies removed
- Documentation updated

**Deleted:** 0 files (old components still exist but unused)

---

**Status:** Branch ready for review and testing before potential merge to main.
