# Geonames Module

**Document Type:** Module Documentation
**Purpose:** Document the Geonames module — geographic data management (countries, admin divisions, postcodes).
**Last Updated:** 2026-02-12

## Overview

The Geonames module provides geographic reference data imported from [geonames.org](https://www.geonames.org/). It manages countries, admin1 divisions (states/provinces), and postcodes. Data is imported via the admin UI or CLI seeders.

---

## 1. Module Structure

```text
app/Modules/Core/Geonames/
├── Database/
│   ├── Migrations/
│   └── Seeders/
│       ├── CountrySeeder.php
│       ├── Admin1Seeder.php
│       └── PostcodeSeeder.php
├── Events/
│   └── PostcodeImportProgress.php
├── Jobs/
│   └── ImportPostcodes.php
└── Models/
    ├── Country.php
    ├── Admin1.php
    └── Postcode.php
```

---

## 2. Data Sources

All data is sourced from [geonames.org](https://www.geonames.org/):

| Dataset | Source File | Scale |
| :--- | :--- | :--- |
| **Countries** | `countryInfo.txt` | Single file, ~250 records |
| **Admin1** | `admin1CodesASCII.txt` | Single file, ~3,800 records |
| **Postcodes** | Per-country `{ISO}.zip` (e.g., `US.zip`) | Can be large |

---

## 3. Admin UI

Three pages under `/admin/geonames/`:

-   **Countries:** View/edit country names. Update button fetches latest from geonames.
-   **Admin1 Divisions:** View/edit division names. Update button fetches latest.
-   **Postcodes:** Import (select countries via multi-select), Update (re-import existing). Real-time progress via WebSocket.

---

## 4. Seeders

-   All seeders cache downloaded files for 7 days in `storage/download/geonames/`.
-   **CountrySeeder / Admin1Seeder:** Upsert strategy — preserves user-edited `name` fields.
-   **PostcodeSeeder:** Delete + re-insert per country (transactional). Broadcasts progress events.
-   Can be run via CLI:
    ```bash
    php artisan db:seed --class=CountrySeeder
    ```

---

## 5. Postcode Import Flow

1.  User selects countries → Livewire dispatches `ImportPostcodes` queued job.
2.  Job runs `PostcodeSeeder` which downloads, parses, and imports per country.
3.  Each step broadcasts `PostcodeImportProgress` event via Reverb.
4.  Frontend listens via Echo/Alpine.js, shows progress bar.
5.  On completion, UI auto-refreshes the table.

---

## 6. Key Design Decisions

-   **Name preservation:** User-edited names (country, admin1) are preserved during updates via upsert column exclusion.
-   **Postcode strategy:** Delete + insert (no user-editable fields) for simplicity.
-   **Import vs Update:** Import excludes already-imported countries; Update re-imports all existing.
-   **Update visibility:** Update button is hidden when no data exists.
