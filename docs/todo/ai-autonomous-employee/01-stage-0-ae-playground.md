# Stage 0 - AE Playground (Implementation Checklist)

**Parent Plan:** `docs/todo/ai-autonomous-employee/00-staged-delivery-plan.md`
**Scope:** Web-only AE chat loop with persistent sessions/messages and visible runtime metadata
**Target Outcome:** A user can open AE Playground, chat, switch sessions, refresh, and keep full history.
**Prerequisite:** `docs/architecture/authorization.md` and `docs/todo/authorization/00-prd.md` Stage B + Stage D

## 1. Stage 0 Contract

### In Scope
1. Authenticated web UI for chat and session switching
2. Persisted session/message history
3. Basic AE response runtime (no business write tools)
4. Debug metadata visible in UI (run id, model, latency)

### Out of Scope
1. Approval workflow
2. External channels (WhatsApp/Telegram/Slack)
3. Cross-user / AE-to-AE orchestration
4. High-risk tool execution

## 2. UI Deliverables

1. `AE Playground` page route
2. Left column: session list + “new session” action
3. Main column: chat transcript + composer
4. Right column: debug panel with latest run metadata

## 3. Data Model Deliverables

Create migrations for:

1. `autonomous_employees`
2. `ae_sessions`
3. `ae_messages`

Minimum fields:

1. `autonomous_employees`: `id`, `user_id`, `company_id`, `status`, `context`, timestamps
2. `ae_sessions`: `id`, `autonomous_employee_id`, `channel_type` (`web`), `title`, `last_activity_at`, timestamps
3. `ae_messages`: `id`, `ae_session_id`, `role` (`user|assistant|system`), `content` (JSON/text), `run_id`, `meta` (JSON), timestamps

Constraints/indexes:

1. FK integrity across all three tables
2. Index on `ae_sessions.autonomous_employee_id`
3. Index on `ae_messages.ae_session_id`
4. Index on `ae_messages.run_id`

## 4. Backend Deliverables

1. `PlaygroundSessionService`
   - create/list/switch sessions for current user AE
2. `PlaygroundMessageService`
   - append user message
   - append assistant message
   - fetch ordered timeline
3. `AutonomousEmployeeRuntime` (Stage 0 adapter)
   - takes latest conversation context
   - returns plain assistant text + metadata (`run_id`, `model`, `latency_ms`)
4. Authorization policy
   - user can only access own AE/sessions/messages

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
2. Session create/list only shows own sessions
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
3. Median short-response latency displayed in debug panel
4. No sensitive tools exposed in Stage 0 runtime

## 9. Implementation Order (Recommended)

1. Migrations + models + relationships
2. Authorization policy boundaries
3. Session/message services
4. Runtime adapter (simple, deterministic)
5. Volt UI shell + components
6. Pest tests + UAT run

## 10. Risks and Mitigations

1. **Risk:** Chat state desync between frontend and DB
   - **Mitigation:** Source-of-truth reload after each send completion
2. **Risk:** Session leakage across users
   - **Mitigation:** enforce user-scoped query methods only
3. **Risk:** Runtime latency variance
   - **Mitigation:** capture latency in metadata and expose in debug panel
