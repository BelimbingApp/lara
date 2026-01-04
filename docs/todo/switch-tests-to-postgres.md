# Switch Testing Database to PostgreSQL

**Status:** Pending
**Priority:** High (Architectural Integrity)

## Context
Currently, the project uses SQLite (`:memory:`) for automated tests (CI and local), but the production environment is strictly PostgreSQL.

## Problem
This creates a "feature parity" mismatch.
- **False Positives:** Tests might pass on SQLite but fail in production due to stricter types or different behavior in Postgres.
- **False Negatives:** We cannot use powerful Postgres-specific features (JSONB, Full-Text Search, Array types) because SQLite doesn't support them.
- **Principle Violation:** This contradicts the "Quality-Obsessed" and "Zero-tolerance for technical debt" principles defined in the Project Brief.

## Task
Update the testing infrastructure to use PostgreSQL instead of SQLite.

### Steps
1.  **Update GitHub Actions (`.github/workflows/tests.yml`)**:
    - Add a PostgreSQL service container.
    - Configure health checks to ensure DB is ready before tests run.
    - Update `DB_CONNECTION` env var to `pgsql`.

2.  **Update Local Testing Configuration (`phpunit.xml`)**:
    - Change `<env name="DB_CONNECTION" value="sqlite"/>` to `pgsql`.
    - Ensure developers have a local testing database available (e.g., via Docker Compose).

3.  **Verify**:
    - Run full test suite to ensure no regressions.
    - Confirm that migrations run correctly on the Postgres test instance.

## References
- [Laravel Docs: Testing with Databases](https://laravel.com/docs/testing#databases)
- [GitHub Actions: Service Containers](https://docs.github.com/en/actions/using-containerized-services/creating-postgresql-service-containers)
