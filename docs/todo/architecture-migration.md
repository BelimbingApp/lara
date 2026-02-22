# Architecture Migration Plan

**Status:** Structure Complete — Focus shifts to module depth
**Priority:** Strategic Investment
**Principles:** Ousterhout's "A Philosophy of Software Design"

## Context
BLB uses a modular architecture defined in `docs/architecture/file-structure.md`. The `app/` directory contains only three top-level directories:

```
app/
├── Base/        # Framework infrastructure (Database, Menu, Routing)
├── Modules/     # Business process modules (Core/*)
└── Providers/   # Laravel bootstrap (AppServiceProvider, VoltServiceProvider)
```

All legacy Laravel scaffold directories (`Http/`, `Models/`, `Console/`, `Livewire/`) have been eliminated. All domain routes live in their modules; `routes/web.php` contains only framework-shell routes (`/`, `dashboard`).

## Completed

### Phase 3: Module Consolidation ✅
- [x] `VerifyEmailController` → `Modules/Core/User/Controllers/Auth/`
- [x] `Logout` action → `Modules/Core/User/Actions/`
- [x] `DatabaseConnectionRecovery` middleware → `Base/Database/Middleware/`
- [x] Deleted empty base `Controller.php` (no consumers)
- [x] Deleted `app/Http/`, `app/Livewire/`, `app/Console/` directories
- [x] Deleted premature ops commands (`belimbing:backup`, `belimbing:update`, `belimbing:create-admin`)
- [x] Removed speculative `app/Admin/` from architecture spec (YAGNI)

### Route Consolidation ✅
- [x] `routes/auth.php` → `Modules/Core/User/Routes/web.php`
- [x] Settings routes (`settings/*`) → `Modules/Core/User/Routes/web.php`
- [x] Licensee setup route → `Modules/Core/Company/Routes/web.php`
- [x] `routes/web.php` reduced to framework shell (`/`, `dashboard`)
- [x] Fixed stale `App.Models.User` channel → `App.Modules.Core.User.Models.User`
- [x] Removed dead `belimbing:backup` schedule from `routes/console.php`

### Dev Infrastructure ✅
- [x] Removed `--kill-others` from `concurrently` (Reverb/queue crashes no longer kill web server)
- [x] Added Reverb config to `.env.example` and `.env` (missing `REVERB_APP_ID`/key/secret caused crashes)
- [x] Set `BROADCAST_CONNECTION=reverb` (was `log` while running Reverb)

## Deferred (YAGNI)

Introduce only when a concrete consumer appears:

- **Support Layer** (`app/Support`): No universal traits/helpers to consolidate yet.
- **Base Foundation** (`app/Base/Foundation`): No shared base Model/Controller needed.
- **Base Extension** (`app/Base/Extension`): Extension system not yet needed.
- **Infrastructure** (`app/Infrastructure`): No cross-cutting infra beyond Laravel defaults.

## What's Next

The structural migration is complete. Focus shifts to **deepening existing modules**:

1. **Module depth** — Richer domain logic, better encapsulation, clearer public APIs between modules. Existing modules (Company, User, Employee, Geonames, Address, Workflow) have routes, models, and views but thin domain logic.
2. **Test coverage** — 11 pre-existing test failures (Company code casing) need fixing. Expand test coverage as modules deepen.
3. **New Base modules as needed** — When cross-cutting concerns emerge organically (events, configuration, security), extract them into `Base/` modules with real consumers driving the interface design.

---

## Principles (Reference)

### Strategic Programming
Invest in design quality to lower future development costs. Do not create abstractions without consumers.

### Deep Modules
Modules should provide powerful functionality through simple interfaces. The `app/Modules` directory is the primary mechanism for depth.

### Destructive Evolution
Rewrite APIs freely; do not create migration paths for data. Rip and replace over adaptor layers.
