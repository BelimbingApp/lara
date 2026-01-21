# Migration Registry

**Document Type:** Database Registry
**Purpose:** Track migration file prefixes, module assignments, and dependencies
**Last Updated:** 2026-01-21

## Overview

This registry tracks the YYYY_MM_DD prefixes assigned to each module to prevent conflicts and document dependencies. Each module must have a unique MM_DD identifier within its architectural layer.

---

## Layer Definitions

| Layer | Year Range | Purpose | Location |
|-------|------------|---------|----------|
| Base | `0001` | Framework infrastructure | `app/Base/Database/Migrations/` |
| Core | `0002` | Core business modules | `app/Modules/Core/{Module}/Database/Migrations/` |
| Business | `0010-0099` | Business process modules | `app/Modules/Business/{Module}/Database/Migrations/` |
| Extensions | `2026+` | Third-party extensions | `extensions/{vendor}/{module}/Database/Migrations/` |

---

## The Module Registry

This keeps track of all the migration files and their dependencies.


| Prefix | Layer | Module | Dependencies |
|--------|-------|--------|--------------|
| `0001_01_01_*` | Base | Database | None |
| `0002_01_03_*` | Modules/Core | Geonames | None |
| `0002_01_05_*` | Modules/Core | Address | Geonames |
| `0002_01_11_*` | Modules/Core | Company | Geonames, Address |
| `0002_01_13_*` | Modules/Core | Employee | Company, Address |
| `0002_01_17_*` | Modules/Core | User | Company, Employee |


## [Fluid] Business Modules (0010+)

**Format:** `YYYY_MM_DD_HHMMSS_description.php`

Years are grouped by business domain category.

### Registered Categories

| Year Range | Category | Reserved For | Status |
|------------|----------|--------------|--------|
| `0010` | ERP | Enterprise Resource Planning | 📂 Available |
| `0020` | CRM | Customer Relationship Management | 📂 Available |
| `0030` | HR | Human Resources | 📂 Available |
| `0040` | Finance | Financial Management | 📂 Available |
| `0050` | Inventory | Inventory Management | 📂 Available |
| `0060` | Manufacturing | Manufacturing/Production | 📂 Available |
| `0070` | Logistics | Shipping/Logistics | 📂 Available |
| `0080` | Analytics | Business Intelligence | 📂 Available |
| `0090` | Marketing | Marketing Automation | 📂 Available |
| `0100+` | Custom | Custom Business Modules | 📂 Available |


---

## [Fluid] Extensions (2026+)

**Format:** `YYYY_MM_DD_HHMMSS_description.php`

Extensions use real calendar years. The MM_DD can be the actual date or a module identifier.

**Location:** `extensions/{vendor}/{module}/Database/Migrations/`

**Discovery:** Loaded via extension service providers (not `ModuleMigrationServiceProvider`)

| Vendor | Module | Year | Example Prefix | Status |
|--------|--------|------|----------------|--------|
| (none) | - | 2026+ | `2026_01_15_*` | 📂 Available |

---

## [Fluid] Dependency Graph

```bash
Base Layer (0001)
  └─ cache, jobs (no dependencies)

Core Layer (0002)
  ├─ Geonames (01_03) → [no dependencies, runs first]
  ├─ Company (01_10) → depends on: Geonames
  └─ User (01_20) → [no dependencies]
       └─ Company adds FK to users (01_10_000004)

Business Layer (0010+)
  └─ (modules depend on Core modules)
```
---

## Adding New Modules

### Process

1. **Choose Layer**
   - Core business logic → Layer `0002`
   - Business process → Layer `0010+`
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

---

## Conflict Resolution

### If Two Modules Need Same MM_DD

1. Check dependencies - dependent module must have higher MM_DD
2. If no dependencies, assign first-come-first-served
3. Update this registry immediately to prevent conflicts

### If Module Dependencies Change

1. May need to renumber migrations
2. Use `migrate:fresh` in development (destructive evolution)
3. Update registry with new MM_DD assignment

---

## Related Documentation

- `docs/architecture/database-conventions.md` - Complete database naming conventions
- `docs/development/creating-module-migrations.md` - Guide for creating migrations
- `docs/architecture/file-structure.md` - Module structure reference
- `app/Base/Database/AGENTS.md` - Database migration guidelines
---