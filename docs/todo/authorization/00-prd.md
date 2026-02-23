# Authorization (AuthZ) PRD / Delivery Plan

**Document Type:** PRD + Implementation Todo
**Status:** Draft
**Last Updated:** 2026-02-23
**Architecture Source:** `docs/architecture/authorization.md`

---

## 1. Product Goal
Ship a production-usable AuthZ foundation that enforces identical rules for users and their Personal Agents before PA approval workflows are built.

## 2. Success Criteria

1. Every protected action can be traced to a capability key.
2. Policy decisions are consistent across web, API, and PA runtime.
3. Deny-by-default is enforced system-wide.
4. PA cannot perform any action its delegated user cannot perform.

## 3. Scope

### In Scope
1. Capability registry and naming convention
2. RBAC role assignment per company
3. Unified authorization service (`can`, `authorize`, `filterAllowed`)
4. Decision logging with reason codes
5. Menu integration as consumer
6. PA delegated actor evaluation

### Out of Scope
1. Complex policy builder UI
2. Full ACL override engine
3. External IdP policy synchronization

## 4. Staged Delivery

## Stage A - Core Capability Vocabulary

Deliverables:
1. Define capability naming convention and owners
2. Seed baseline capabilities for current modules
3. Add static validation for unknown capability usage

Acceptance:
1. Unknown capability usage fails tests/CI
2. Capability inventory is documented and queryable

## Stage B - Policy Engine + RBAC

Deliverables:
1. Role/capability schema and assignments
2. `AuthorizationService` implementation
3. Company-scope gates

Acceptance:
1. Deny-by-default verified by tests
2. Role grant allows expected actions
3. Cross-company access denied

## Stage C - App Integration Surface

Deliverables:
1. Controller/Volt integration pattern (`authorize(...)`)
2. Route/API middleware hooks where applicable
3. `menu.php` capability checks via service

Acceptance:
1. Menu visibility matches policy decisions
2. Backend endpoints remain enforced even if menu hidden

## Stage D - PA Delegation Integration

Deliverables:
1. `personal_agent` actor mapping to delegated user
2. Delegation constraints in policy engine
3. Decision logs include actor type (`human_user`/`personal_agent`)

Acceptance:
1. PA allow/deny outcomes mirror delegated user baseline
2. PA-specific safety constraints can reduce permissions further
3. Audit records can differentiate PA vs user decisions

## Stage E - Audit, DX, and Hardening

Deliverables:
1. Decision log query endpoint/console tooling
2. Reason-code mapping for user-safe messages
3. Performance checks for hot paths

Acceptance:
1. Security review can reconstruct decision path
2. p95 decision latency remains within target for common checks
3. Error paths fail closed with explicit reason codes

## 5. Work Breakdown (Initial Todo)

1. Finalize capability taxonomy (`<domain>.<resource>.<action>`)
2. Implement authz schema migrations
3. Implement models/repositories for roles/capabilities/assignments
4. Implement `AuthorizationService` and policy pipeline
5. Add policy integration examples in one module end-to-end
6. Add PA delegated actor adapter
7. Add decision logging and reason code enums
8. Add Pest unit + feature coverage
9. Document module integration recipe for adopters

## 6. Test Strategy

### Unit
1. Capability resolution
2. Role/capability evaluation
3. Company scope guard
4. Delegation intersection (PA <= user)

### Feature
1. Protected endpoint allow path
2. Protected endpoint deny path
3. Cross-company denial
4. Menu item visibility by capability
5. PA tool authorization parity with delegated user

### Security/Regression
1. Fail-closed on service exceptions
2. Unknown capability denial
3. Revocation takes effect immediately

## 7. Risks

1. Capability sprawl without ownership discipline
2. Inconsistent enforcement if teams bypass service
3. Hidden coupling between menu rules and backend policies
4. Migration churn while module boundaries are still evolving

## 8. Mitigations

1. Capability owner required per bounded context
2. Lint/static check for direct bypass patterns
3. Shared integration test harness for web/API/PA paths
4. Keep early schema simple and evolvable (destructive changes acceptable now)

## 9. Exit Gate for PA Stage 2 (Approve/Reject)

Do not implement PA approval inbox until all are true:
1. Stage B complete (engine + RBAC)
2. Stage D complete (PA delegation)
3. Decision logging operational
4. At least one sensitive workflow validated end-to-end with AuthZ enforcement
