# AI Autonomous Employee (AE) Architecture

**Document Type:** Architecture Specification
**Status:** Planning
**Last Updated:** 2026-02-25
**Related:** `docs/architecture/user-employee-company.md`, `docs/architecture/authorization.md`, `docs/architecture/database.md`

---

## 1. Problem Essence

BLB needs autonomous employees (AE) to be managed as first-class employees under the same organizational model and authorization system as humans, with clear delegation boundaries and accountable supervision.

---

## 2. Decision Summary

1. AE is an employee under the same management UI and org structure as human employees.
2. Human and AE share one employee model/table.
3. Existing employee attributes are reused; non-applicable fields are nullable.
4. Add only minimal new employee fields now: `employee_type` and `job_description`.
5. Cost/token accounting is deferred to a future HR module.
6. AE permissions are constrained by delegation and cannot exceed supervisor effective permissions.

---

## 3. Public Interface

### 3.1 Workforce Subject Model

One subject model for authorization and org operations:

- `Employee` with `employee_type = human | ae`
- Both can be assigned roles and permissions
- Both can supervise subordinate employees

### 3.2 Required Operations

1. `createEmployee(...)`
2. `updateEmployee(...)`
3. `assignSupervisor(employeeId, supervisorId)`
4. `assignRole(employeeId, roleId)`
5. `grantPermission(employeeId, capability)`
6. `revokePermission(employeeId, capability)`
7. `setJobDescription(employeeId, text)`
8. `disableEmployee(employeeId)`

### 3.3 AE-Specific Management Operations

1. `createAutonomousEmployee(supervisorId, profile)`
2. `setAutonomousEmployeeScope(employeeId, scope)`
3. `validateDelegation(supervisorId, subordinateId)`

The UI remains the same employee UI; AE uses type-aware behavior, not a separate product surface.

---

## 4. Employee Data Model (Current Scope)

### 4.1 Single Table Strategy

Use one `employees` table for both human and AE records.

### 4.2 Minimal Additions

1. `employee_type` (enum-like value: `human`, `ae`)
2. `job_description` (`TEXT`, nullable at DB level, required by domain workflow)

### 4.3 Attribute Applicability

- Existing attributes remain available for both types.
- Non-applicable attributes are `NULL`.
- `phone` remains nullable and valid for AE (messaging channels now or later).
- `date_of_birth` is interpreted as employee identity birth date.
  - Human: biological date of birth.
  - AE: identity creation date.

No additional AE operational or financial fields are added at this stage.

---

## 5. Authorization and Delegation Rules

### 5.1 Core Invariants

1. AE effective permissions must be a strict subset of supervisor effective permissions.
2. Delegation cannot create new privileges.
3. Explicit deny always wins.
4. Every AE must have a supervision chain that resolves to a human accountable owner.
5. Supervision graph must be acyclic.

### 5.2 Supervisor Model

- Supervisor can be human or AE.
- Human employees with required capability can create/manage subordinate AEs.
- One supervisor can manage multiple subordinate AEs, subject to policy limits.

### 5.3 Capability Gate (Illustrative)

Capabilities for AE administration should be explicit in AuthZ, for example:

1. `employee.ae.create`
2. `employee.ae.update`
3. `employee.ae.assign_role`
4. `employee.ae.assign_permission`
5. `employee.ae.disable`

The final capability vocabulary is owned by the AuthZ module.

---

## 6. UI and UX Rules

1. Employee listing includes both human and AE rows.
2. Display type badges: `Human` / `AE`.
3. Employee create/edit flow includes type selector.
4. Forms render the same base fields; optional guidance can hide non-relevant physical fields for AE.
5. Supervisor and role assignment flows are shared.
6. Delegation policy violations return explicit deny reasons.

---

## 7. Error Policy

1. Policy violations are denied deterministically by AuthZ.
2. Validation errors return field-specific messages.
3. Privilege escalation attempts are logged as security events.
4. Management actions must be auditable with actor, target employee, and decision reason.

---

## 8. Implementation Boundaries (Now vs Later)

### 8.1 In Scope Now

1. AE as `employee_type = ae` in a unified employee model.
2. `job_description` support.
3. Delegation constraints integrated with shared AuthZ.
4. Unified management UI behavior.

### 8.2 Out of Scope Now

1. HR-specific compensation, token spend, and cost accounting.
2. Rich AE runtime telemetry fields in employee core table.
3. Channel-level integration details (Telegram/WhatsApp) as schema drivers.

---

## 9. Alignment with BLB Principles

1. Deep module boundary: complexity (delegation, policy checks, audit rules) is hidden in AuthZ + employee domain services.
2. Simple public interface: managers operate through familiar employee workflows.
3. Strategic programming: avoid premature AE-specific schema sprawl while preserving forward compatibility.

---

## 10. Open Questions

