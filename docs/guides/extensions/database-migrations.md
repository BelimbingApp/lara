# Extension Database Migrations

This guide explains how extensions can create and manage database tables in the Belimbing framework.

## Overview

Extensions can create their own database tables by placing migration files in their `migrations/` directory and loading them through a Service Provider. This allows extensions to have their own database schema while maintaining proper namespace isolation.

## Extension Structure

Extensions should follow this directory structure:

```
extensions/
├── vendor/                    # Third-party extensions
│   └── {vendor-name}/
│       └── {extension-name}/
│           ├── composer.json
│           ├── manifest.json
│           ├── src/
│           │   └── Providers/
│           │       └── ExtensionServiceProvider.php
│           ├── migrations/     # Extension migrations
│           │   └── 2026_01_01_000000_create_example_table.php
│           ├── seeders/
│           ├── routes/
│           └── views/
│
└── custom/                    # Custom business extensions
    └── {extension-name}/
        └── [same structure]
```

## Table Naming Conventions

**Critical**: Extension tables must be prefixed with the vendor/company name followed by the module/table name to prevent conflicts.

### Format
```
{vendor}_{module}_{table_name}
```

### Examples
- `sbg_companies` - SBG vendor, company module
- `sbg_company_relationships` - SBG vendor, company module, relationships table
- `acme_orders` - ACME vendor, orders table
- `acme_order_items` - ACME vendor, orders module, items table

### Why This Matters

1. **Namespace Isolation**: Prevents conflicts between extensions and core modules
2. **Visual Distinction**: Developers can instantly identify extension tables
3. **Selective Management**: Easier to backup, migrate, or remove extension data

## Creating Migration Files

### Step 1: Create Migration File

Create a migration file in your extension's `migrations/` directory:

```php
<?php

// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2026 Your Name

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sbg_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sbg_companies');
    }
};
```

### Step 2: Follow Database Standards

Refer to `app/Base/Database/AGENTS.md` for migration standards:

- **Primary Keys**: Use `id()` method (creates `UNSIGNED BIGINT`)
- **Foreign Keys**: Use `foreignId()` method (creates `UNSIGNED BIGINT`)
- **Timestamps**: Include `$table->timestamps()` for created_at/updated_at
- **Soft Deletes**: Consider `$table->softDeletes()` if logical deletion is needed

### Step 3: Reference Core Tables

If your extension needs to reference core framework tables, use proper foreign key constraints:

```php
Schema::create('sbg_company_extensions', function (Blueprint $table) {
    $table->id();

    // Reference core companies table
    $table->foreignId('company_id')
          ->constrained('companies')
          ->cascadeOnDelete();

    // Reference core users table
    $table->foreignId('user_id')
          ->nullable()
          ->constrained('users')
          ->nullOnDelete();

    $table->string('extension_data');
    $table->timestamps();
});
```

## Loading Migrations via Service Provider

### Step 1: Create Service Provider

Create a Service Provider in your extension:

```php
<?php

// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2026 Your Name

namespace Extensions\Vendor\CompanyExtension\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;

class CompanyExtensionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register any bindings here
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations from extension directory
        $this->loadMigrationsFrom(
            __DIR__ . '/../../migrations'
        );
    }
}
```

### Step 2: Register Service Provider

Add your Service Provider to `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\VoltServiceProvider::class,
    \Extensions\Vendor\CompanyExtension\Providers\CompanyExtensionServiceProvider::class,
];
```

Or use Composer auto-discovery in your extension's `composer.json`:

```json
{
    "extra": {
        "laravel": {
            "providers": [
                "Extensions\\Vendor\\CompanyExtension\\Providers\\CompanyExtensionServiceProvider"
            ]
        }
    }
}
```

## Running Migrations

Once your Service Provider is registered, Laravel will automatically discover your extension migrations when you run:

```bash
php artisan migrate
```

This will run all migrations, including those from extensions.

### Running Extension Migrations Only

To run only your extension's migrations (useful for testing):

