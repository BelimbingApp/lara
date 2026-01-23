# Architecture Migration Plan

**Status:** Draft
**Priority:** Strategic Investment
**Principles:** Ousterhout's "A Philosophy of Software Design"

## Context
The project is in the **Initialization Phase**. The current codebase uses a standard Laravel structure (`app/Http`, `app/Models`, etc.) with a nascent `app/Modules` directory. The goal is to migrate to the `app/Base`, `app/Extensions` architecture defined in `docs/architecture/file-structure.md`.

## Principles Alignment

### 1. Strategic Programming
**Directives:** "Invest in design quality to lower future development costs."
**Application:**
- We will not just "move files"; we will define the **interfaces** for the new layers (`Base`, `Support`) first.
- We accept that this migration slows down feature development temporarily to create a scalable foundation.
- **Trade-off:** We are paying an upfront cost now (velocity dip) to avoid the "Standard Laravel Monolith" trap where everything is coupled.

### 2. Deep Modules
**Directives:** "Modules should provide powerful functionality through simple interfaces."
**Application:**
- **Bad:** Creating `app/Base/Service.php` that just wraps `app/Http/Controllers`.
- **Good:** Creating `app/Base` components that hide the complexity of valid extensions, event hooking, and configuration cascading.
- The `app/Modules` directory is the primary mechanism for depth. Each module must encapsulate its own routes, views, and logic, exposing only a minimal API to other modules.

### 3. Destructive Evolution
**Directives:** "Rewrite APIs freely; do not create migration paths for data."
**Application:**
- We will likely delete existing "standard" Controllers and Models if they don't fit the modular pattern.
- We will not waste time creating adaptors for the old structure. It is a "rip and replace" operation.

---

## Migration Roadmap

### Phase 1: Foundation (The Support Layer)
Before building the Base layer, we need the utilities that Base depends on.
- [ ] Create `app/Support` structure.
- [ ] Migrate universal traits/helpers from `app/Services` to `app/Support`.

### Phase 2: The Base Layer Abstractions
Define the interfaces that modules will implement.
- [ ] Create `app/Base/Foundation` (Base Models, Controllers).
- [ ] Create `app/Base/Extension` (The contract for what a Module *is*).
- [ ] **Design Trade-off:** We will define these interfaces *before* we have many consumers, which risks "speculative generality" (a red flag). To mitigate, we will implement only what our *current* modules (`User`, `Company`) clearly need.

### Phase 3: Module Consolidation
Move "homeless" code into Modules.
- [ ] **Audit:** Identify logic in `app/Http/Controllers` that belongs to specific domains.
- [ ] **Migrate:** Move User logic to `app/Modules/Core/User`.
- [ ] **Migrate:** Move Company logic to `app/Modules/Core/Company`.
- [ ] **Cleanup:** `app/Http` and `app/Models` should be empty (or deleted) by the end of this phase.

### Phase 4: Infrastructure & Admin
- [ ] Create `app/Infrastructure` for Services that are not domain logic (Caching, Queues).
- [ ] Initialize `app/Admin` as a specialized module for system management.

---

## Technical Notes
- **Namespaces:** Use `App\Base`, `App\Modules`, etc. Update `composer.json` autoloading if necessary (though standard PSR-4 for `App\` covers this).
- **Service Providers:** Laravel 12 uses `bootstrap/providers.php`. We will likely need a `BaseServiceProvider` to bootstrap the custom directory loaders.
