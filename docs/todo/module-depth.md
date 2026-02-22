# Module Depth — Next Steps

**Status:** Planning
**Context:** Architecture migration is complete. Modules have routes, models, and views but thin domain logic. This document tracks opportunities to deepen each module.

## Current Module Inventory

| Module | Models | Tests | Routes | Domain Logic |
|--------|--------|-------|--------|--------------|
| Company | 7 (Company, Department, DepartmentType, LegalEntityType, RelationshipType, CompanyRelationship, ExternalAccess) | 38 | 7 | Status transitions, hierarchy, relationships |
| User | 1 (User) | 10 + 17 auth + 7 settings | 12 | Auth (via Laravel), settings |
| Geonames | 3 (Country, Admin1, Postcode) | 0 | 2 | Reference data only |
| Employee | 1 (Employee) | 0 | 3 | Thin |
| Address | 2 (Address, Addressable) | 3 | 3 | Polymorphic addressable |
| Workflow | 0 (migrations only) | 0 | 0 | Skeleton |

## Priorities

### 1. Test Coverage Gaps
Modules with zero tests are blind spots:
- [ ] **Geonames** — No tests. Reference data module; test seeders, model scopes, and search.
- [ ] **Employee** — No tests. Test CRUD, company relationship, validation.
- [ ] **Workflow** — No tests, no models. Decide: build out or remove if YAGNI.

### 2. Employee Module
Thin model with routes but no domain logic:
- [ ] Define employment lifecycle (hire, transfer, terminate)
- [ ] Link to Company (belongs to) and User (optional, for system access)
- [ ] Add tests

### 3. Workflow Module
Currently just migrations — no models, no routes, no logic:
- [ ] Evaluate: is a generic workflow engine needed now, or is the Company status machine sufficient?
- [ ] If YAGNI, remove the module and its migrations
- [ ] If needed, define the contract: what entities use workflows, what transitions look like

### 4. Cross-Module APIs
Modules reference each other but lack formal APIs:
- [ ] Company ↔ Address: polymorphic relationship exists but no encapsulated API
- [ ] Company ↔ Employee: employee belongs to company but no hiring/transfer logic
- [ ] User ↔ Employee: no link yet (should a User optionally map to an Employee?)

### 5. Domain Logic Depth
- [ ] **Company**: Relationship lifecycle (approve, suspend, terminate relationships). Validation rules for hierarchy depth. Department assignment logic.
- [ ] **Address**: Geocoding integration. Address validation against Geonames data.
- [ ] **Geonames**: Search/autocomplete for country, admin1, postcode lookups.
