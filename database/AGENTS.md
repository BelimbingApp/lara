# Database Migration Guidelines

- Table names should be prefixed with the name of the module or vendor or company followed by the name of the table.
- Use `id()` method for primary keys, not `uuid()`.

## Database Naming Conventions

As a framework designed for extensibility and adoption by various businesses, Belimbing adheres to strict database naming conventions. These conventions are crucial for preventing conflicts between the core framework, installed modules, and the custom business logic implemented by the end users.

### Module Table Prefixes

Tables related to a module should be prefixed with the name of the module. Examples:
- `companies`
- `company_relation_types`
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
