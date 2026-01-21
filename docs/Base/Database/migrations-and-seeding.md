# Migrations & Seeding Behavior

**Document Type:** How-To / Reference
**Purpose:** Explain how BLB runs module migrations and module seeders
**Last Updated:** 2026-01-21

## Overview

Belimbing (BLB) extends Laravel’s migration commands to be **module-aware** and to support **registry-based seeding**:

- Migrations are **auto-discovered** from module directories.
- `php artisan migrate --seed` runs seeders from a **seeder registry table** (`base_database_seeders`) instead of relying on a single `DatabaseSeeder` to orchestrate everything.

## Migration discovery and ordering

### Where migrations are discovered

When you run migration commands (e.g. `migrate`, `migrate:rollback`, `migrate:status`), BLB loads migrations from:

- `app/Base/*/Database/Migrations/`
- `app/Modules/*/*/Database/Migrations/`

### How migrations are ordered

Laravel orders migrations by filename. BLB’s timestamp conventions (year/layer, module prefix, time) enforce a stable order:

- **Base** (`0001_*`) runs before **Core** (`0002_*`)
- Within a layer, module prefixes help keep dependent modules ordered.

See `docs/architecture/database-conventions.md` and `docs/Base/Database/migration-registry.md`.

## `--module` option (module-specific migrations)

BLB’s custom `migrate` command adds `--module` to selectively load migrations:

```bash
# Single module (case-sensitive)
php artisan migrate --module=Geonames

# Multiple modules (comma-delimited)
php artisan migrate --module=Geonames,Company

# Wildcard for all modules
php artisan migrate --module=*
```

Notes:

- Module names are **case-sensitive** (they must match the directory name).
- When `--module` is provided, only those module migration paths are loaded.

## Seeder registry (how module seeding works)

### Key idea

Migrations register seeders into a registry table (`base_database_seeders`). Then `migrate --seed` runs seeders from that registry in migration order.

This gives BLB a **deep-module** interface:

- A migration owns its schema change **and** can register its corresponding seeder.
- The framework handles execution ordering and retry behavior.

### How seeders are registered

Migrations that need data should register their seeder in `up()` and unregister it in `down()`:

```php
// inside a migration
$this->registerSeeder(\App\Modules\Core\Geonames\Database\Seeders\CountrySeeder::class);

// inside down()
$this->unregisterSeeder(\App\Modules\Core\Geonames\Database\Seeders\CountrySeeder::class);
```

Registration stores:

- `seeder_class` (FQCN)
- `module_name` (e.g. `Geonames`)
- `module_path`
- `migration_file` (used for ordering)
- `status`, `ran_at`, `error_message`

### Seeder execution statuses

Seeders move through these states:

- `pending` → `running` → `completed`
- `failed` (retriable)

Important behavior:

- **Runnable seeders are only `pending` or `failed`.**
- **Completed seeders are not run again** unless their status is reset to `pending` (see below).

## What happens on `php artisan migrate --module=Geonames --seed`

### If there are pending Geonames migrations

- Migrations run first.
- During migration `up()`, relevant seeders get (re-)registered in the registry (status becomes `pending`).
- After migrations finish, `--seed` runs runnable seeders for `Geonames` in `migration_file` order.

### If Geonames is “already migrated and seeded”

- No migrations run.
- Registry entries are already `completed`.
- Result: **no seeders run** (because only `pending` / `failed` are runnable).

## Rerunning / forcing seeders

### Retry failed seeders

If a seeder is `failed`, the next `migrate --seed` will retry it automatically (it’s runnable).

### Rerun seeders by rolling back migrations

Rolling back the module unregisters seeders (if `down()` does it), then re-running migrations registers them again as `pending`:

```bash
php artisan migrate:rollback --module=Geonames
php artisan migrate --module=Geonames --seed
```

### Force a specific seeder (bypass registry)

If you provide `--seeder`, BLB bypasses the registry and calls `db:seed` directly:

```bash
php artisan migrate --module=Geonames --seed --seeder="App\\Modules\\Core\\Geonames\\Database\\Seeders\\Admin1Seeder"
```

Use this when you intentionally want to rerun a seeder even if it’s already `completed`.

## Related docs

- `docs/Base/Database/migration-registry.md`
- `docs/architecture/database-conventions.md`
- `docs/tutorials/laravel-command-lifecycle.md`
