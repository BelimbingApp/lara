# Database Conventions

**Document Type:** Architecture Specification
**Purpose:** Define database table and migration file naming conventions for Belimbing framework
**Last Updated:** 2026-01-20

## Overview

Belimbing uses layered naming conventions for both migration files and database tables to clearly distinguish between framework infrastructure, business modules, and vendor extensions. This prevents naming conflicts and makes ownership immediately clear.

---

## Migration Loading and Discovery

**Implementation:** Migrations are auto-discovered from module directories when migration commands execute. The `App\Base\Database\ServiceProvider` registers custom migration commands that use `InteractsWithModuleMigrations` trait to discover `Database/Migrations` directories via glob patterns. Migration files are sorted by filename (timestamp prefix) order by Laravel.

### Benefits

1. **No Manual Registration**: Glob-based discovery finds all module migrations automatically
2. **Self-Contained Modules**: Each module owns its database layer (migrations, seeders, factories)
3. **Enforced Ordering**: Year-based naming ensures Base → Core → Business → Extensions
4. **Deep Modules**: Migrations live with the code they support

### Module-Specific Migration Loading

BLB's custom `migrate` command supports a `--module` option to selectively load migrations from specific modules:

```bash
# Run migrations for a single module (case-sensitive)
php artisan migrate --module=Geonames

# Run migrations for multiple modules (comma-delimited)
php artisan migrate --module=Geonames,Users,Company

# Run migrations for all modules (wildcard)
php artisan migrate --module=*
```

**Conventions:**

1. **Comma-Delimited:** Multiple modules are separated by commas (e.g., `--module=Foo,Bar,Baz`)
2. **Case-Sensitive:** Module names must match the directory name exactly (e.g., `Geonames`, not `geonames`)
3. **Wildcard Support:** Use `*` to load all modules (e.g., `--module=*`)
4. **Auto-Discovery Paths:** Searches `app/Base/*/Database/Migrations` and `app/Modules/*/*/Database/Migrations`

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

```bash
0001_01_01_xxxxxx  # Base Layer - Database module (01_01)
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
- **Module (MM_DD):** Each Base module gets its own `MM_DD` identifier
- **Format:** `0001_MM_DD_xxxxxx` where `MM_DD` identifies the module
- **Purpose:** Framework infrastructure modules (Extensions, Permissions, Config, Audit, etc.)
- **Module assignments:**
  - `0001_01_01_xxxxxx` - Database module (`app/Base/Database/`)
  - `0001_01_02_xxxxxx` - To assign
  - ... (up to `0001_12_31_xxxxxx` = 365 possible Base modules)
- **Location:** `app/Base/{Module}/Database/Migrations/` (auto-discovered)
- **Examples:**
  - `app/Base/Database/Database/Migrations/0001_01_01_000000_create_base_database_seeders_table.php`

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

All modules (within Base, Core, Business) are self-contained with their own database layer. Migrations are auto-discovered from `{Module}/Database/Migrations/` by `App\Base\Database\ServiceProvider`.

**Base Module Structure (`app/Base/{Module}/`):**
```bash
app/Base/{ModuleName}/
├── Database/
│   └── Migrations/           # Module-specific migrations (0001_MM_DD_*)
│       ├── 0001_MM_DD_000000_create_{table}_table.php
│       └── ...
├── ServiceProvider.php       # Module service provider
└── ...                       # Other module files
```

**Core/Business Module Structure (`app/Modules/{Layer}/{Module}/`):**
```bash
app/Modules/{Layer}/{ModuleName}/
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

**Example - Database Module (Base):**
```bash
app/Base/Database/
├── Database/
│   └── Migrations/
│       └── 0001_01_01_000000_create_base_database_seeders_table.php
├── MigrateCommand.php
└── ServiceProvider.php
```

**Example - Geonames Module (Core):**
```bash
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
```bash
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

**Pattern:** `base_{module}_{entity}`

**Purpose:** Framework infrastructure modules (not business logic)

**Structure:** `app/Base/{Module}/` contains framework modules, each with tables prefixed `base_{module}_*`

