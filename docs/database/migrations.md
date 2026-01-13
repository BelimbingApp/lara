# Database Migrations

This guide explains how to safely work with database migrations during development using Laravel's built-in batch system.

## 🚀 Quick Reference

```bash
# Check current status (Always do this first!)
php artisan migrate:status

# Undo the last "batch" of migrations (Safest way to iterate)
php artisan migrate:rollback

# Undo specific batch
php artisan migrate:rollback --batch=2

# Run pending migrations
php artisan migrate
```

## 🧠 Core Concept: Batches

Laravel groups migrations that are run together into **batches**. This allows you to surgically rollback recent work while preserving the rest of your database (users, other modules, etc.).

**Example:**
1.  `migrate:fresh` runs foundation tables → **Batch 1**
2.  Later, you create a new feature and run `migrate` → **Batch 2**

If you run `migrate:rollback` now, it **only** removes Batch 2. Batch 1 remains untouched.

**Why this matters:**
*   ✅ **Speed:** Faster than rebuilding the whole DB.
*   ✅ **Safety:** Preserves test data in other tables.
*   ✅ **Simplicity:** No need to count steps or configure modules.

## 🛠 Common Workflows

### 1. Modifying a Table (Rename, Add Column, etc.)

**Scenario:** You just created a table in your latest batch, but realized you need to change a column name.

1.  **Check Status:** Ensure the migration you want to change is in the *latest* batch.
    ```bash
    php artisan migrate:status
    ```
2.  **Rollback:** Undo the last batch.
    ```bash
    php artisan migrate:rollback
    ```
3.  **Edit:** Modify your migration file (and Model if necessary).
4.  **Re-run:** Apply the changes.
    ```bash
    php artisan migrate
    ```

### 2. Deep Refactoring (Older Batches)

**Scenario:** You need to change a migration that ran weeks ago (Batch 1).

*   **Development:** You likely need to `migrate:refresh` or manually modify the database if you can't lose data.
*   **Production:** **NEVER** edit old migrations. Create a **new** migration to modify the schema.
    ```bash
    php artisan make:migration add_tax_id_to_companies
    ```

## 🎓 Best Practices

| ✅ Do | ❌ Don't |
| :--- | :--- |
| **Check status first** (`migrate:status`) | Run `migrate:rollback` blindly |
| Use **Batch Rollback** for iterating | Use `migrate:fresh` for small changes (wipes DB) |
| Create **New Migrations** for old tables | Edit old migration files (breaks consistency) |
| Test with a separate DB (`.env.testing`) | Run tests on your local dev data |

## ❌ Troubleshooting

### "Table already exists"
The rollback failed or didn't run, but you tried to migrate again.
*   **Fix:** Manually drop the table in `tinker` or your SQL client, then run `migrate`.
    ```bash
    php artisan tinker
    >>> Schema::drop('table_name');
    ```

### "Foreign key constraint violation"
You are trying to drop a table that is referenced by another table.
*   **Fix:** Rollback the *dependent* table first (or drop it manually if strictly local dev). Ensure your `down()` methods drop tables in the reverse order of creation.

---

**Note:** We previously explored a custom `migrate:module` command. We have decided to use standard Laravel Batch Rollback instead (YAGNI).
