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

---

## 1. Migration Architecture

### Auto-Discovery & Loading

Migrations are **auto-discovered** from module directories when migration commands execute. The `App\Base\Database\ServiceProvider` uses the `InteractsWithModuleMigrations` trait to scan:
-   `app/Base/*/Database/Migrations/`
-   `app/Modules/*/*/Database/Migrations/`

### Module-Specific Migrations

BLB extends the native `migrate` command with a `--module` option:

```bash
# Default: Load all modules (equivalent to --module=*)
php artisan migrate

# Single module (case-sensitive, matches directory name)
php artisan migrate --module=Geonames

# Multiple modules
php artisan migrate --module=Geonames,Users
```

**Behavior:**
-   **Case-Sensitive:** Module names must match the directory name exactly.
-   **Dependencies:** The command does *not* automatically resolve dependencies. You must migrate dependencies first or use `--module=*`.
-   **Core Tables:** Laravel core tables (in `database/migrations`) are always included.

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

BLB replaces the standard `DatabaseSeeder` with a **Seeder Registry** pattern. This allows modules to define their own seeding requirements self-sufficiently.

### The Registry (`base_database_seeders`)

A dedicated table tracks which seeders have run for which module.
-   **Registration:** Migrations register seeders via `registerSeeder()`. Seeders under `app/Modules/*/*/Database/Seeders/` are also **discovered** when you pass `--seed`; any not yet in the registry are added then, so they run even if the migration did not call `registerSeeder()`.
-   **State tracking:** Seeders have states: `pending` → `running` → `completed` (or `failed`).

### How to Implement

In your migration file:

```php
public function up(): void
{
    Schema::create('my_table', ...);

    // Register the seeder that populates this table
    $this->registerSeeder(\App\Modules\Core\MyModule\Database\Seeders\MySeeder::class);
}

public function down(): void
{
    // Unregister on rollback
    $this->unregisterSeeder(\App\Modules\Core\MyModule\Database\Seeders\MySeeder::class);

    Schema::dropIfExists('my_table');
}
```

### Execution (`migrate --seed`)

When you run `php artisan migrate --seed`:
1.  **Migrations Run:** New migrations execute `up()`, registering their seeders as `pending`.
2.  **Registry check:** The command checks `base_database_seeders` for any `pending` or `failed` seeders for the target module(s).
3.  **Execution:** It runs them in the order of their associated migration files.
4.  **Idempotency:** Seeders marked `completed` are **skipped**. This allows you to safely run `--seed` on every deployment.

### Manual Overrides

**Force run a specific seeder** (bypasses registry):
```bash
# Short form (no quoting needed)
php artisan migrate --seed --seeder=Geonames/CountrySeeder
# Or FQCN with single quotes so backslashes are preserved
php artisan migrate --seed --seeder='App\Modules\Core\Geonames\Database\Seeders\CountrySeeder'
```

**Retry failed seeders:**
Just run `migrate --seed` again. The registry knows it failed and will retry.

**Re-seed a module:**
Rollback and re-migrate. The rollback removes the `completed` record; the migrate adds a new `pending` record.
```bash
php artisan migrate:rollback --module=Geonames
php artisan migrate --module=Geonames --seed
```

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
│   │   └── CountrySeeder.php
│   └── Factories/
│       └── CityFactory.php
└── Models/
    └── City.php
```

---

## 5. Related Documentation

-   `app/Base/Database/AGENTS.md`: Migrate/seeding CLI (e.g. `migrate --seed`, `--module`, `--seeder`).
-   `docs/architecture/file-structure.md`: Full project directory layout.
-   `docs/Base/Database/migration-registry.md`: Registry of assigned module IDs (`MM_DD`).
-   `docs/development/database-conventions.md`: Detailed naming conventions (timestamp ranges, table patterns).
