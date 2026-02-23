# Authorization (AuthZ) Architecture

**Document Type:** Architecture Specification
**Status:** Proposed (Pre-Implementation)
**Last Updated:** 2026-02-23
**Related:** `docs/architecture/user-employee-company.md`, `docs/architecture/ai-personal-agent.md`

---

## 1. Problem Essence
BLB needs one authorization system that consistently decides what both humans and their Personal Agents are allowed to do across UI, APIs, tools, and workflows.

---

## 2. Why AuthZ, Not ACL-First

**AuthZ** is the complete decision system (principals, policies, scope, conditions, audit).

**ACL** is one mechanism (resource-level allow/deny lists).

Decision:
1. Build AuthZ core first.
2. Use RBAC + scoped policies as the baseline.
3. Add ACL-style overrides later only for concrete resource exceptions.

Rationale:
1. BLB rules are cross-cutting (company scope, role, workflow state, delegation).
2. PA approvals and tool execution need policy evaluation, not menu-only checks.
3. ACL-first would couple implementation to resource lists too early and create rework.

---

## 3. Public Interface (First)

All callers (web, API, PA runtime, jobs, menu rendering) use one decision contract.

```php
interface AuthorizationService
{
    public function can(Actor $actor, string $capability, ?ResourceContext $resource = null, array $context = []): AuthorizationDecision;

    public function authorize(Actor $actor, string $capability, ?ResourceContext $resource = null, array $context = []): void;

    public function filterAllowed(Actor $actor, string $capability, iterable $resources, array $context = []): iterable;
}
```

### 3.1 Actor Model

```php
final class Actor
{
    public string $type; // 'human_user' | 'personal_agent'
    public int $id;
    public ?int $companyId;
    public ?int $actingForUserId; // required when type='personal_agent'
    public array $attributes; // role, department, manager chain, etc.
}
```

Rules:
1. A PA is a delegated actor for exactly one user.
2. PA cannot exceed delegated user permissions.
3. Same capability vocabulary applies to human and PA actors.
4. Every decision carries actor type for audit.

### 3.2 Resource Context

```php
final class ResourceContext
{
    public string $type; // e.g. 'employee', 'leave_request', 'invoice'
    public int|string|null $id;
    public ?int $companyId;
    public array $attributes;
}
```

### 3.3 Decision Contract

```php
final class AuthorizationDecision
{
    public bool $allowed;
    public string $reasonCode; // e.g. 'allowed', 'denied_company_scope', 'denied_missing_capability'
    public array $appliedPolicies;
    public array $auditMeta;
}
```

---

## 4. Top-Level Components

### 4.1 Capability Registry

**Responsibility:** Define permission vocabulary and ownership.

Contract:
1. Register capabilities by bounded context (e.g. `employee.view`, `leave.request.submit`).
2. Expose capability metadata to UI and policy layer.
3. Reject unknown capabilities at runtime.

Invariants:
1. Capability keys are unique and stable.
2. Capability definitions are code-reviewed artifacts.

### 4.2 Policy Engine

**Responsibility:** Evaluate allow/deny based on actor, scope, resource, and runtime context.

Contract:
1. Apply baseline checks: authentication, company scope, actor status.
2. Apply RBAC capability checks.
3. Apply conditional policies (ownership, workflow state, manager relation).
4. Return structured `AuthorizationDecision`.

Invariants:
1. Deny by default.
2. Deterministic results for same input tuple.
3. No hidden side effects.

### 4.3 Assignment Store (Roles and Grants)

**Responsibility:** Persist role assignments and optional direct grants.

Contract:
1. Assign role(s) to principals in company scope.
2. Resolve effective capabilities.
3. Support future resource-level overrides without breaking API.

Invariants:
1. Company boundaries are enforced.
2. Revocation takes effect immediately on next decision.

### 4.4 Audit and Explainability Layer

**Responsibility:** Make decisions traceable for security and debugging.

Contract:
1. Emit decision events with actor, capability, outcome, reason.
2. Store enough metadata for incident review.
3. Provide reason codes for user-safe messages.

Invariants:
1. No sensitive secret leakage in user-facing reason.
2. Internal logs retain forensic details.

---

## 5. Policy Model (v1)

### 5.1 Baseline Order of Evaluation

1. Actor validity (exists, active)
2. Company scope gate
3. Capability grant gate (RBAC/direct grant)
4. Resource ownership/state checks
5. Delegation constraints (PA specific)
6. Final allow/deny

### 5.2 Delegation Rules for PA

1. `personal_agent` actor must include `actingForUserId`.
2. Effective permissions = intersection of:
   - delegated user effective permissions
   - PA safety policy (tool/channel limits)
3. High-risk actions may still require human approval even if allowed.

### 5.3 Menu Integration Rule

`menu.php` is a consumer, not source of truth.

Pattern:
1. Menu item declares required capability.
2. Menu renderer calls `AuthorizationService::can(...)`.
3. Hidden menu item does not imply denied backend access (backend enforces separately).

---

## 6. Data Model Direction (v1)

Proposed tables (names may be refined):
1. `authz_roles`
2. `authz_capabilities`
3. `authz_role_capabilities`
4. `authz_principal_roles` (principal_type: user|personal_agent)
5. `authz_principal_capabilities` (optional direct grants)
6. `authz_decision_logs`

Notes:
1. Keep schema company-aware where applicable.
2. Keep principal abstraction explicit so PA and user can share engine.

---

## 7. Module-Level Error Policy

1. Unknown capability -> deny + `denied_unknown_capability` + warning log.
2. Missing actor context -> deny + `denied_invalid_actor_context`.
3. Policy evaluation exception -> deny + `denied_policy_engine_error` + error log.
4. Audit logging failure -> decision result stands, but emit high-severity operational alert.

---

## 8. Expected Call Patterns

1. **Web Controller/Volt Action**
   - `authorize(actor, capability, resource)` before service call.
2. **PA Tool Execution**
   - evaluate as `personal_agent` actor with delegation context.
3. **Menu Rendering**
   - `can(...)` checks only for visibility hints.
4. **Batch Jobs/Queue Workers**
   - use system actor or delegated actor explicitly; never implicit user context.

---

## 9. Complexity Hotspots

1. Multi-company users and company context switching.
2. Manager-subordinate conditional policies.
3. Workflow-state-dependent permissions.
4. Consistency between synchronous UI checks and async job execution.
5. Future ACL overrides without policy ambiguity.

---

## 10. Non-Goals (v1)

1. Full visual policy builder UI.
2. Arbitrary ABAC DSL exposed to adopters.
3. Cross-company delegation for PA.
4. External federated identity policy mapping.

---

## 11. Initial Acceptance Conditions

1. One API for all authorization decisions (`can/authorize/filterAllowed`).
2. Same capability key can be evaluated for both human and PA actors.
3. Deny-by-default proven through tests.
4. UI, API, and PA tool path all call the same policy engine.
5. Decision logs include actor type and reason code.
