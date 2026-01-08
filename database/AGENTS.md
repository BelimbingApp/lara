# Database Migration Guidelines

- Table names should be prefixed with the name of the module or vendor or company followed by the name of the table.
- Use `id()` method for primary keys, not `uuid()`.
- For changes to tables, use batch rollback by batch number, do not use `migrate:fresh`.

## Database Naming Conventions

As a framework designed for extensibility and adoption by various businesses, Belimbing adheres to strict database naming conventions. These conventions are crucial for preventing conflicts between the core framework, installed modules, and the custom business logic implemented by the end users.

### Module Table Prefixes

Tables related to a module should be prefixed with the name of the module. Examples:
- `companies`
- `company_relationship_types`
- `company_relationships`
- `company_external_accesses`

Tables related to extensions should be prefixed with the name of the vendor or company followed by the name of the module. Examples:
- `sbg_companies`
- `sbg_company_relationships`
- `sbg_company_external_accesses`

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
