# Database Module (app/Base/Database)

This module provides **module-aware migration infrastructure** extending Laravel's migration commands. It enables selective migration/rollback by module and automatic seeder discovery.

**See `docs/architecture/database.md` for migration and seeding architecture; `docs/development/database-conventions.md` for detailed naming conventions.**

## Key Components

### Commands (Override Laravel)

All commands support `--module=<name>` for selective operation:

| Command | Description |
|---------|-------------|
| `MigrateCommand` | Runs migrations with module filtering and seeder registry support |
| `RollbackCommand` | Rolls back migrations, optionally filtered by module |
| `ResetCommand` | Resets all migrations, optionally filtered by module |
| `RefreshCommand` | Refreshes migrations with module-aware seeding |
| `StatusCommand` | Shows migration status, optionally filtered by module |

```bash
# Default: all modules (module-first architecture)
php artisan migrate
# Or the following, which is equivalent:
php artisan migrate --module=*

# Module-specific operations (case-sensitive)
php artisan migrate --module=Geonames
php artisan migrate --module=Geonames,Company
php artisan migrate:rollback --module=Geonames
```

### SeederRegistry Model

Tracks seeder execution state. Seeders are registered by migrations using `RegistersSeeders` trait and executed automatically during `migrate --seed`.

**Statuses:** `pending` → `running` → `completed` | `failed` | `skipped`

### RegistersSeeders Trait

Used by migrations to register seeders for automatic execution:

```php
use App\Base\Database\RegistersSeeders;

return new class extends Migration
{
    use RegistersSeeders;

    public function up(): void
    {
        Schema::create('geonames_countries', ...);

        // Register seeder for automatic execution
        $this->registerSeeder(CountriesSeeder::class);
    }
};
```

**App-level seeders** (non-module): Same pattern as modules — the migration that creates the tables registers the seeder in `up()` and unregisters in `down()` (use `RegistersSeeders`). Migration in `database/migrations/`, seeder class in `database/seeders/`. They get `module_name`/`module_path` = null and run with `migrate --seed` in migration order. Do not add seeders to `DatabaseSeeder::run()`.

### Concerns

- **InteractsWithModuleOption**: Parses `--module` option (comma-delimited, case-sensitive)
- **InteractsWithModuleMigrations**: Loads migrations from module directories

## Module Auto-Discovery Paths

Migrations are discovered from:
- `app/Base/*/Database/Migrations/`
- `app/Modules/*/*/Database/Migrations/`

## Implementation Notes

### ServiceProvider Pattern

Commands are registered via Laravel's `extend()` method in a deferred service provider. This overrides Laravel's migration commands while preserving the container's deferred loading.

```php
// ServiceProvider.php
$this->app->extend('command.migrate', fn ($command, $app) =>
    new MigrateCommand($app['migrator'], $app['events'])
);
```

### Seeding Behavior

Seeder registration is done in migrations via `registerSeeder()`. Seeders under `app/Modules/*/*/Database/Seeders/` are also discovered when you pass `--seed`: any not yet in the registry are added then, so they run even if the migration did not call `registerSeeder()`. Plain `migrate` (no `--seed`) never runs seeders. If a migration does not register its seeder, pass `--seed` (e.g. below).

```bash
# Run all pending seeders (after migrations)
php artisan migrate --seed

# Seed only one module (case-sensitive)
php artisan migrate --seed --module=Company

# Run a single seeder (short form: Module/SeederClass or Module/Sub/SeederClass)
php artisan migrate --seed --seeder=Company/RelationshipTypeSeeder
# Or FQCN with single quotes so backslashes are preserved
php artisan migrate --seed --seeder='App\Modules\Core\Company\Database\Seeders\RelationshipTypeSeeder'
```

### Development vs. Production Seeders

| Category | Location | Naming | Registered in migration? |
|----------|----------|--------|--------------------------|
| **Production** | `Database/Seeders/` | `{Entity}Seeder` | Yes (`registerSeeder()`) |
| **Development** | `Database/Seeders/Dev/` | `Dev{Description}Seeder` | No (run explicitly) |

- **Production seeders** populate reference/config data needed in all environments.
- **Development seeders** create fake data for UI work and manual testing. They live in `Dev/` subdirectory with a `Dev` class prefix.

```bash
# Run a dev seeder explicitly (note the Dev/ subdirectory in the path)
php artisan migrate --seed --seeder=Company/Dev/DevCompanyAddressSeeder
```

## Database ID Standards

- **Primary Keys**: Use `id()` (UNSIGNED BIGINT auto-increment)
- **Foreign Keys**: Use `foreignId()` (UNSIGNED BIGINT)
- **No UUIDs** for primary keys unless explicitly required

## Development Workflow

### Rollback by Batch (Preserve Data)

Never use `migrate:fresh` once you have development data. Use batch rollback:

```bash
# Check batch numbers
php artisan migrate:status

# Rollback specific batch
php artisan migrate:rollback --batch=2

# Edit migrations, then re-run
php artisan migrate --seed
```

### Module-Specific Testing

```bash
# Rollback and re-run single module
php artisan migrate:rollback --module=Geonames
php artisan migrate --module=Geonames --seed
```

### Refactoring Dependencies

**Key Principles:**
1. Base modules first
2. Core modules next
3. Business modules next
4. Extension modules last
5. Migration dates enforce load order
6. Foreign key constraints respect dependency order
7. No circular dependencies allowed

If you need to break a circular dependency:

1. **Use nullable foreign keys** with deferred constraints
2. **Split into two migrations** (create table, then add constraint)
3. **Use pivot tables** for many-to-many relationships
4. **Redesign the relationship** if truly circular