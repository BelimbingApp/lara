# Stage 0 - Digital Worker Playground (Implementation Checklist)

**Parent Plan:** `docs/todo/ai-autonomous-employee/00-staged-delivery-plan.md`
**Scope:** Web-only Digital Worker chat loop with persistent sessions/messages and visible runtime metadata
**Target Outcome:** A user can open Digital Worker Playground, chat, switch sessions, refresh, and keep full history.
**Prerequisite:** `docs/architecture/authorization.md` and `docs/todo/authorization/00-prd.md` Stage B + Stage D
**Last Updated:** 2026-02-26

## 1. Stage 0 Contract

### In Scope
1. Authenticated web UI for chat and session switching
2. Persisted session/message history
3. Basic Digital Worker response runtime (no business write tools)
4. Debug metadata visible in UI (run id, model, latency)

### Out of Scope
1. Approval workflow
2. External channels (WhatsApp/Telegram/Slack)
3. Cross-user / Digital Worker-to-Digital Worker orchestration
4. High-risk tool execution

### Digital Worker Chained to Human
Per `docs/architecture/ai-digital-worker.md`: every Digital Worker is an employee with a supervision chain that resolves to a human. In Stage 0, playground sessions belong to a Digital Worker (an employee with `employee_type = 'digital_worker'`); access is scoped by “current user supervises this Digital Worker” (or is the human at the end of the chain).

## 2. UI Deliverables

1. `Digital Worker Playground` page route
2. Left column: session list + “new session” action
3. Main column: chat transcript + composer
4. Right column: debug panel with latest run metadata

## 3. Data Model Deliverables

Align with `docs/architecture/ai-digital-worker.md`: one `employees` table for both human and Digital Worker.

### 3.1 Employees (existing table, add columns)

- `employee_type`: add `'digital_worker'` as a valid value (existing values: full_time, part_time, contractor, intern). When `employee_type === 'digital_worker'`, row is a Digital Worker. Model exposes `isDigitalWorker(): bool` and scopes `digitalWorker()` / `human()`.
- `job_description` (TEXT, nullable) — optional short role label per architecture §4.5; full Digital Worker context is workspace-based when OpenClaw-like runtime is adopted
- `supervisor_id` already exists — for Digital Worker supervision chain to human

### 3.2 New tables (transcript only; memory/recall out of scope — see §11 below)

1. `digital_worker_sessions`
   - `id`, `employee_id` (FK → employees where employee_type = 'digital_worker'), `channel_type` (`web`), `title`, `last_activity_at`, timestamps
2. `digital_worker_messages`
   - `id`, `digital_worker_session_id`, `role` (`user`|`assistant`|`system`), `content` (JSON/text), `run_id`, `meta` (JSON), timestamps
   - **Transcript only** — ordered turn-by-turn chat history for context assembly. Semantic memory (long-term recall over markdown) is a post–Stage 0 concern; see `docs/architecture/ai-digital-worker.md` §12.

### 3.3 Constraints/indexes

1. FK integrity: `digital_worker_sessions.employee_id` → `employees.id`; `digital_worker_messages.digital_worker_session_id` → `digital_worker_sessions.id`
2. Index on `digital_worker_sessions.employee_id`
3. Index on `digital_worker_messages.digital_worker_session_id`
4. Index on `digital_worker_messages.run_id`

## 4. Backend Deliverables

1. `PlaygroundSessionService`
   - create/list/switch sessions for Digital Workers supervised by the current user (or equivalent: sessions for Digital Worker employees whose supervision chain includes current user)
2. `PlaygroundMessageService`
   - append user message
   - append assistant message
   - fetch ordered timeline
3. `DigitalWorkerRuntime` (Stage 0 adapter)
   - takes latest conversation context
   - returns plain assistant text + metadata (`run_id`, `model`, `latency_ms`)
4. Authorization policy
   - user can only access sessions for Digital Workers they supervise (Digital Worker chained to human)

## 5. Frontend Deliverables (Volt/Livewire)

1. Volt page component for playground shell
2. Child component for session list
3. Child component for chat timeline
4. Child component for composer submit action
5. Debug panel component

Behavior requirements:

1. Message submit is optimistic or clearly loading
2. New assistant message appears without full page reload
3. Session switching reloads timeline correctly
4. Refresh preserves selected session and history

## 6. Testing Deliverables (Pest)

### Feature Tests
1. Auth user can open playground
2. Session create/list only shows sessions for Digital Workers the user supervises
3. Message post persists both user and assistant rows
4. User cannot access another user’s session (403/404)
5. Refresh fetches existing timeline in order

### Unit Tests
1. Runtime adapter returns required metadata keys
2. Message service maintains role ordering and timestamp ordering

## 7. Manual UAT Script

1. Login as Employee A
2. Open Playground and create `Session A1`
3. Send 3 prompts and verify assistant responses appear
4. Create `Session A2`, send 1 prompt
5. Switch back to `Session A1` and verify original 3 prompts/history intact
6. Refresh browser and verify active session + messages still present
7. Login as Employee B and verify Employee A sessions are not visible

## 8. Exit Criteria

1. All Stage 0 feature tests pass
2. UAT script passes without data leakage
3. No sensitive tools exposed in Stage 0 runtime

## 9. Implementation Order (Recommended)

1. Migrations (employees columns + digital_worker_sessions + digital_worker_messages) + models + relationships
2. Authorization policy boundaries (Digital Worker chained to human, scope by supervisor)
3. Session/message services
4. Runtime adapter (simple, deterministic)
5. Volt UI shell + components
6. Pest tests + UAT run

## 11. Future: Memory and Recall

Stage 0 persists only the **chat transcript** (messages table). Long-term semantic memory (MemSearch-style: markdown source of truth, vector index for recall) is out of scope. When implementing memory:

- See `docs/architecture/ai-digital-worker.md` §12 for the design: transcript vs memory, MemSearch pattern, PHP-native implementation, SQLite per Digital Worker.
- Workspace layout: `workspace/{employee_id}/` with MEMORY.md, memory/*.md, and memory.db (vector index).

## 12. Risks and Mitigations

1. **Risk:** Chat state desync between frontend and DB
   - **Mitigation:** Source-of-truth reload after each send completion
2. **Risk:** Session leakage across users
   - **Mitigation:** Enforce supervisor-scoped query methods only; Digital Worker chained to human
3. **Risk:** Runtime latency variance
   - **Mitigation:** Capture latency in metadata and expose in debug panel (no target for now)