**Examples:**
```bash
# Extensions Module (app/Base/Database/)
base_database_seeders            # Seeders registry

# Permissions Module (app/Base/Permissions/)
base_permissions_definitions     # Permission definitions
base_permissions_roles           # Role definitions
base_permissions_role_users      # Role assignments

# Config Module (app/Base/Config/)
base_config_scopes              # Scope-based configuration
base_config_values              # Configuration values

# Audit Module (app/Base/Audit/)
base_audit_logs                 # Audit trail
base_audit_changes              # Change tracking

# Workflow Module (app/Base/Workflow/)
base_workflow_definitions       # Workflow engine definitions
base_workflow_transitions       # Status transition rules

# Events Module (app/Base/Events/)
base_events_listeners           # Event listener registry
base_events_subscriptions       # Event subscriptions

# Schema Module (app/Base/Schema/)
base_schema_extensions          # Dynamic schema extension metadata
```

**Characteristics:**
- Framework-owned infrastructure modules
- Each module in `app/Base/{Module}/` owns tables with `base_{module}_*` prefix
- Never created by end users
- Meta-tables for the framework itself
- Module name provides namespace within Base layer
- Should never conflict with business domain tables

---

### 2. Core Module Tables (`app/Modules/Core/`)

**Pattern:** `{module}_{entity}` (no `core_` prefix)

**Purpose:** Core business domain modules essential to the framework

**Structure:** `app/Modules/Core/{Module}/` contains core business modules

**Examples:**
```bash
# Geonames Module (app/Modules/Core/Geonames/)
geonames_countries          # Countries
geonames_admin1             # Administrative divisions

# Company Module (app/Modules/Core/Company/)
companies                    # Main entity
company_relationships        # Relationships
company_relationship_types   # Relationship types
company_external_accesses    # External access

# User Module (app/Modules/Core/User/)
users                        # Main entity
user_permissions             # User-level permissions (business, not base_permissions_*)
user_profiles                # User profiles

# Workflow Module (app/Modules/Core/Workflow/)
workflow_instances          # Workflow instances (business workflows, not base_workflow_*)
workflow_steps              # Workflow steps
```

