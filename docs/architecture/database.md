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

### Development vs. Production Seeders

Seeders fall into two categories with distinct naming and placement conventions:

| Category | Purpose | Location | Naming |
| :--- | :--- | :--- | :--- |
| **Production** | Reference/config data needed in all environments | `Database/Seeders/` | `{Entity}Seeder` |
| **Development** | Fake/test data for local development only | `Database/Seeders/Dev/` | `Dev{Description}Seeder` |

**Production seeders** populate structural data derived from config (e.g., `DepartmentTypeSeeder`, `RelationshipTypeSeeder`). They are registered via `RegistersSeeders` in migrations and run automatically on `migrate --seed` in all environments.

**Development seeders** create realistic fake data for UI development and manual testing. They live in a `Dev/` subdirectory and use a `Dev` class name prefix so they are immediately recognizable in CLI output, logs, and seeder registry. They should **never** be registered via `RegistersSeeders` in migrations — run them explicitly:

```bash
# Run a specific dev seeder (note the Dev/ subdirectory in the path)
php artisan migrate --seed --seeder=Company/Dev/DevCompanyAddressSeeder

# All dev seeders in a module are discovered with --seed if pending
php artisan migrate --seed --module=Company
```

**Conventions:**
-   Dev seeders use `firstOrCreate` patterns for idempotency.
-   Dev seeders may depend on production seeders having run first (e.g., dev data references `RelationshipType` records).
-   Never add dev seeders to production deployment scripts.

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

-   `app/Base/Database/AGENTS.md`: Migrate/seeding CLI (e.g. `migrate --seed`, `--module`, `--seeder`).
-   `docs/architecture/overview.md`: Full project directory layout.
