# Agent Developer Workflow — IT Ticket Driven

## Document Status

- Status: Draft
- Purpose: define how an AI coding agent operates as an employee, using IT tickets as the work contract and visibility surface
- Position: POC exploration for sb-group dogfooding BLB's agent infrastructure
- Related:
  - `docs/architecture/ai-agent.md` — agent-as-employee architecture
  - `extensions/sb-group/docs/qac/1-qac-domain-model.md` — first target task
  - `app/Modules/Business/IT/` — IT ticket module
  - `app/Modules/Core/AI/` — Lara orchestration and agentic runtime

## 1. Problem Essence

A human supervisor assigns development work to an AI coding agent. Today there is no shared surface for task assignment, progress tracking, discussion, and deliverable review between the human and the agent. The IT ticket module already provides this surface for human-to-human IT work.

## 2. Core Idea

Use the existing IT ticket as the contract between human supervisor and coding agent:

- **Supervisor** creates or approves a ticket describing the work.
- **Coding agent** picks up the ticket, posts progress to the ticket timeline, asks questions there, and marks deliverables.
- **Supervisor** reviews, comments, and transitions the ticket through approval/closure.

The ticket becomes the single source of truth for what was requested, what was done, and what decisions were made.

## 3. Agent Topology

### 3.1 Lara as Orchestrator

Lara remains the general-purpose assistant. When the supervisor says "build the QAC module," Lara:

1. Creates (or helps draft) an IT ticket with structured scope.
2. Delegates execution to a coding agent via `LaraTaskDispatcher`.
3. Can check delegation status and relay summaries.

Lara does **not** write code herself. She orchestrates and advises.

### 3.2 Kodi — The Coding Agent

Kodi is BLB's second primitive agent (`Employee::KODI_ID = 2`, `employee_number = 'SYS-002'`), provisioned at install time alongside Lara (`Employee::LARA_ID = 1`). While Lara is the system orchestrator, Kodi is the system developer.

- Own workspace: `workspace/2/` with `IDENTITY.md`, `SOUL.md`, `config.json`.
- Coding-optimized LLM: Claude Sonnet/Opus via `config.json` model selection.
- Coding-specific tools: file read/edit, bash, git, test runner (some already exist in `app/Modules/Core/AI/Tools/`).
- Scoped to the repository and the feature branch for the ticket.

### 3.3 Why Two Agents, Not One

| Concern | Lara (orchestrator) | Kodi (coding agent) |
|---------|--------------------|-----------------------|
| LLM model | Cost-efficient (GPT-4o-mini, Gemini Flash) | Coding-optimized (Claude Sonnet/Opus) |
| Tools | Navigation, search, delegation, general assistance | File edit, bash, git, test runner, ticket updates |
| Context window | Short conversations, quick answers | Long sessions, full file context |
| Permissions | Broad read, limited write | Narrow scope, deep write access to repo |
| Accountability | General assistant | Traceable to specific ticket and branch |

This follows the architecture doc §15: "a coding agent might use Claude Opus, a general-purpose agent might use an open-weight model."

## 4. Human-in-the-Loop Flow

```
Supervisor                        IT Ticket                         Kodi (Coding Agent)
    │                                 │                                  │
    ├── "Build QAC CaseRecord" ───────┼── Ticket created (OPEN) ────────►│
    │                                 │                                  │
    │                                 │◄── Self-assigns (ASSIGNED) ──────┤
    │                                 │◄── "Reading domain model doc,    │
    │                                 │     starting with migration"  ───┤
    │                                 │                                  │
    │                                 │◄── Transitions to IN_PROGRESS ───┤
    │                                 │◄── "Migration + model created.   │
    │                                 │     Question: shared table or    │
    │                                 │     core + detail tables?" ──────┤
    │                                 │                                  │
    │   (notification)                │   (status: BLOCKED)              │
    │                                 │                                  │
    ├── "Shared table per §17         │                                  │
    │    of domain doc" ──────────────┼── Comment delivered ─────────────►│
    │                                 │                                  │
    │                                 │◄── Transitions to IN_PROGRESS ───┤
    │                                 │◄── "Model + factory + seeder +   │
    │                                 │     Pest tests complete.         │
    │                                 │     Branch: feat/qac-case-record │
    │                                 │     PR ready." ─────────────────┤
    │                                 │                                  │
    │                                 │◄── Transitions to REVIEW ────────┤
    │                                 │                                  │
    ├── Reviews PR, approves ─────────┼── Transitions to RESOLVED ──────►│
    ├── Merges, closes ───────────────┼── Transitions to CLOSED ────────►│
```

## 5. What Exists Today

