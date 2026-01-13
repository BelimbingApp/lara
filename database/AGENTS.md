# Database Migration Guidelines

- Table names should **always** be prefixed with the name of the module or vendor or company followed by the name of the table.
- Use `id()` method for primary keys, not `uuid()`.
- For changes to tables, use batch rollback by batch number, do not use `migrate:fresh`.

## Database Naming Conventions

As a framework designed for extensibility and adoption by various businesses, Belimbing adheres to strict database naming conventions. These conventions are crucial for preventing conflicts between the core framework, installed modules, and the custom business logic implemented by the end users.

### Table Naming by Layer

**Base Layer Tables (`app/Base/`):**
- Prefix: `base_*`
- Purpose: Framework infrastructure (not business logic)
- Examples:
  - `base_extensions` - Extension registry
  - `base_extension_hooks` - Hook registrations
  - `base_config_scopes` - Scope-based configuration
  - `base_permissions` - Permission system
  - `base_audit_logs` - Audit trail
  - `base_workflow_definitions` - Workflow engine definitions

**Core Module Tables (`app/Modules/Core/`):**
- Prefix: Module name only (no `core_` prefix)
- Purpose: Core business domain modules
- Examples:
  - `companies` - Company module
  - `company_relationships` - Company module
  - `users` - User module
  - `geonames_countries` - Geonames module

**Business Module Tables (`app/Modules/Business/`):**
- Prefix: Module name
- Examples:
  - `erp_orders` - ERP module
  - `crm_leads` - CRM module
  - `hr_employees` - HR module

**Vendor Extension Tables (`extensions/vendor/`):**
- Prefix: `vendor_module_*`
- Examples:
  - `sbg_companies` - SBG vendor extension
  - `sbg_company_relationships` - SBG vendor extension
  - `acme_crm_leads` - ACME vendor extension

### Rationale

The `base_*` prefix distinguishes framework infrastructure from business domain tables:
- `base_*` tables are framework-owned meta-systems (never user-created)
- Module tables are business domains (even if core to framework)
- Vendor tables are clearly marked by vendor name

## Database ID Standards

- **Primary Keys**: Use `id()` method which creates `UNSIGNED BIGINT` (auto-incrementing primary key)
- **Foreign Keys**: Use `foreignId()` for foreign key columns, which also creates `UNSIGNED BIGINT`
- **Rationale**: This is Laravel's standard convention and ensures type consistency between primary keys and foreign keys

### Example Migration

```php
Schema::create('companies', function (Blueprint $table) {
    $table->id();  // Creates UNSIGNED BIGINT auto-incrementing primary key
    $table->foreignId('parent_id')->nullable()->constrained('companies');
    $table->string('name');
    $table->timestamps();
});
```

**Note**: Laravel's `id()` method is an alias for `bigIncrements()` and creates an auto-incrementing UNSIGNED BIGINT primary key. The `foreignId()` method also creates UNSIGNED BIGINT columns, ensuring type compatibility. Do NOT use `uuid()` for primary keys unless explicitly required.

## Migrations Without Data Loss

During development, you often need to test migration changes without wiping out all your data (especially user accounts). Use rollback by batch ID to selectively rollback migrations.

### Understanding Migration Batches

Laravel tracks migrations in **batches**. All migrations run together in a single `php artisan migrate` command get assigned the same batch number.

**Visual Example:**

```
Initial setup:
$ php artisan migrate:fresh
→ Creates batch 1 with users, cache, jobs tables

Add Company module:
$ php artisan migrate
→ Creates batch 2 with company tables

Add Inventory module:
$ php artisan migrate
→ Creates batch 3 with inventory tables
```

**Result:**
```
Batch 1: users, cache, jobs                    ← Foundation (keep this!)
Batch 2: companies, company_relationships       ← Your current work
Batch 3: inventory_items, inventory_locations   ← Future work
```

**Rolling back by batch:**
```bash
php artisan migrate:rollback --batch=3    # Removes batch 3 only
php artisan migrate:rollback --batch=2     # Removes batch 2 only
                                          # Batch 1 remains untouched!
```

### Rollback by Batch ID

**Step 1:** Check current batch numbers:

```bash
php artisan migrate:status
```

Output example:
```
Migration name                                           Batch  Status
0001_01_01_000000_create_users_table ................... [1]    Ran
0001_01_01_000001_create_cache_table ................... [1]    Ran
0001_01_01_000010_create_companies_table ............... [2]    Ran
0001_01_01_000011_create_company_relationship_types .... [2]    Ran
0001_01_01_000012_create_company_relationships ......... [2]    Ran
```

**Step 2:** Rollback a specific batch:

```bash
# Rollback a specific batch number
php artisan migrate:rollback --batch=2
```

**Step 3:** Make your changes to migration files, then re-run:

```bash
php artisan migrate

# Optionally seed
php artisan db:seed --class=App\\Modules\\Core\\Company\\Seeders\\RelationshipTypeSeeder
```

### Best Practices

1. ⭐ **Always use `--batch` flag** - Rollback by specific batch number for precise control
2. **Check `migrate:status` first** - Always verify which batch contains your migrations
3. **Run migrations together** - Group related migrations in one batch for easy rollback
4. **Use seeders per module** - Allows re-seeding specific data without affecting others
5. **Never use `migrate:fresh`** - Once you have development data, use batch rollback instead
6. **Keep batches focused** - Don't mix unrelated migrations in the same `migrate` command