1. Should `job_description` be mandatory at create time for both `human` and `ae`, or required before activation only?
2. Should policy set a hard maximum depth for AE supervision chains?
3. Should AE creation require dual approval for high-privilege departments?

---

## 11. OpenClaw Architecture (Research Findings)

*Relevant when designing AE execution, tooling, or channel integration. Source: OpenClaw agent system research.*

### 11.1 High-Level Architecture

**Pattern:** Skills (teach) + Tools (execute) + Policies (constrain) + Channels (interface)

```
User Message (WhatsApp/Telegram/Slack)
  ↓
Gateway (routing & access control)
  ↓
Queue (session-based serialization)
  ↓
Agent (AI runtime with skills & tools)
  ↓
Tool Execution (sandboxed if configured)
  ↓
Response (streamed back to channel)
  ↓
Session Persistence (JSONL history)
```

### 11.2 Core Components

#### Agent
- Embedded AI runtime based on pi-mono
- Processes messages through serialized execution loop
- Each agent has dedicated workspace directory
- Session manager for conversation history
- Bootstrap files for context (AGENTS.md, SOUL.md, TOOLS.md, etc.)

**Execution Model:**
- Runs serialized per session (prevents race conditions)
- Each run has unique `runId` for tracking
- Sessions isolated by `sessionKey` (e.g., per user, per group)
- Supports Docker sandboxing for security

#### Skills
AgentSkills-compatible instruction packs (Markdown files)

**Structure:**
```
skill-name/
├── SKILL.md          # YAML frontmatter + instructions
└── (optional files)
```

**Loading Precedence:**
1. Workspace skills (highest priority)
2. Managed skills (user-installed)
3. Bundled skills (shipped with system)

**Conditional Loading (Gating):**
- OS platform requirements
- Required binaries (e.g., csvkit)
- Environment variables
- Config values

#### Tools
Executable functions exposed to the AI

**Categories:**
- **Coding Tools:** File operations (read, write, edit, exec)
- **Web Tools:** External data (web_fetch, web_search)
- **Messaging Tools:** Send messages across channels
- **Session Tools:** Multi-agent coordination (sessions_send, sessions_spawn)
- **Platform Tools:** System integration (browser, canvas)

**Tool Schema:**
```typescript
interface Tool {
  name: string;
  description: string;
  schema: JSONSchema;  // TypeBox/Zod schema for parameters
  execute: (toolCallId: string, params: unknown) => Promise<ToolResult>;
}
```

#### Policies
Multi-level security constraints

**Policy Resolution Layers:**
1. Tool profile policy (e.g., "safe", "full")
2. Per-model overrides
3. Global allow/deny
4. Per-agent overrides
5. Per-group/channel policies
6. Sandbox restrictions
7. Subagent restrictions

**Example Policy:**
```json
{
  "tools": {
    "allow": ["customer_lookup", "invoice_create"],
    "deny": ["database_raw_query"],
    "exec": {
      "security": "allowlist",
      "ask": "on-miss",
      "safeBins": ["git", "npm"]
    }
  }
}
```

### 11.3 Agent Execution Loop (Summary)

1. **Message Entry** — User sends via channel; gateway validates; enqueued in session lane
2. **Session Resolution** — Load history from JSONL; restore context; token budgeting
3. **Context Assembly** — Load bootstrap files and eligible skills; build system prompt
4. **LLM Inference** — Send prompt + tools; stream response; process tool calls
5. **Tool Execution Loop** — Validate against policies; execute (sandbox if configured); log; return result
6. **Response Delivery** — Stream back to channel; persist transcript
7. **Cleanup & Logging** — Save session state; audit log

**Timeout Handling:** Agent runtime ~600s default; wait timeout ~30s client-side; AbortSignal for cancellation.

### 11.4 Security Mechanisms

- **Access Control:** Channel-specific policies (DM vs group), pairing codes, allowlists, mention requirements
- **Tool Policy:** Multi-level allow/deny; per-user, per-company, per-tool; approval workflows for sensitive ops
- **Sandboxing:** Docker containers; workspace access modes (none/ro/rw); resource limits; network isolation
- **Exec Approvals:** Human-in-the-loop for shell commands; allowlist of safe commands
- **Session Isolation:** Separate sessions per user; company-scoped data; no cross-user/cross-company leakage
- **Audit & Compliance:** All tool executions logged; session transcripts retained; security audit commands

### 11.5 Research References

- Agent runtime, session management: `openclaw/src/agents/`
- Skills: `openclaw/skills/` (AgentSkills-compatible)
- Tools: `openclaw/src/agents/tools/`
- Channels: `openclaw/src/{telegram,discord,slack,signal,imessage,web}/`
- Security: `openclaw/docs/gateway/security/`
- Execution flow: `openclaw/docs/concepts/agent-loop.md`

---

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 0.1 | 2026-02-25 | AI + Kiat | Pivoted from PA document to AE architecture; unified employee model and delegation invariants |