### 5.1 IT Ticket Module (`app/Modules/Business/IT/`)

- `Ticket` model with `assignee_id`, `status`, `priority`, `category`, `description`, `metadata` (JSON).
- Workflow flow `it_ticket` with statuses: `open → assigned → in_progress → awaiting_parts → resolved → closed`.
- Capability-gated transitions (e.g. `workflow.it_ticket.assign`).
- Timeline rendered from `StatusHistory` on the Show page.
- Livewire CRUD: `Index`, `Create`, `Show`.

### 5.2 Agent Infrastructure (`app/Modules/Core/AI/`)

- `AgenticRuntime` — iterative LLM ↔ tool-calling loop (max 10 iterations).
- `LaraOrchestrationService` — `/delegate` command dispatches to agents.
- `LaraTaskDispatcher` — **stub only** (returns a `dispatch_id` without queue execution).
- `LaraCapabilityMatcher` — selects best agent for a task description.
- `DelegateTaskTool` / `DelegationStatusTool` — Lara's tools for delegation.
- Existing tools: `BashTool`, `WriteJsTool`, `EditDataTool`, `QueryDataTool`, `WebFetchTool`, `WebSearchTool`, `BrowserTool`, `DocumentAnalysisTool`, `ImageAnalysisTool`.

### 5.3 Agent-as-Employee Model (`docs/architecture/ai-agent.md`)

- Agent is `Employee` with `employee_type = 'agent'`.
- Supervisor chain, delegated authorization, capability-scoped permissions.
- Per-agent workspace: `workspace/{employee_id}/` with `config.json`, `sessions/`, future `MEMORY.md`.
- Per-agent LLM config with ordered fallback.

## 6. Gaps to Fill

### 6.1 IT Module Changes

| Change | Where | Description |
|--------|-------|-------------|
| **FK migration: `assignee_id` → `employees`** | Migration | Change `assignee_id` FK from `users` to `employees`. Change `reporter_id` FK from `users` to `employees`. Update model relationships accordingly. |
| **New workflow statuses** | `TicketWorkflowSeeder` | Add `blocked` (agent needs human input) and `review` (agent deliverable awaits human review). |
| **New transitions** | `TicketWorkflowSeeder` | `in_progress → blocked`, `blocked → in_progress`, `in_progress → review`, `review → resolved`, `review → in_progress` (rework). |
| **Programmatic ticket creation** | New service or refactor `Create.php` | Extract ticket creation logic into a service callable by agents (accepts `Actor` instead of `Auth::user()`). |
| **Agent comment tags** | Convention on `StatusHistory.comment_tag` | Define tags: `agent_progress`, `agent_question`, `agent_deliverable`, `agent_error`. The Show page can render these with distinct styling. |
| **Ticket metadata for agent context** | `Ticket.metadata` | Store structured agent context: `dispatch_id`, `branch_name`, `related_docs` (paths the agent should read). |

### 6.2 AI Module Changes

| Change | Where | Description |
|--------|-------|-------------|
| **Real `LaraTaskDispatcher`** | `LaraTaskDispatcher.php` | Replace stub with actual queue job dispatch. The job: loads agent workspace, builds system prompt, injects ticket context, runs `AgenticRuntime`. |
| **Agent queue job** | New job class | A Laravel job that executes the coding agent's work session. Reads the ticket, loads context docs, runs the tool-calling loop, posts results back to the ticket. |
| **`TicketUpdateTool`** | New tool | Lets the agent post to the ticket's `StatusHistory` — progress updates, questions, deliverables. Also lets the agent transition the ticket (e.g. `in_progress → blocked`). |
| **`ReadFileTool`** | New tool (or alias `BashTool`) | Read project files. `BashTool` exists but a dedicated tool with path validation is safer. |
| **`EditFileTool`** | New tool | Edit project files with diff-based changes. Critical for a coding agent. |
| **`GitTool`** | New tool | Branch creation, commit, push. Scoped to the agent's working branch. |
| **`RunTestsTool`** | New tool | Execute `php artisan test` with path filtering. Agent should verify its own work. |

### 6.3 Coding Agent Provisioning

