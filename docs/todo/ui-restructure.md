# UI Restructure -- Tracking

> **Branch:** `ui-restructure`
> **Architecture doc:** `docs/architecture/ui-layout.md`
> **Created:** 2026-03-09

## Summary

Major UI restructuring: core/licensee directory separation, Volt removal (already done -- no Volt pages exist), layout zone changes, and new components.

## Phase 1: Structural Move (Foundation) ✅

- [x] Move `resources/views/` to `resources/core/views/`
- [x] Move `resources/css/` to `resources/core/css/`
- [x] Move `resources/js/` to `resources/core/js/`
- [x] Update `vite.config.js` -- input paths, refresh globs, VITE_THEME_DIR support
- [x] Create `config/view.php` -- override Laravel default view paths to `resources/core/views`
- [x] Update `config/livewire.php` -- all `resource_path()` calls
- [x] Update `app/Providers/VoltServiceProvider.php` -- resource paths (or remove if Volt fully dropped)
- [x] Split `resources/css/app.css` into `resources/core/css/tokens.css` + `resources/core/css/components.css`
- [x] Create new `resources/app.css` entry point importing core then licensee
- [x] Update `@source` directives for new relative paths
- [x] Update `@vite` in `resources/core/views/partials/head.blade.php`
- [x] Update `stubs/livewire.layout.stub` @vite reference
- [x] Update AGENTS.md files (root, resources/views/, docs/, app/Modules/Core/AI/)
- [x] Update `.cursor/rules/ui-architect.mdc`
- [x] Create licensee `resources/custom/` scaffold (empty css/views/js dirs)
- [x] Verify build works (`npm run build`)
- [x] Verify `php artisan serve` works

## Phase 2: Layout Zone Changes ✅

- [x] Remove Impersonation Banner component (zone A eliminated)
- [x] Move impersonation warning to Status Bar (`text-status-danger`)
- [x] Update Top Bar: remove search placeholder, add Lara chat trigger
- [x] Update Status Bar: remove time placeholder, add impersonation warning
- [x] Set up `wire:navigate` for shell persistence (only Main Content swaps)
- [x] Implement drag-resizable sidebar with icon rail snap
  - [x] Drag handle (invisible until hover, `col-resize` cursor)
  - [x] Continuous width range (`w-14` to `w-72`)
  - [x] Auto-collapse to icon rail below threshold
  - [x] Persist width to `localStorage`
  - [x] Toggle button snaps between rail and last width
- [x] Implement sidebar pinned section
  - [x] Pinned items section at top of sidebar
  - [x] Drag-reorder within pinned section (HTML5 drag-and-drop, Alpine handlers)
  - [x] Pin/unpin action on menu items (hover icon button)
  - [x] Per-user storage (server-side, ordered list of menu item IDs)
  - [x] Migration for user pinned items (`user_pinned_menu_items` table)
  - [x] Model (`UserPinnedMenuItem`), controller (`PinnedMenuItemController`), routes
  - [x] Optimistic UI with server reconciliation (fetch-based toggle/reorder)
  - [x] CSRF meta tag added to head partial
- [x] Alphabetically order main menu items (remove manual position)

## Phase 3: New Components (~80%)

- [x] Build `<x-ui.tabs>` page-level tabs component
  - [x] Alpine.js client-side tab switching
  - [x] Active tab reflected in URL (hash)
  - [x] Accessible (ARIA roles, keyboard navigation)
- [x] Implement Lara Chat mobile full-screen takeover
  - [x] Full viewport below Top Bar on small screens
  - [x] Close button dismissal
- [ ] Create licensee directory scaffolding command (`blb:theme:init`)

## Phase 4: Documentation Updates ✅

- [x] `docs/architecture/file-structure.md` -- already up to date (no old paths found)
- [x] `docs/architecture/authorization.md` -- impersonation banner ref updated to status bar
- [x] `docs/architecture/broadcasting.md` -- echo.js path refs (discovered during sweep)
- [x] `docs/guides/theming.md` -- all 23 path references + override model
- [x] `docs/development/theme-customization.md` -- all 20 path references
- [x] `docs/development/palette-preference.md` -- CSS path refs
- [x] `docs/development/agent-context.md` -- view path refs
- [x] `docs/guides/development-setup.md` -- Vite config refs
- [x] `docs/tutorials/vite-roles.md` -- all path references
- [x] `docs/tutorials/volt-and-blade.md` -- view path refs
- [x] `docs/Base/Menu/remove-maryui-daisyui.md` -- all 13 path refs
- [x] `docs/Base/Menu/PRD.md` -- path ref
- [x] `docs/modules/menu-prd.md` -- path ref
- [x] `docs/todo/tool-workspace-ui.md` -- path ref
- [x] `docs/architecture/caddy-development-setup.md` -- Vite config refs
- [x] Regenerate IDE helper files (`.phpstorm.meta.php`, `_ide_helper.php`, `_ide_helper_models.php`)

## Decisions Log

| Decision | Rationale |
|----------|-----------|
| No Volt | Collapses controller/view boundary; blocks independent licensee overrides; agent convenience irrelevant |
| `resources/core/` + `resources/{licensee}/` | Clear ownership boundary; safe upgrades; visible customization |
| `.env` VITE_THEME_DIR | Bridge between Vite (build-time) and PHP (runtime); default `custom` |
| Impersonation in Status Bar | Consolidates system warnings in one zone; eliminates dedicated banner zone |
| Lara in Top Bar | Replaces search placeholder; gives AI assistant prominent position |
| Livewire `wire:navigate` | Already in stack; morphs DOM preserving Alpine state in shell |
| Drag-resizable sidebar | Continuous width + icon rail snap; drag handle invisible until hover |
| Pinned menu section | User-curated quick access; drag-reorderable; main menu stays alphabetical |
| Page-level tabs only | Solves complex model UX; no application-level multi-screen tabs |
| Lara Chat mobile fullscreen | Floating overlay unusable on small screens |
| Tabs use URL hash | `history.replaceState` with `#tab-id`; survives refresh; responds to back/forward via `hashchange` |
