# Database Conventions

**Document Type:** Architecture Specification
**Purpose:** Define database table and migration file naming conventions for Belimbing framework
**Last Updated:** 2026-01-14

## Overview

Belimbing uses layered naming conventions for both migration files and database tables to clearly distinguish between framework infrastructure, business modules, and vendor extensions. This prevents naming conflicts and makes ownership immediately clear.

---

## Migration Loading and Discovery

**Implementation:** Migrations are auto-discovered from module directories via `App\Base\Database\ServiceProvider` (formerly `DatabaseServiceProvider`). The service provider looks for the directories `Database/Migrations` in each module to load the migrations files, which will be sorted by filename (timestamp prefix) order by Laravel.

### Benefits

1. **Zero HTTP Overhead**: Auto-discovery only runs during console commands (`runningInConsole()`)
2. **No Manual Registration**: Glob-based discovery finds all module migrations automatically
3. **Self-Contained Modules**: Each module owns its database layer (migrations, seeders, factories)
4. **Enforced Ordering**: Year-based naming ensures Base → Core → Business → Extensions
5. **Deep Modules**: Migrations live with the code they support

---

## Migration File Naming Conventions

Migration files use a **two-level hierarchy** in the timestamp format to enforce architectural layer ordering and module identification. This ensures migrations run in the correct sequence: Base → Core → Business → Extensions, with clear module-level separation within each layer.

### Timestamp Format and Hierarchy

Laravel migrations use the format `YYYY_MM_DD_HHMMSS`. Belimbing uses this format with a two-level hierarchy:

**Level 1 - Architectural Layer (Year):**
- **Year (`YYYY`)**: Designates the top architectural layer (Base, Core, Business, Extensions)
- Ensures proper execution order: Base runs before Core, Core before Business, etc.

**Level 2 - Module Identification (Month/Day):**
- **Month/Day (`MM_DD`)**: Designates the specific module within each architectural layer
- Allows up to 365 modules per architectural layer (provides ample room for growth)

**Level 3 - Migration Ordering (Time):**
- **Time (`HHMMSS`)**: Used for ordering migrations within each module
- Allows fine-grained sequencing of related migrations

```
0001_01_01_xxxxxx  # Base Layer (app/Base/) - module 01_01
0001_01_03_xxxxxx  # Base Layer - Events module (01_03)
0001_01_10_xxxxxx  # Base Layer - Configuration module (01_10)
0002_01_03_xxxxxx  # Core Layer - Geonames module (01_03)
0002_01_10_xxxxxx  # Core Layer - Company module (01_02)
0002_01_20_xxxxxx  # Core Layer - User module (01_01)
0002_02_01_xxxxxx  # Core Layer - reserved for future module
0010_01_01_xxxxxx  # Business Layer - ERP module (01_01)
0010_01_02_xxxxxx  # Business Layer - CRM module (01_02)
2026_01_01_xxxxxx  # Extensions (use real years)
```

**Rationale:**
- The year part provides architectural layer separation (Base → Core → Business → Extensions)
- The `MM_DD` part provides module-level identification within each layer, allowing up to 365 modules per architectural layer (far more than needed, but provides flexibility)
- The time part provides fine-grained ordering within each module
- This design scales well: if Core modules exceed 8 (years 0002-0009), `MM_DD` provides ample room for expansion without needing additional years

### Reserved Year Ranges

#### Base Layer (`app/Base/`)
- **Year:** `0001`
- **Module (MM_DD):** `01_01` (Base infrastructure is a single "module")
- **Format:** `0001_01_01_xxxxxx`
- **Purpose:** Framework infrastructure migrations
- **Examples:**
  - `0001_01_01_000000_create_base_extensions_table.php`
  - `0001_01_01_000001_create_base_permissions_table.php`
  - `0001_01_01_000002_create_base_audit_logs_table.php`