| Artifact | Description |
|----------|-------------|
| **Employee record** | Primitive agent: `Employee::KODI_ID = 2`, `employee_number = 'SYS-002'`, `employee_type = 'agent'`, `full_name = 'Kodi Belimbing'`, `short_name = 'Kodi'`, `designation = 'System Developer'`. Provisioned at install time via `Employee::provisionKodi()`, same pattern as Lara. |
| **Workspace `config.json`** | LLM: Claude Sonnet as primary, fallback to GPT-4o. High `max_tokens` (8192+). |
| **Workspace `IDENTITY.md`** | Name, role, emoji. "Kodi, BLB system developer." |
| **Workspace `SOUL.md`** | Operating principles: "Read the project `AGENTS.md` and nearest nested `AGENTS.md`. Follow conventions. Write Pest tests. Use `query()` not magic statics. Single quotes. Post progress to the ticket." |
| **Workspace `AGENTS.md`** | Session behavior: tool preferences, memory strategy, when to ask vs. act. |
| **AuthZ capabilities** | `it_ticket.ticket.create`, `it_ticket.ticket.view`, `workflow.it_ticket.assign` (self-assign), plus whatever module capabilities the task requires. |

## 7. Ticket Structure for Agent Tasks

A ticket assigned to a coding agent should carry structured context in `metadata`:

```json
{
    "agent_dispatch_id": "agent_dispatch_abc123",
    "branch": "feat/qac-case-record",
    "context_docs": [
        "extensions/sb-group/docs/qac/1-qac-domain-model.md"
    ],
    "scope": "Build CaseRecord model, migration, factory, and Pest tests per domain model §6.1",
    "acceptance_criteria": [
        "Migration creates qac_case_records table with all §6.1 fields",
        "Model uses HasWorkflowStatus, registers flow qac_internal_case",
        "Factory covers all fields",
        "Pest feature test covers creation and key relationships",
        "Pint passes"
    ],
    "constraints": [
        "Follow extensions/ directory layout per extensions/README.md",
        "Use 0400 migration prefix for sb-group modules"
    ]
}
```

The agent reads this structured context instead of parsing free-text descriptions. The `description` field remains human-readable for the ticket UI.

## 8. Session and Memory Model

Each ticket maps to one agent session:

```
workspace/{employee_id}/
├── config.json
├── IDENTITY.md
├── SOUL.md
├── AGENTS.md
├── sessions/
│   ├── {ticket_id}.jsonl       # Conversation history for this ticket
│   └── {ticket_id}.meta.json   # Session metadata
├── MEMORY.md                    # Cross-ticket learnings
└── memory/
    └── 2026-03-24.md            # Daily log
```

When the agent resumes work on a ticket (e.g. after receiving a human reply to a `blocked` question), it reloads the session from `{ticket_id}.jsonl` and continues.

## 9. Security and Scope Boundaries

### 9.1 Repository Access

The coding agent operates on a **feature branch** in the same repository. It must not:

- Push to `main` directly.
- Modify files outside the ticket's declared scope (enforced by `SOUL.md` instruction + PR review).
- Access other licensees' extensions.

### 9.2 Authorization

Per `docs/architecture/ai-agent.md` §5.1:

- Agent capabilities are a strict subset of supervisor capabilities.
- Agent cannot escalate its own permissions.
- Every action is attributable via the supervision chain.

### 9.3 Tool Safety

- `BashTool` / `EditFileTool` are `ToolRiskClass::DESTRUCTIVE` — require explicit capability grant.
- `TicketUpdateTool` is `ToolRiskClass::INTERNAL` — safe for agent self-service.
- Git push requires the branch name to match the ticket's declared branch.

## 10. Recommended POC Sequence

### Phase 1: Manual Walkthrough (No Code Changes)

1. Kodi is provisioned at install time (`Employee::provisionKodi()`). Verify the record exists.
2. Manually create an IT ticket with structured `metadata`.
3. Impersonate the agent: use the Lara playground or a script to simulate the agent reading the ticket, working, and posting updates to `StatusHistory`.
4. Validate that the ticket UI renders agent updates clearly.

### Phase 2: Ticket ↔ Agent Wiring

1. Add `blocked` and `review` statuses + transitions to `TicketWorkflowSeeder`.
2. Extract ticket creation into a service (decouple from `Auth::user()`).
3. Build `TicketUpdateTool` — agent can post to ticket timeline and transition status.
4. Render agent-tagged comments with distinct styling in the Show page.

### Phase 3: Real Dispatch

1. Wire `LaraTaskDispatcher` to a real Laravel queue job.
2. Build the agent queue job: load workspace → build prompt → inject ticket context → run `AgenticRuntime` → post results.
3. Build `EditFileTool` and `GitTool` for the coding agent.
4. End-to-end test: supervisor creates ticket → agent picks up → agent creates a branch, writes code, runs tests, posts PR → supervisor reviews.

### Phase 4: First Real Task

1. Use the QAC `CaseRecord` model (§6.1 of the domain model doc) as the first real coding task.
2. Scope it tightly: one model, one migration, one factory, one test file.
3. Evaluate: quality of output, usefulness of ticket-based tracking, supervisor interaction friction.

## 11. Resolved Design Decisions

