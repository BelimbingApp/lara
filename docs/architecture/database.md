# Database Architecture

**Document Type:** Architecture Specification
**Purpose:** Define the architectural standards for database migrations, seeding, and schema conventions in Belimbing.
**Last Updated:** 2026-02-08

## Overview

Belimbing (BLB) uses a **module-first database architecture**. Unlike standard Laravel applications where all migrations live in a single directory, BLB distributes database logic (migrations, seeders, factories) into independent modules.

To manage this complexity, the framework enforces:
1.  **Layered Naming Conventions**: To ensure correct execution order (Base → Core → Business).
2.  **Auto-Discovery**: To load migrations dynamically without manual registration.
3.  **Registry-Based Seeding**: To orchestrate seeding across modules without a monolithic `DatabaseSeeder`.

**Operational detail** (commands, `--module`, `--seed`, `--seeder`, dev workflow, RegistersSeeders trait, discovery paths): **[app/Base/Database/AGENTS.md](../../app/Base/Database/AGENTS.md)** is the single source. This document keeps only the high-level design, naming spec, registry table, and directory layout.

---

## 1. Migration Architecture

Migrations are **auto-discovered** from Base and Module directories when migration commands run. Module-specific migration and rollback use the `--module` option (case-sensitive). Laravel core tables in `database/migrations/` are always included.

For discovery paths, command list, and `--module` usage examples, see [app/Base/Database/AGENTS.md](../../app/Base/Database/AGENTS.md) (Key Components, Module Auto-Discovery Paths).

---

## 2. Naming & Execution Order

### Timestamp Conventions

Migration files use a **two-level hierarchy** in the timestamp to enforce architectural layering. This ensures Base infrastructure always exists before Core business logic.

**Format:** `YYYY_MM_DD_HHMMSS`

| Layer | Year Range | Purpose |
| :--- | :--- | :--- |
| **Laravel Core** | `0001` | Native Laravel tables (jobs, cache, sessions). |
| **Base** | `0100` | Framework infrastructure (Permissions, Audit, Config). |
| **Core** | `0200` | Foundational business domains (User, Company, Geonames). |
| **Business** | `0300+` | User-added business modules (ERP, CRM). |
| **Extensions** | `2026+` | Vendor extensions (uses real years). |

### Module Identification (MM_DD)

Within each year (Layer), the `MM_DD` component identifies the specific module.
*   **Base (0100):** `0100_01_01` (Database), `0100_01_03` (Events)
*   **Core (0200):** `0200_01_03` (Geonames), `0200_01_20` (User)

**Example ordering:**
1.  `0100_01_01_000000_create_base_database_seeders_table.php` (Base: seeder registry)
2.  `0200_01_03_000000_create_geonames_countries_table.php` (Core: Geonames)
3.  `0200_01_20_000000_create_users_table.php` (Core: User)
4.  Root `database/migrations/` (cache, jobs, sessions) is always included.

### Table Naming Conventions

Table names must prevent conflicts between modules and vendors.

| Layer | Pattern | Example |
| :--- | :--- | :--- |
| **Base** | `base_{module}_{entity}` | `base_permissions_roles` |
| **Core** | `{module}_{entity}` | `companies`, `users` |
| **Business** | `{module}_{entity}` | `erp_orders` |
| **Vendor** | `{vendor}_{module}_{entity}` | `sbg_companies_ext` |

**Rationale:**
-   `base_` prefix explicitly separates framework meta-data from business data.
-   Core/Business modules share the `{module}_` pattern as they are both business domains.
-   Vendor extensions use namespaces to safely extend core tables.

---

## 3. Seeding Architecture

BLB uses a **Seeder Registry** (`base_database_seeders` table). Migrations register seeders via `registerSeeder()` in `up()` and unregister in `down()`. Seeders can also be discovered from module `Database/Seeders/` when `--seed` is used. States: `pending` → `running` → `completed` | `failed` | `skipped`. Completed seeders are skipped on later runs.

For the RegistersSeeders trait, code examples, execution flow, dev vs production seeders, and CLI (`migrate --seed`, `--seeder`), see [app/Base/Database/AGENTS.md](../../app/Base/Database/AGENTS.md) (SeederRegistry, RegistersSeeders, Seeding Behavior, Development vs. Production Seeders, Development Workflow).

---

## 4. Directory Structure

All database assets live within their module to support portability.

