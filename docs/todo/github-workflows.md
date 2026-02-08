# GitHub Workflows for Validating Migration File Names

## Purpose
This document outlines the requirements for validating migration file names in the Belimbing framework before merging changes to the main branch. Ensuring that migration file names adhere to the established naming conventions is crucial for maintaining the integrity and order of database migrations.

## Validation Requirements
1. **File Naming Convention**: Migration files must follow the two-level hierarchy format:
   - **Format**: `YYYY_MM_DD_HHMMSS_description.php`
   - **Level 1**: `YYYY` indicates the architectural layer (e.g., `0001` for Base, `0002` for Core).
   - **Level 2**: `MM_DD` identifies the specific module within the architectural layer.
   - **Level 3**: `HHMMSS` is used for ordering migrations within each module.

2. **Module Identification**: Ensure that the `MM_DD` part of the filename corresponds to the correct module as defined in the architecture documentation.

3. **Automated Checks**: Implement automated checks in the GitHub workflow to validate the naming convention against the defined rules before allowing a pull request to be merged into the main branch.

4. **Documentation Reference**: Link to the main documentation for migration file naming conventions to provide context for the validation rules.

### Migration Layers

```
0100  Base       Framework infrastructure (cache, jobs) except the seeders table
0200  Core       Core business modules (Geonames, Company, User)
0300+ Business   Business process modules (ERP, CRM, HR)
2026+ Extensions Third-party vendor extensions
```

### Module Structure

Each module contains:
```
app/Modules/{Layer}/{ModuleName}/
├── Database/
│   ├── Migrations/   # YYYY_MM_DD_HHMMSS_*.php
│   ├── Seeders/      # Module-specific seeders
│   └── Factories/    # Model factories
└── Models/           # Eloquent models
```

### Naming Convention

**Migration files:** `YYYY_MM_DD_HHMMSS_description.php`
- `YYYY` = Layer (0100, 0200, 0300+)
- `MM_DD` = Module identifier within layer
- `HHMMSS` = Ordering within module

**Table names:**
- Base: `base_*`
- Core: `{module}_*` (no `core_` prefix)
- Business: `{module}_*`
- Extensions: `{vendor}_*`

### Registered Modules (as of 2026-01-14)

| Layer | Module | Prefix | Migrations | Status |
|-------|--------|--------|------------|--------|
| Base | Infrastructure | `0100_01_01_*` | 2 | ✅ Active |
| Modules/Core | Geonames | `0200_01_03_*` | 2 | ✅ Active |
| Modules/Core | Company | `0200_01_10_*` | 5 | ✅ Active |
| Modules/Core | User | `0200_01_20_*` | 1 | ✅ Active |