1. **Ticket assignment model**: `assignee_id` stays on the ticket but its FK changes from `users` to `employees`. `Employee` is the unified workforce entity — every `User` already links to an `Employee` via `users.employee_id`. This gives us one assignment field that works for both humans and agents without ambiguity. The `reporter_id` FK also changes from `users` to `employees` for consistency — reporters can be agents too (e.g. an agent filing a sub-task ticket).

2. **No iteration limit**: the agent uses whatever it takes to complete the task. The hardcoded `AgenticRuntime::MAX_ITERATIONS = 10` is not appropriate for coding work. The implementation approach: multi-session loop — the agent completes one session, persists state to the ticket timeline, then starts another session. The ticket is the continuity anchor, not the runtime iteration counter.

3. **Merge conflicts**: always attempt resolution first. The agent should try to resolve conflicts using context from the ticket and the conflicting changes. Only transition to `blocked` if resolution fails or introduces ambiguity that requires human judgement.

4. **Costing**: deferred. Token spend tracking is a future HR/finance module concern. Not blocking for POC.

5. **Ticket structure**: flat. No parent-child ticket hierarchy. Use `#IT-{id}` mentions in ticket descriptions and comments to link related tickets. Simple, scannable, avoids over-engineering the ticketing model.

6. **Notifications**: flexible, at stakeholder discretion. Each stakeholder chooses per-event-type: off, database notification, email, or future channels. This aligns with the existing infrastructure — `StatusConfig.notifications` already supports `on_enter` recipient types and `channels`, and the `Setting` model provides per-employee cascading preferences (Employee → Company → Global). No new notification system needed; wire the existing pieces together.

## 12. Framework Prerequisites (BLB PRs)

Before this workflow can function, the following BLB framework changes are needed. These are PRs to `main`, not licensee-scoped work.

### 12.1 IT Module: FK Migration to Employees

**Priority: P0 — blocks everything else.**

Both `reporter_id` and `assignee_id` on `it_tickets` currently FK to `users`. They must FK to `employees` instead, since agents are employees without user accounts. This is a schema change + model relationship update + factory update + view update.

Affected files:
- `app/Modules/Business/IT/Database/Migrations/` — new migration to change FKs
- `app/Modules/Business/IT/Models/Ticket.php` — change `reporter()` and `assignee()` to `BelongsTo(Employee::class, ...)`
- `app/Modules/Business/IT/Database/Factories/TicketFactory.php` — use `Employee::factory()` instead of `User::factory()`
- `app/Modules/Business/IT/Livewire/Tickets/Create.php` — resolve `reporter_id` from `Auth::user()->employee_id`
- `app/Modules/Business/IT/Livewire/Tickets/Show.php` — no change needed (uses `$ticket->assignee?->name`)
- `app/Modules/Business/IT/Livewire/Tickets/Index.php` — update `reporter` search to use `Employee` relation
- `resources/core/views/livewire/it/tickets/show.blade.php` — no change needed (already uses `?->name`)
- `app/Base/Workflow/Listeners/SendTransitionNotification.php` — `addModelRelation` resolves the relation dynamically; need to verify `Employee` is notifiable or resolve through `Employee→User`
- `app/Modules/Business/IT/Database/Seeders/Dev/DevTicketSeeder.php` — update to use employees

### 12.2 IT Module: Workflow Status Additions

**Priority: P1 — needed for agent-in-the-loop flow.**

Add `blocked` and `review` statuses with transitions:
- `in_progress → blocked` (label: "Block — Needs Input")
- `blocked → in_progress` (label: "Unblock")
- `in_progress → review` (label: "Submit for Review")
- `review → resolved` (label: "Approve")
- `review → in_progress` (label: "Request Rework")

Update `Show.php` status variant map and `Index.php` status variant map to include new statuses.

### 12.3 IT Module: Ticket Creation Service

**Priority: P1 — needed for programmatic ticket creation.**

Extract ticket creation from `Create.php` Livewire component into a domain service that accepts an `Actor` DTO. The Livewire component becomes a thin adapter. Agents and Lara call the service directly.

### 12.4 Notification: Employee Notifiability

**Priority: P2 — needed for agent → supervisor notifications.**

The `SendTransitionNotification` listener expects notifiable recipients (objects with `notify()` method). Today it resolves `User` objects from model relations. With the FK change, relations now return `Employee` objects. Need to either:
- Make `Employee` notifiable (implements `Notifiable` trait, routes notifications through linked User), or
- Add a resolution step: `Employee → User` → notify.

The first option is cleaner — `Employee` becomes the notification subject regardless of type. For agents, notifications could be stored as database records or silently dropped (agent doesn't need email).