#### Core Modules (`app/Modules/Core/`)
- **Year:** `0002` (all Core modules share the same year)
- **Module (MM_DD):** Each module gets its own `MM_DD` identifier
- **Format:** `0002_MM_DD_xxxxxx` where `MM_DD` identifies the module
- **Purpose:** Core business domain module migrations
- **Module assignments:**
  - `0002_01_03_xxxxxx` - Geonames module (must come first - other modules depend on it)
  - `0002_01_10_xxxxxx` - Company module (depends on Geonames for country/admin1 data)
  - `0002_01_20_xxxxxx` - User module
  - `0002_02_01_xxxxxx` - Reserved for future core module (e.g., Workflow)
  - `0002_02_02_xxxxxx` - Reserved for future core module (e.g., Admin)
  - ... (up to `0002_12_31_xxxxxx` = 365 possible modules)
- **Examples:**
  - `0002_01_03_000000_create_geonames_countries_table.php` (Geonames module - runs first)
  - `0002_01_03_000001_create_geonames_admin1_table.php` (Geonames module)
  - `0002_01_10_000000_create_companies_table.php` (Company module - depends on Geonames)
  - `0002_01_10_000001_create_company_relationships_table.php` (Company module)
  - `0002_01_20_000000_create_users_table.php` (User module)

#### Business Modules (`app/Modules/Business/`)
- **Year:** `0010` and above (reserve years per major category, use MM_DD for modules within category)
- **Module (MM_DD):** Each module gets its own `MM_DD` identifier within the year
- **Format:** `YYYY_MM_DD_xxxxxx` where year identifies category, `MM_DD` identifies module
- **Purpose:** Business-specific module migrations
- **Year assignments (with MM_DD for modules within each year):**
  - `0010_01_01_xxxxxx` - ERP module (first module in year 0010)
  - `0010_01_02_xxxxxx` - Reserved for future ERP-related module
  - `0020_01_01_xxxxxx` - CRM module (first module in year 0020)
  - `0020_01_02_xxxxxx` - Reserved for future CRM-related module
  - `0030_01_01_xxxxxx` - HR module (first module in year 0030)
  - `0100_01_01_xxxxxx` - Custom business modules (first module in year 0100)
- **Examples:**
  - `0010_01_01_000000_create_erp_orders_table.php` (ERP module)
  - `0010_01_01_000001_create_erp_invoices_table.php` (ERP module)
  - `0030_01_01_000000_create_hr_employees_table.php` (HR module)

#### Extensions (`extensions/vendor/` or `extensions/custom/`)
- **Years:** Real years (e.g., `2026`, `2027`)
- **Format:** `2026_01_01_xxxxxx` (actual creation date)
- **Purpose:** Extension migrations loaded via Service Provider
- **Note:** Extensions are loaded separately via Service Providers, so their ordering is managed independently. Use real years for clarity.
- **Examples:**
  - `2026_01_15_120000_create_sbg_companies_table.php`
  - `2026_02_01_090000_create_acme_integrations_table.php`

### Time Part Usage

The time part (`xxxxxx` = `HHMMSS`) is used for ordering **within each module**:

- Within Base: `0001_01_01_000000`, `0001_01_01_000001`, etc.
- Within User module: `0002_01_01_000000`, `0002_01_01_000010`, etc.
- Within Company module: `0002_01_02_000000`, `0002_01_02_000100`, etc.

This allows fine-grained ordering within each module while maintaining clear layer and module separation.

### Benefits of Year-Based Ordering

1. **Visual Clarity:** The layer is immediately obvious from the filename
   - `0001_01_01_*` = Base
   - `0002_01_01_*` = Core
   - `0010_01_01_*` = Business

2. **Scalability:** Easy to add new modules without timestamp conflicts
   - Reserve years per module
   - No need to coordinate timestamps across modules

3. **Enforced Ordering:** Filesystem enforces execution order
   - Base runs before Core
   - Core runs before Business
   - Prevents accidental dependency issues

4. **Works with Laravel:** No additional tooling required
   - Laravel sorts migrations by filename prefix
   - `0001 < 0002 < 0010 < 0030` maintains correct order

### Module Database Structure

Each module is self-contained with its own database layer:

