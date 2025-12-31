# Database Naming Conventions

## Overview

As a framework designed for extensibility and adoption by various businesses, Belimbing adheres to strict database naming conventions. These conventions are crucial for preventing conflicts between the core framework, installed modules, and the custom business logic implemented by the end users.

## The `blb_` Prefix

All database tables that belong to the **Belimbing Core Framework** or its **Official Modules** must be prefixed with `blb_`.

### Rationale

1.  **Conflict Prevention**: Generic table names (e.g., `settings`, `logs`, `configurations`, `status`) are highly likely to be used by the implementation layer (the actual business application) or third-party Laravel packages. The `blb_` prefix creates a safe namespace for the framework.
2.  **Visual Distinction**: Developers can instantly distinguish between "Infrastructure/Framework" tables and "Business Domain" tables when browsing the database.
3.  **Maintenance & Operations**:
    *   **Selective Backups**: it simplifies operations like backing up only business data (excluding framework config) or vice versa.
    *   **Resets**: Easier to "reset the framework" to fresh state without wiping business transactional data.

### Examples

| Table Purpose | Bad Name (Avoid) | Good Name (Recommended) |
| :--- | :--- | :--- |
| Status Configurations | `status_configs` | `blb_status_configs` |
| System Settings | `settings` | `blb_settings` |
| Audit Trail | `audit_logs` | `blb_audit_logs` |
| Module Tracker | `modules` | `blb_modules` |

## Exceptions: Standard Laravel Tables

Standard Laravel tables should remain **unprefixed** and adhere to Laravel's defaults.

**Do NOT prefix:**
*   `users`
*   `password_reset_tokens`
*   `sessions`
*   `jobs`
*   `failed_jobs`
*   `cache`
*   `cache_locks`

### Rationale for Exceptions

Most of the Laravel ecosystem (including first-party packages like Sanctum, Horizon, Telescope, and Pulse) expects these standard table names. While they can often be configured, doing so increases friction, complicates configuration management, and can break compatibility with third-party tools that assume standard Laravel conventions.

## Implementation Guide

### Migration Files

When creating migrations for framework components, define the table name explicitly with the prefix.

```php
Schema::create('blb_status_configs', function (Blueprint $table) {
    // ...
});
```

### Eloquent Models

Explicitly define the table property in your Eloquent models to match the prefixed table name.

```php
class StatusConfig extends Model
{
    protected $table = 'blb_status_configs';
}
```