```text
app/Modules/Core/Geonames/
├── Database/
│   ├── Migrations/
│   │   ├── 0200_01_03_000000_create_countries.php
│   │   └── 0200_01_03_000001_create_cities.php
│   ├── Seeders/
│   │   ├── CountrySeeder.php          # Production: reference data
│   │   └── Dev/
│   │       └── DevCitySeeder.php      # Development: fake test data
│   └── Factories/
│       └── CityFactory.php
└── Models/
    └── City.php
```

---

## 6. Migration Registry

This registry tracks the YYYY_MM_DD prefixes assigned to each module to prevent conflicts and document dependencies. Each module must have a unique MM_DD identifier within its architectural layer.

### Module Registry

| Prefix | Layer | Module | Dependencies |
|--------|-------|--------|--------------|
| `0001_01_01_*` | Base | Database | None |
| `0100_01_01_*` | Base | Other module | None |
| `0100_01_11_*` | Base | Authz | Database |
| `0200_01_03_*` | Modules/Core | Geonames | None |
| `0200_01_05_*` | Modules/Core | Address | Geonames |
| `0200_01_07_*` | Modules/Core | Company | Geonames, Address |
| `0200_01_09_*` | Modules/Core | Employee | Company, Address |
| `0200_01_20_*` | Modules/Core | User | Company, Employee |

### Business Module Categories (0300+)

**Format:** `YYYY_MM_DD_HHMMSS_description.php`

Years are grouped by business domain category.

| Year Range | Category | Reserved For | Status |
|------------|----------|--------------|--------|
| `0300` | ERP | Enterprise Resource Planning | 📂 Available |
| `0400` | CRM | Customer Relationship Management | 📂 Available |
| `0500` | HR | Human Resources | 📂 Available |
| `0600` | Finance | Financial Management | 📂 Available |
| `0700` | Inventory | Inventory Management | 📂 Available |
| `0800` | Manufacturing | Manufacturing/Production | 📂 Available |
| `0900` | Logistics | Shipping/Logistics | 📂 Available |
| `0910` | Analytics | Business Intelligence | 📂 Available |
| `0920` | Marketing | Marketing Automation | 📂 Available |
| `0930+` | Custom | Custom Business Modules | 📂 Available |

### Extensions (2026+)

**Format:** `YYYY_MM_DD_HHMMSS_description.php`

Extensions use real calendar years. The MM_DD can be the actual date or a module identifier.

**Location:** `extensions/{vendor}/{module}/Database/Migrations/`

**Discovery:** Loaded via extension service providers (not `ModuleMigrationServiceProvider`)

| Vendor | Module | Year | Example Prefix | Status |
|--------|--------|------|----------------|--------|
| (none) | - | 2026+ | `2026_01_15_*` | 📂 Available |

### Dependency Graph

```bash
Base Layer (0100)
  └─ cache, jobs (no dependencies)

Core Layer (0200)
  ├─ Geonames (01_03) → [no dependencies, runs first]
  ├─ Address (01_05) → [depends on: Geonames]
  ├─ Company (01_07) → [depends on: Address]
  ├─ User (01_20) → [depends on: Company]
  └─ Workflow (01_21) → [to do depends on: User]

Business Layer (0300+)
  └─ (modules depend on Core modules)
```

### Adding New Modules

1. **Choose Layer**
   - Core business logic → Layer `0200`
   - Business process → Layer `0300+`
   - Extension → Real year (e.g., `2026`)

2. **Select MM_DD**
   - Check this registry for available codes
   - Consider dependencies (dependent modules need higher MM_DD)
   - Update this registry with your assignment

3. **Create Migrations**
   - Use format: `YYYY_MM_DD_HHMMSS_description.php`
   - Place in `app/Modules/{Layer}/{Module}/Database/Migrations/`

4. **Document**
   - Add module to this registry
   - List dependencies
   - Document which tables are created

### Conflict Resolution

#### If Two Modules Need Same MM_DD

1. Check dependencies - dependent module must have higher MM_DD
2. If no dependencies, assign first-come-first-served
3. Update this registry immediately to prevent conflicts

#### If Module Dependencies Change

1. May need to renumber migrations
2. Use `migrate:fresh --seed` in development (destructive evolution; --seed required)
3. Update registry with new MM_DD assignment

---

## 7. Related Documentation

-   **[app/Base/Database/AGENTS.md](../../app/Base/Database/AGENTS.md)** — Single source for migrate/seeding CLI (`migrate`, `--seed`, `--module`, `--seeder`), RegistersSeeders trait, discovery paths, dev vs production seeders, development workflow, and database portability.
-   **docs/architecture/file-structure.md** — Full project directory layout.