```
app/Modules/Core/{ModuleName}/
├── Database/
│   ├── Migrations/           # Module-specific migrations
│   │   ├── 0002_MM_DD_000000_create_{table}_table.php
│   │   ├── 0002_MM_DD_000001_create_{related}_table.php
│   │   └── ...
│   ├── Seeders/              # Module-specific seeders
│   │   ├── {ModuleName}Seeder.php
│   │   └── ...
│   └── Factories/            # Module-specific factories
│       ├── {Model}Factory.php
│       └── ...
└── Models/
    ├── {Model}.php
    └── ...
```

**Benefits:**
- **Encapsulation**: Migrations, seeders, and factories live with their models
- **Ownership**: Clear which module owns which database schema
- **Portability**: Can copy entire module directory with its database layer
- **Testability**: Module tests can seed their own data independently
- **Discovery**: Auto-discovered without manual registration

**Example - Geonames Module:**
```
app/Modules/Core/Geonames/
├── Database/
│   ├── Migrations/
│   │   ├── 0002_01_03_000000_create_geonames_countries_table.php
│   │   └── 0002_01_03_000001_create_geonames_admin1_table.php
│   └── Seeders/
│       ├── CountrySeeder.php
│       └── Admin1Seeder.php
└── Models/
    ├── Country.php
    └── Admin1.php
```

**Example - Company Module:**
```
app/Modules/Core/Company/
├── Database/
│   ├── Migrations/
│   │   ├── 0002_01_10_000000_create_companies_table.php
│   │   ├── 0002_01_10_000001_create_company_relationship_types_table.php
│   │   ├── 0002_01_10_000002_create_company_relationships_table.php
│   │   ├── 0002_01_10_000003_create_company_external_accesses_table.php
│   │   └── 0002_01_10_000004_add_company_id_to_users_table.php
│   ├── Seeders/
│   │   └── RelationshipTypeSeeder.php
│   └── Factories/
│       ├── CompanyFactory.php
│       └── RelationshipTypeFactory.php
└── Models/
    ├── Company.php
    ├── CompanyRelationship.php
    ├── ExternalAccess.php
    └── RelationshipType.php
```

---

## Table Naming Conventions

### 1. Base Layer Tables (`app/Base/`)

**Prefix:** `base_*`

**Purpose:** Framework infrastructure and meta-systems (not business logic)

**Examples:**
```
base_extensions              # Extension registry
base_extension_hooks         # Hook registrations
base_config_scopes           # Scope-based configuration
base_config_values           # Configuration values
base_permissions             # Permission definitions
base_roles                   # Role definitions
base_audit_logs              # Audit trail
base_workflow_definitions    # Workflow engine definitions
base_workflow_transitions    # Status transition rules
base_event_listeners         # Event listener registry
base_schema_extensions       # Dynamic schema extension metadata
```

**Characteristics:**
- Framework-owned infrastructure
- Never created by end users
- Meta-tables for the framework itself
- Should never conflict with business domain tables

---

### 2. Core Module Tables (`app/Modules/Core/`)

**Prefix:** Module name only (no `core_` prefix)

**Purpose:** Core business domain modules essential to the framework

**Examples:**
```
companies                    # Company module
company_relationships        # Company module
company_relationship_types   # Company module
company_external_accesses    # Company module

users                        # User module
user_permissions             # User module (business permissions, not base_permissions)

geonames_countries          # Geonames module
geonames_admin1             # Geonames module

workflow_instances          # Workflow module (business workflows, not base_workflow_definitions)
```

