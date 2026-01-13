# Table Naming Standards

**Document Type:** Architecture Specification
**Purpose:** Define database table naming conventions for Belimbing framework
**Last Updated:** 2026-01-13

## Overview

Belimbing uses a layered table naming convention to clearly distinguish between framework infrastructure, business modules, and vendor extensions. This prevents naming conflicts and makes table ownership immediately clear.

## Naming Convention by Layer

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

```
Table Prefixes by Layer:

base_*                      # Framework infrastructure (app/Base/)
├── [module_name]*          # Core business modules (app/Modules/Core/)
├── [module_name]*          # Business modules (app/Modules/Business/)
└── vendor_module_*         # Vendor extensions (extensions/vendor/)

Visual Example:
base_extensions             # Framework's extension registry
base_permissions            # Framework's permission system
companies                   # Core Company module
users                       # Core User module
erp_orders                  # Business ERP module
crm_leads                   # Business CRM module
sbg_companies               # SBG vendor extension
acme_integrations           # ACME vendor extension
```

## Rationale

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

## Migration Notes

During the initialization phase, some existing tables may not follow this convention:
- `core_geonames_countries` should be renamed to `geonames_countries`
- Any future framework infrastructure should use `base_*` prefix

See `database/AGENTS.md` for migration best practices.

## Related Documentation

- `docs/architecture/file-structure.md` - Complete file structure
- `docs/todo/architecture-migration.md` - Migration roadmap
- `database/AGENTS.md` - Database migration guidelines