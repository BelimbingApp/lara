# Architecture Migration Plan

**Status:** Phase 3 Complete
**Priority:** Strategic Investment
**Principles:** Ousterhout's "A Philosophy of Software Design"

## Context
BLB uses a modular architecture defined in `docs/architecture/file-structure.md`. The `app/` directory now contains only three top-level directories:

```
app/
├── Base/        # Framework infrastructure (Database, Menu, Routing)
├── Modules/     # Business process modules (Core/*)
└── Providers/   # Laravel bootstrap (AppServiceProvider, VoltServiceProvider)
```

All legacy Laravel scaffold directories (`Http/`, `Models/`, `Console/`, `Livewire/`) have been eliminated.

## Completed

### Phase 3: Module Consolidation ✅
- [x] `VerifyEmailController` → `Modules/Core/User/Controllers/Auth/`
- [x] `Logout` action → `Modules/Core/User/Actions/`
- [x] `DatabaseConnectionRecovery` middleware → `Base/Database/Middleware/`
- [x] Deleted empty base `Controller.php` (no consumers)
- [x] Deleted `app/Http/`, `app/Livewire/` directories
- [x] Deleted premature ops commands (`belimbing:backup`, `belimbing:update`, `belimbing:create-admin`) and `app/Console/`
- [x] Removed speculative `app/Admin/` from architecture spec (YAGNI — admin UI lives inside each module)

## Deferred (YAGNI)

These phases are not yet needed. Introduce them only when a concrete consumer appears:

- **Support Layer** (`app/Support`): No universal traits/helpers exist yet to consolidate.
- **Base Foundation** (`app/Base/Foundation`): No shared base Model/Controller needed — modules use Laravel's directly.
- **Base Extension** (`app/Base/Extension`): Extension system not yet needed.
- **Infrastructure** (`app/Infrastructure`): No cross-cutting infra services (caching, queues) beyond what Laravel provides.

## What's Next

The directory structure migration is complete. Focus shifts to **deepening existing modules** rather than creating new architectural layers:

1. **Route consolidation** — `routes/auth.php` contains User-domain auth routes that could move into `Modules/Core/User/Routes/`. Evaluate whether the route discovery system should handle auth routes or if they stay global (they need `guest` middleware, which may differ from module route patterns).
2. **Module depth** — Invest in making existing modules (Company, User, Geonames, etc.) deeper: richer domain logic, better encapsulation, clearer public APIs between modules.
3. **New Base modules as needed** — When cross-cutting concerns emerge organically (events, configuration, security), extract them into `Base/` modules with real consumers driving the interface design.

---

## Principles (Reference)

### Strategic Programming
Invest in design quality to lower future development costs. Do not create abstractions without consumers.

### Deep Modules
Modules should provide powerful functionality through simple interfaces. The `app/Modules` directory is the primary mechanism for depth.

### Destructive Evolution
Rewrite APIs freely; do not create migration paths for data. Rip and replace over adaptor layers.