**Characteristics:**
- Business domain data
- Foundational to the framework
- Module name provides namespace
- No `core_` prefix (directory location indicates they're core)

---

### 3. Business Module Tables (`app/Modules/Business/`)

**Prefix:** Module name

**Purpose:** Business-specific modules added by users

**Examples:**
```
erp_orders                  # ERP module
erp_invoices                # ERP module
erp_products                # ERP module

crm_leads                   # CRM module
crm_opportunities           # CRM module
crm_campaigns               # CRM module

hr_employees                # HR module
hr_departments              # HR module
hr_payroll                  # HR module
```

**Characteristics:**
- User-added business modules
- Can be installed/uninstalled
- Module name prevents conflicts

---

### 4. Vendor Extension Tables (`extensions/vendor/`)

**Prefix:** `vendor_module_*`

**Purpose:** Third-party vendor extensions

**Examples:**
```
sbg_companies               # SBG vendor - company extension
sbg_company_custom_fields   # SBG vendor - company extension
sbg_crm_leads               # SBG vendor - CRM extension

acme_integrations           # ACME vendor - integration extension
acme_workflows              # ACME vendor - workflow extension
```

**Characteristics:**
- Clearly marked by vendor name
- Prevents conflicts between vendors
- Easy to identify third-party data

---

## Complete Hierarchy

### Migration Years by Layer

```
0001_01_01_xxxxxx           # Base Layer (app/Base/) - single module
0002_MM_DD_xxxxxx           # Core Modules (app/Modules/Core/) - MM_DD identifies module
0010+_MM_DD_xxxxxx          # Business Modules (app/Modules/Business/) - year = category, MM_DD = module
2026+_MM_DD_xxxxxx          # Extensions (real years) - MM_DD can be actual date or module identifier
```

### Table Prefixes by Layer

```
base_*                      # Framework infrastructure (app/Base/)
├── [module_name]*          # Core business modules (app/Modules/Core/)
├── [module_name]*          # Business modules (app/Modules/Business/)
└── vendor_module_*         # Vendor extensions (extensions/vendor/)
```

### Visual Example

**Migration Files:**
```
0001_01_01_000000_create_base_extensions_table.php          # Base layer
0001_01_01_000001_create_base_permissions_table.php         # Base layer
0002_01_03_000000_create_geonames_countries_table.php       # Core - Geonames module (runs first)
0002_01_03_000001_create_geonames_admin1_table.php         # Core - Geonames module
0002_01_10_000000_create_companies_table.php                # Core - Company module (depends on Geonames)
0002_01_10_000001_create_company_relationships_table.php    # Core - Company module
0002_01_20_000000_create_users_table.php                   # Core - User module
0010_01_01_000000_create_erp_orders_table.php               # Business - ERP module
0030_01_01_000000_create_hr_employees_table.php             # Business - HR module
2026_01_15_120000_create_sbg_companies_table.php            # Extension
```

**Tables:**
```
base_extensions             # Framework's extension registry
base_permissions            # Framework's permission system
companies                   # Core Company module
users                       # Core User module
erp_orders                  # Business ERP module
crm_leads                   # Business CRM module
sbg_companies               # SBG vendor extension
acme_integrations           # ACME vendor extension
```

---

## Rationale

### Why Year-Based Migration Ordering?

1. **Enforces Architecture**: Filesystem enforces Base → Core → Business order
2. **Visual Clarity**: Layer is immediately obvious from filename
3. **Scalability**: Easy to add modules without timestamp coordination
4. **No Tooling Required**: Works with Laravel's default behavior
5. **Define Errors Out of Existence**: Prevents accidental wrong ordering

### Why `base_*` for Framework Infrastructure?

1. **Semantic Clarity**: `base_*` clearly indicates foundational framework layer
2. **Consistency**: Matches `app/Base/` directory structure
3. **Distinction**: Framework infrastructure ≠ business modules
4. **Protection**: Users will never create `base_*` tables, avoiding conflicts

### Why No `core_` for Core Modules?

1. **Directory Provides Context**: `app/Modules/Core/` already indicates "core"
2. **Module Name is Namespace**: `companies` belongs to Company module
3. **Business Domain First**: Even core modules are business domains, not framework plumbing
4. **Cleaner Schema**: Shorter table names, less redundancy

### Why `vendor_` for Extensions?

1. **Clear Ownership**: Immediately obvious who owns the data
2. **Conflict Prevention**: Multiple vendors can extend same domains
3. **Easy Identification**: Clear distinction from core/business modules

---

## Related Documentation

- `docs/architecture/file-structure.md` - Complete file structure and module organization
- `docs/development/module-migration-restructure-summary.md` - Module-based migration restructure summary (2026-01-14)
- `database/AGENTS.md` - Database migration guidelines and best practices
- `docs/todo/architecture-migration.md` - Migration roadmap