```bash
php artisan migrate --path=extensions/vendor/company-extension/migrations
```

### Rolling Back Extension Migrations

To rollback extension migrations:

```bash
php artisan migrate:rollback --path=extensions/vendor/company-extension/migrations
```

## Migration Best Practices

### 1. Use Descriptive Names

Migration filenames should clearly describe what they do:

```
✅ Good:
2026_01_15_120000_create_sbg_companies_table.php
2026_01_20_090000_add_logo_url_to_sbg_companies_table.php
2026_02_01_100000_create_sbg_company_relationships_table.php

❌ Bad:
2026_01_01_000000_migration.php
2026_01_01_000001_update.php
```

### 2. One Table Per Migration (Recommended)

Keep migrations focused and granular:

```php
// ✅ Good: One migration for one table
Schema::create('sbg_companies', function (Blueprint $table) {
    // ...
});

// ❌ Avoid: Multiple unrelated tables in one migration
Schema::create('sbg_companies', function (Blueprint $table) {
    // ...
});
Schema::create('sbg_orders', function (Blueprint $table) {
    // ...
});
```

**Exception**: If tables are truly inseparable and always created/dropped together, combining them is acceptable.

### 3. Always Implement `down()` Method

Ensure your migrations can be rolled back:

```php
public function down(): void
{
    Schema::dropIfExists('sbg_companies');
}
```

### 4. Use Transactions When Possible

For data migrations (not schema changes), wrap in transactions:

```php
use Illuminate\Support\Facades\DB;

public function up(): void
{
    DB::transaction(function () {
        // Data migration logic
    });
}
```

### 5. Handle Foreign Key Dependencies

Order your migrations to respect foreign key dependencies:

```php
// Migration 1: Create base table
Schema::create('sbg_companies', function (Blueprint $table) {
    $table->id();
    $table->string('name');
});

// Migration 2: Create dependent table (runs after Migration 1)
Schema::create('sbg_company_relationships', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained('sbg_companies');
});
```

## Example: Complete Extension Migration

Here's a complete example of an extension with migrations:

### Directory Structure

```
extensions/vendor/sbg/
└── company-extension/
    ├── composer.json
    ├── manifest.json
    ├── src/
    │   └── Providers/
    │       └── CompanyExtensionServiceProvider.php
    └── migrations/
        ├── 2026_01_01_000000_create_sbg_companies_table.php
        └── 2026_01_02_000000_create_sbg_company_relationships_table.php
```

### Service Provider

```php
<?php

// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2026 SBG

namespace Extensions\Vendor\Sbg\CompanyExtension\Providers;

use Illuminate\Support\ServiceProvider;

class CompanyExtensionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
    }
}
```

### Migration File

```php
<?php

// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2026 SBG

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sbg_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sbg_companies');
    }
};
```

## Troubleshooting

### Migrations Not Found

**Problem**: `php artisan migrate` doesn't find extension migrations.

**Solutions**:
1. Verify Service Provider is registered in `bootstrap/providers.php`
2. Check that `loadMigrationsFrom()` path is correct
3. Clear config cache: `php artisan config:clear`
4. Verify migration file follows Laravel naming convention

### Table Name Conflicts

**Problem**: Migration fails with "Table already exists" error.

**Solutions**:
1. Ensure table name uses vendor prefix: `{vendor}_{table_name}`
2. Check for duplicate migration files
3. Verify migration hasn't already run: `php artisan migrate:status`

### Foreign Key Errors

**Problem**: Foreign key constraint fails.

**Solutions**:
1. Ensure referenced table exists (check migration order)
2. Verify foreign key column type matches referenced primary key
3. Check that referenced table uses `id()` method (UNSIGNED BIGINT)

## Related Documentation

- [Database Migration Guidelines](../../../app/Base/Database/AGENTS.md) - Core migration standards
- [Extension Configuration Overrides](./config-overrides.md) - Config management
- [Extension Structure](../../architecture/file-structure.md) - Overall extension architecture