**Characteristics:**
- Business domain data
- Foundational to the framework
- Module name provides namespace
- No `core_` prefix (directory location indicates they're core)
- No `base_` prefix (these are business domain, not infrastructure)

---

### 3. Business Module Tables (`app/Modules/Business/`)

**Pattern:** `{module}_{entity}`

**Purpose:** Business-specific modules added by users

**Structure:** `app/Modules/Business/{Module}/` contains user-added business modules

**Examples:**
```bash
# ERP Module (app/Modules/Business/ERP/)
erp_orders                  # Orders
erp_invoices                # Invoices
erp_products                # Products

# CRM Module (app/Modules/Business/CRM/)
crm_leads                   # Leads
crm_opportunities           # Opportunities
crm_campaigns               # Campaigns

# HR Module (app/Modules/Business/HR/)
hr_employees                # Employees
hr_departments              # Departments
hr_payroll                  # Payroll
```

**Characteristics:**
- User-added business modules
- Can be installed/uninstalled
- Module name provides namespace and prevents conflicts
- Same naming pattern as Core modules (distinction is directory location)

---

### 4. Vendor Extension Tables (`extensions/vendor/`)

**Pattern:** `{vendor}_{module}_{entity}`

**Purpose:** Third-party vendor extensions

**Structure:** `extensions/{vendor}/{Module}/` contains vendor-specific modules

**Examples:**
```bash
# SBG Vendor Extensions
sbg_companies_extensions        # SBG vendor - Company module extension
sbg_companies_custom_fields     # SBG vendor - Company custom fields
sbg_crm_leads                   # SBG vendor - CRM module extension

# ACME Vendor Extensions
acme_integrations_api           # ACME vendor - API integrations
acme_integrations_webhooks      # ACME vendor - Webhook handlers
acme_workflows_custom           # ACME vendor - Custom workflows
```

**Characteristics:**
- Vendor name provides top-level namespace
- Module name provides second-level namespace
- Prevents conflicts between vendors and across modules
- Easy to identify third-party data
- Can extend both Base and Module layers

---

## Complete Hierarchy

### Migration Years by Layer

```bash
0001_MM_DD_xxxxxx           # Base Layer (app/Base/{Module}/Database/Migrations/)
0002_MM_DD_xxxxxx           # Core Modules (app/Modules/Core/{Module}/Database/Migrations/)
0010+_MM_DD_xxxxxx          # Business Modules (app/Modules/Business/{Module}/Database/Migrations/)
2026+_MM_DD_xxxxxx          # Extensions (real years) - MM_DD can be actual date or module identifier
```

**Note:** All migrations are auto-discovered from `{Module}/Database/Migrations/` directories by `App\Base\Database\ServiceProvider`.

### Table Naming Patterns by Layer

```bash
Base Layer: base_{module}_{entity}        # Base Layer: Framework infrastructure (app/Base/{Module}/)
Modules/Core Layer: {module}_{entity}     # Core Modules: Foundational business (app/Modules/Core/{Module}/)
Modules/Business Layer: {module}_{entity} # Business Modules: User-added business (app/Modules/Business/{Module}/)
Vendor Extensions Layer: {vendor}_{module}_{entity}    # Vendor Extensions: Third-party (extensions/{vendor}/{Module}/)
```

**Key Points:**
- **Base modules** use `base_{module}_*` to clearly distinguish infrastructure from business
- **Core & Business modules** use `{module}_*` (directory location provides layer context)
- **Vendor extensions** use `{vendor}_{module}_*` to prevent conflicts
- Module name always provides namespace within its layer

### Visual Example

**Tables:**
```bash
# Base Layer - Framework infrastructure modules
base_database_seeders               # Database module
base_extensions_hooks               # Extensions module
base_permissions_definitions        # Permissions module
base_permissions_roles              # Permissions module

# Core Modules - Foundational business
geonames_countries                  # Geonames module
companies                           # Company module
company_relationships               # Company module
users                               # User module

# Business Modules - User-added business
erp_orders                          # ERP module
crm_leads                           # CRM module

# Vendor Extensions - Third-party
sbg_companies_extensions            # SBG vendor
acme_integrations_api               # ACME vendor
```

---

## Rationale

### Why Year-Based Migration Ordering?

1. **Enforces Architecture**: Filesystem enforces Base → Core → Business order
2. **Visual Clarity**: Layer is immediately obvious from filename
3. **Scalability**: Easy to add modules without timestamp coordination
4. **No Tooling Required**: Works with Laravel's default behavior
5. **Define Errors Out of Existence**: Prevents accidental wrong ordering

### Why `base_{module}_*` for Framework Infrastructure?

1. **Semantic Clarity**: `base_*` clearly indicates foundational framework layer
2. **Consistency**: Matches `app/Base/{Module}/` directory structure
3. **Module Namespace**: Second segment provides module-level namespace (e.g., `base_permissions_*`, `base_extensions_*`)
4. **Distinction**: Framework infrastructure ≠ business modules
5. **Protection**: Users will never create `base_*` tables, avoiding conflicts
6. **Scalability**: Base layer can contain multiple modules, each with clear table ownership

### Why No `core_` for Core Modules?

1. **Directory Provides Context**: `app/Modules/Core/` already indicates "core"
2. **Module Name is Namespace**: `companies` belongs to Company module
3. **Business Domain First**: Even core modules are business domains, not framework plumbing
4. **Cleaner Schema**: Shorter table names, less redundancy

### Why `{vendor}_` for Extensions?

1. **Clear Ownership**: Immediately obvious who owns the data
2. **Conflict Prevention**: Multiple vendors can extend same domains
3. **Easy Identification**: Clear distinction from core/business modules

---

## Related Documentation

- `docs/architecture/file-structure.md` - Complete file structure and module organization
- `docs/todo/architecture-migration.md` - Migration roadmap
