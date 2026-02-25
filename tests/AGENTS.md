# Tests Agent Guide

## Test Baseline Seeding

- The test suite uses `Tests\TestingBaselineSeeder`.
- The source of truth for which modules are seeded in tests is:
  - `tests/Support/testing-seed-modules.php`
- Add a module name (for example, `'Authz'`, `'Company'`) to include its production seeders in test baseline.
- Remove a module name to exclude its production seeders from test baseline (for example, heavy or network-bound seeders).

## Environment Notes

- Automated tests run with in-memory SQLite when configured via `phpunit.xml`:
  - `DB_CONNECTION=sqlite`
  - `DB_DATABASE=:memory:`
- In that setup, test DB refreshes do not modify the local development database.
