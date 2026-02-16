# AI Personal Agent (PA) Architecture — Personal Agents for Business

**Document Type:** Architecture Reference & Vision (Future Implementation)
**Status:** Research / Planning
**Last Updated:** 2026-02-16
**Inspiration:** OpenClaw agent system
**Related:** `docs/brief.md` (AI-Native Architecture)

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Vision: Personal Agents for Every User](#2-vision-personal-agents-for-every-user)
3. [OpenClaw Architecture (Research Findings)](#3-openclaw-architecture-research-findings)
4. [BLB Personal Agent Design](#4-blb-personal-agent-design)
5. [Use Cases: Company-Wide PA Deployment](#5-use-cases-company-wide-pa-deployment)
6. [PA-to-PA Communication](#6-pa-to-pa-communication)
7. [Messaging Channels as Primary Interface](#7-messaging-channels-as-primary-interface)
8. [Security & Safety Model](#8-security--safety-model)
9. [Implementation Considerations](#9-implementation-considerations)
10. [Future Possibilities](#10-future-possibilities)

---

## 1. Executive Summary

**Primary intent:** The PA system exists to **help employees achieve the goals set by the company**. Everything the PA does—daily planning, task execution, workflows, and coordination—serves that end: aligning individual effort with company objectives and removing friction so people can focus on outcomes, not process.

**What:** BLB's AI-native architecture includes **Personal Agents (PAs)** for every user in an organization. Users interact with their PA through familiar messaging channels (WhatsApp, Telegram, Slack, etc.) instead of traditional web forms.

**Why:** To better help employees achieve the set goals of the company. PAs reduce form fatigue, streamline business processes, enable natural language interaction, and provide 24/7 support so that users can focus on goals rather than learning complex UIs.

**How:** Based on OpenClaw's proven architecture: Skills (teach domain knowledge) + Tools (execute business operations) + Policies (ensure safety) + Messaging Channels (natural interface).

**Vision:** Every employee has a PA that knows company policies, can execute workflows, communicate with other PAs, and help users get work done through simple conversations. PAs help users **plan and achieve daily goals in line with company objectives**. The same reporting structure as the organization is maintained: supervisor PAs can instruct subordinate PAs (e.g. to assist users, to take note of something). Work history is maintained for on-demand progress reports over any period; all user interactions are logged (storage mechanism TBD: DB tables or files).

---

## 2. Vision: Personal Agents for Every User

### 2.1 The Problem with Traditional ERP

When process and UI get in the way, employees spend time on *how* to do things instead of *what* the company needs done. The result: company goals are harder to achieve, and individual contribution is harder to see and support.

**Current state:**
- Complex web forms with dozens of fields
- Users must learn where every feature is hidden
- Mobile UX is poor (desktop-optimized forms don't work on phones)
- Simple tasks require multiple page navigations
- Knowledge silos (users don't know company policies)

**User frustration examples:**
- "I just want to apply for leave, why do I need to fill out 8 fields?"
- "Which form do I use to request equipment?"
- "I'm on vacation, can't access the VPN to approve this invoice"
- "What's the company policy on expense claims?"

### 2.2 The PA Solution

**Every user gets a Personal Agent (PA) that helps them achieve the company's goals.** The PA does this by:

- **Planning and achieving daily goals** – Breaks down work into clear daily objectives aligned with company and team goals (e.g. onboarding checklist, project milestones, recurring tasks)
- Knowing company policies, workflows, and data
- Being accessible via messaging apps (WhatsApp, Telegram, Slack, etc.)
- Handling tasks through natural conversation
- Working on mobile (anywhere, anytime)
- Learning from interactions (with privacy controls)

**Example interactions:**

```
Employee → PA (WhatsApp):
"Apply for 3 days leave from Feb 15"

PA → Employee:
"✓ Leave request created:
  Dates: Feb 15-17, 2026 (3 days)
  Type: Annual leave
  Balance after: 12 days remaining
  Status: Pending approval from Sarah Chen
  
Should I notify Sarah?"

Employee: "Yes"

PA → Sarah's PA (internal):
{leave_approval_request: employee_id, dates, type}

Sarah's PA → Sarah (Telegram):
"John Doe requested leave Feb 15-17 (3 days).
Team capacity: OK (2 others available)
Approve? [Yes] [No] [Details]"

Sarah: "Yes"

Sarah's PA → John's PA:
{leave_approved}

John's PA → John (WhatsApp):
"✓ Leave approved by Sarah Chen
  Confirmation #: LV-2026-0123
  Added to calendar"
```

### 2.3 Core Principles

1. **Conversation over Forms** - Natural language instead of field-by-field input
2. **Channel-Native** - Users stay in apps they already use (WhatsApp, Slack, etc.)
3. **Context-Aware** - PA knows company policies, user's role, historical patterns
4. **Proactive** - PA suggests actions, reminds deadlines, flags anomalies
5. **Safe & Auditable** - All actions logged, policies enforced, approval workflows respected
6. **PA-to-PA** - Agents communicate to enable cross-department workflows; organizational reporting structure is preserved (supervisor PAs can instruct subordinate PAs)
7. **Work history & interaction logging** - Work history maintained for on-demand progress reports over any period; all user interactions are logged (storage TBD: DB tables or files)

### 2.4 Work History and Interaction Logging

**Work history:** The system maintains work history so that users (and their managers) can request on-demand progress reports for any period. This supports status updates, performance conversations, and compliance.

**User interaction logging:** All user interactions with their PA are logged to support auditing, improvement of PA behavior, and compliance. The exact storage mechanism is to be determined—options include database tables (e.g. normalized `pa_interactions`, `pa_work_history`) or file-based logs (e.g. per-user or per-session JSONL in the PA workspace). Choice will depend on query needs, retention policy, and performance.

---

## 3. OpenClaw Architecture (Research Findings)

### 3.1 High-Level Architecture

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

### 3.2 Core Components

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

### 3.3 Agent Execution Loop (Detailed)

**Complete Flow:**

1. **Message Entry**
   - User sends message via channel (WhatsApp, Telegram, etc.)
   - Gateway validates & resolves session
   - Enqueued in session-specific lane

2. **Session Resolution**
   - Load session history from JSONL
   - Restore conversation context
   - Apply token budgeting

3. **Context Assembly**
   - Load bootstrap files (AGENTS.md, SOUL.md, TOOLS.md)
   - Load eligible skills (filtered by requirements/permissions)
   - Build system prompt with token limits
   - Apply compaction if needed

4. **LLM Inference**
   - Send prompt + available tools to LLM
   - Stream response chunks
   - Process tool calls

5. **Tool Execution Loop**
   - Validate tool call against policies
   - Execute in sandbox if configured
   - Log execution for audit
   - Return result to LLM
   - Continue until completion

6. **Response Delivery**
   - Stream text deltas back to channel
   - Persist complete transcript to session
   - Update conversation history

7. **Cleanup & Logging**
   - Save session state
   - Audit log tool executions
   - Update metrics

**Timeout Handling:**
- Agent runtime: 600s default
- Wait timeout: 30s client-side
- AbortSignal for cancellation

**Event Hooks (Extension Points):**
```typescript
before_agent_start    // Inject company context
before_tool_call      // Validate against business rules
after_tool_call       // Transform results
tool_result_persist   // Audit/compliance logging
agent_end             // Post-processing, analytics
```

### 3.4 Security Mechanisms

**1. Access Control (Who Can Talk)**
- Channel-specific policies (DM vs Group)
- Pairing codes for initial setup
- User/group allowlists
- Mention requirements in groups

**2. Tool Policy (What Can Execute)**
- Multi-level allow/deny lists
- Per-user, per-company, per-tool restrictions
- Approval workflows for sensitive operations

**3. Sandboxing (Blast Radius Reduction)**
- Docker containers for isolated execution
- Workspace access modes: none/ro/rw
- Resource limits (CPU, memory, disk)
- Network isolation options

**4. Exec Approvals (Human-in-the-Loop)**
- Require approval for shell commands
- Allowlist of safe commands
- Interactive confirmation prompts

**5. Session Isolation**
- Separate sessions per user
- Company-scoped data access
- No cross-user/cross-company leakage

**6. Audit & Compliance**
- All tool executions logged
- Session transcripts retained (configurable)
- Security audit commands
- Compliance reporting

---

## 4. BLB Personal Agent Design

### 4.1 Architecture Overview

**BLB PA System:**

```
┌─────────────────────────────────────────────────────────────────┐
│                     BLB Application                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │  User 1 PA   │  │  User 2 PA   │  │  User 3 PA   │          │
│  │  (Employee)  │  │  (Manager)   │  │  (Admin)     │          │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘          │
│         │                  │                  │                 │
│         └──────────┬───────┴──────────────────┘                 │
│                    │                                            │
│         ┌──────────▼──────────────┐                             │
│         │   PA Gateway             │                            │
│         │  (Routing & Dispatch)    │                            │
│         └──────────┬──────────────┘                             │
│                    │                                            │
│         ┌──────────▼──────────────┐                             │
│         │   Skills Registry        │                            │
│         │  (Company Policies,      │                            │
│         │   Workflows, Procedures) │                            │
│         └──────────┬──────────────┘                             │
│                    │                                            │
│         ┌──────────▼──────────────┐                             │
│         │   Tools Registry         │                            │
│         │  (Business Operations)   │                            │
│         └──────────┬──────────────┘                             │
│                    │                                            │
│         ┌──────────▼──────────────┐                             │
│         │   Laravel Backend        │                            │
│         │  (Database, Auth, Queue) │                            │
│         └──────────────────────────┘                            │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
                     ▲
                     │
     ┌───────────────┼───────────────┐
     │               │               │
┌────▼─────┐   ┌────▼─────┐   ┌────▼─────┐
│ WhatsApp │   │ Telegram │   │  Slack   │
│ Channel  │   │ Channel  │   │ Channel  │
└──────────┘   └──────────┘   └──────────┘
     ▲               ▲               ▲
     │               │               │
 Employees       Managers        Executives
```

### 4.2 PA per User Model

**Every user in the company gets their own PA:**

```php
// users table already has company_id
User:
  - id
  - company_id
  - name, email
  
// New: PA configuration per user
PersonalAgent:
  - id
  - user_id
  - company_id
  - preferred_channel (whatsapp, telegram, slack, etc.)
  - phone_number / chat_id / channel_identifier
  - status (active, paused, disabled)
  - permissions (JSON - what this PA can do)
  - context (JSON - user preferences, shortcuts)
  - created_at, updated_at
```

**PA Identity:**
- Each PA is associated with exactly one user
- PA inherits user's permissions + role-based capabilities
- PA knows user's department, supervisor, company context
- PA has access to company-wide skills + user-specific customizations

### 4.3 Workspace Structure

```
storage/app/ai-personal-agent/
├── company_{id}/                    # Company-specific workspace
│   ├── config/
│   │   ├── COMPANY.md              # Company info, policies, structure
│   │   ├── WORKFLOWS.md            # Standard operating procedures
│   │   └── POLICIES.md             # Leave policies, approval rules, etc.
│   ├── skills/                     # Company-specific skills
│   │   ├── leave-application/
│   │   │   └── SKILL.md
│   │   ├── expense-claim/
│   │   │   └── SKILL.md
│   │   ├── invoice-approval/
│   │   │   └── SKILL.md
│   │   └── employee-onboarding/
│   │       └── SKILL.md
│   └── tools/                      # Company-specific tool configs
│       └── tool-policies.json
├── users/                          # Per-user PA data
│   └── user_{id}/
│       ├── sessions/
│       │   └── {sessionId}.jsonl  # Conversation history
│       ├── context/
│       │   ├── shortcuts.json      # User-defined shortcuts
│       │   └── preferences.json    # PA behavior preferences
│       └── audit/
│           └── actions.log         # Audit trail
└── global/
    ├── base-skills/                # BLB framework skills
    │   ├── blb-csv-import/
    │   ├── blb-data-query/
    │   └── blb-report-generator/
    └── tools/                      # Framework-level tools
        └── tool-registry.php
```

### 4.4 Tool Categories for BLB

| Category | Purpose | Example Tools | Safety Level |
|----------|---------|---------------|--------------|
| **Data Query** | Read business data | `customer_lookup`, `invoice_search`, `inventory_check`, `employee_directory` | Low risk |
| **Data Import** | Import external data | `csv_import`, `bulk_update`, `data_sync` | Medium risk |
| **Workflow Execution** | Execute business processes | `leave_apply`, `invoice_approve`, `order_create`, `expense_claim` | Medium risk |
| **Reporting** | Generate insights | `sales_report`, `aging_analysis`, `attendance_summary` | Low risk |
| **Diagnostics** | Troubleshoot issues | `slow_query_analyze`, `queue_status`, `process_trace` | Low risk |
| **Admin Operations** | System management | `user_create`, `permission_grant`, `module_install` | High risk |
| **Code Generation** | Extend system | `generate_livewire`, `create_migration`, `scaffold_module` | High risk (admin only) |

### 4.5 Example Tool Implementation (Laravel)

```php
<?php
// app/Base/AI/Tools/LeaveApplicationTool.php

namespace App\Base\AI\Tools;

use App\Modules\Business\HR\Models\LeaveRequest;
use App\Modules\Core\Employee\Models\Employee;

class LeaveApplicationTool extends BaseTool
{
    public string $name = 'blb_leave_apply';
    
    public string $description = 'Submit a leave application for the current user';
    
    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'start_date' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Leave start date (YYYY-MM-DD)',
                ],
                'end_date' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Leave end date (YYYY-MM-DD)',
                ],
                'leave_type' => [
                    'type' => 'string',
                    'enum' => ['annual', 'sick', 'unpaid', 'compassionate'],
                    'description' => 'Type of leave',
                ],
                'reason' => [
                    'type' => 'string',
                    'description' => 'Reason for leave (optional)',
                ],
            ],
            'required' => ['start_date', 'end_date', 'leave_type'],
        ];
    }
    
    public function execute(string $userId, array $params): array
    {
        // Get employee record
        $employee = Employee::query()
            ->where('user_id', $userId)
            ->firstOrFail();
        
        // Validate leave balance
        $leaveDays = $this->calculateLeaveDays($params['start_date'], $params['end_date']);
        $balance = $employee->getLeaveBalance($params['leave_type']);
        
        if ($leaveDays > $balance && $params['leave_type'] !== 'unpaid') {
            return [
                'success' => false,
                'error' => "Insufficient leave balance. Requested: {$leaveDays} days, Available: {$balance} days",
            ];
        }
        
        // Create leave request
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => $params['leave_type'],
            'start_date' => $params['start_date'],
            'end_date' => $params['end_date'],
            'days' => $leaveDays,
            'reason' => $params['reason'] ?? null,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
        
        // Notify supervisor's PA (PA-to-PA communication)
        if ($employee->supervisor_id) {
            $this->notifySupervisorPA($employee->supervisor, $leaveRequest);
        }
        
        // Log for audit
        $this->logToolExecution('leave_apply', $userId, $params, $leaveRequest->id);
        
        return [
            'success' => true,
            'leave_request_id' => $leaveRequest->id,
            'confirmation_number' => $leaveRequest->confirmation_number,
            'days' => $leaveDays,
            'remaining_balance' => $balance - $leaveDays,
            'status' => 'pending',
            'approver' => $employee->supervisor->full_name ?? 'HR Department',
        ];
    }
}
```

### 4.6 Example Skill (Business Domain Knowledge)

```markdown
---
name: blb-leave-application
description: Apply for leave and check leave balance
metadata:
  blb:
    requires:
      permissions: ['leave.apply']
      employee: true
    category: 'hr-self-service'
---

# Leave Application Skill

Help users apply for leave through natural conversation.

## Company Leave Policy

- Annual leave: 14 days per year (pro-rated for new employees)
- Sick leave: 14 days per year (requires MC if > 1 day)
- Unpaid leave: Unlimited (requires advance approval)
- Compassionate leave: 3 days per incident

## Application Process

1. **Validate request:**
   - Check leave balance for employee
   - Verify dates don't overlap with existing leave
   - Check company blackout dates (e.g., year-end closing)
   - Validate minimum notice period (3 days for annual leave)

2. **Calculate days:**
   - Count only working days (exclude weekends, public holidays)
   - Half-day leave = 0.5 days
   
3. **Get approval:**
   - Immediate supervisor for ≤ 3 days
   - Department head for 4-7 days
   - HR Director for > 7 days or special circumstances

4. **Create leave request:**
   - Use `blb_leave_apply` tool
   - Return confirmation number
   - Notify approver's PA

## Conversation Examples

**Simple leave:**
User: "I need leave Feb 15-17"
PA: Understands annual leave, 3 days, asks to confirm

**With context:**
User: "Apply sick leave tomorrow"
PA: Asks if user has MC, reminds to submit within 3 days

**Balance check:**
User: "How much annual leave do I have?"
PA: Uses `blb_leave_balance` tool, shows remaining days + expiry

## Edge Cases

- Insufficient balance → suggest unpaid leave option
- Overlapping leave → show conflict, suggest alternative dates
- Blackout period → explain policy, suggest adjacent dates
- Backdated leave → require supervisor override

## Tool Mapping

- Check balance: `blb_leave_balance`
- Apply for leave: `blb_leave_apply`
- Check status: `blb_leave_status`
- Cancel leave: `blb_leave_cancel` (if not yet approved)
```

---

## 5. Use Cases: Company-Wide PA Deployment

### 5.1 HR & Self-Service

#### Leave Management
**User → PA (WhatsApp):**
```
"Apply for 2 days sick leave starting tomorrow"
"Check my leave balance"
"Cancel my leave on Feb 20"
"When can I take 1 week off without affecting the team?"
```

**PA → User:**
- Validates balance, creates request
- Notifies supervisor's PA for approval
- Updates calendar, sends confirmation
- Proactively reminds about MC submission

#### Expense Claims
```
User: "Claim taxi fare $45.50"
PA: "Receipt? [Upload photo]"
User: [Uploads receipt photo]
PA: 
  - OCR extracts amount, date, vendor
  - Validates against policy (max $50 per trip)
  - Creates expense claim
  - Routes to manager for approval
```

#### Employee Onboarding
```
HR: "Onboard John Doe, Software Engineer, starts Feb 15"
PA:
  - Creates employee record
  - Generates employee number
  - Provisions user account
  - Assigns to IT department
  - Triggers onboarding checklist
  - Notifies IT (laptop), Facilities (workspace), HR (orientation)
  - Creates PA for new employee
```

### 5.2 Operations & Workflow

#### Invoice Processing
```
AP Clerk: "Create invoice for PO-2026-0123"
PA:
  - Retrieves PO data
  - Pre-fills invoice fields from PO
  - Validates against PO (quantities, prices)
  - Creates draft invoice
  - Routes to manager for approval
  - Notifies supplier upon approval
```

#### Order Fulfillment
```
Warehouse Staff: "Ship order ORD-456"
PA:
  - Validates order status (paid, in stock)
  - Generates packing list
  - Reserves inventory
  - Creates shipment record
  - Notifies customer with tracking
  - Updates order status
```

#### Inventory Alerts (Proactive)
```
PA → Procurement Manager (Slack):
"⚠️ Low stock alert:
  Item: Widget A (SKU: WDG-001)
  Current: 45 units
  Reorder point: 50 units
  Suggested order: 200 units
  Preferred supplier: ABC Corp
  
Create PO? [Yes] [Snooze] [Adjust]"
```

### 5.3 Management & Decision Support

#### Approval Workflows
```
Manager's PA → Manager (Telegram):
"📋 3 pending approvals:
  1. Leave: John Doe, Feb 15-17 (Team capacity: OK)
  2. Expense: Alice Wang, $850 training course (Within budget)
  3. Purchase: New laptop $2,400 (Pending IT approval)
  
[Approve All] [Review Individually] [Defer]"

Manager: "Approve 1 and 2"

Manager's PA:
  - Updates leave request status
  - Notifies John's PA → John
  - Processes expense claim
  - Notifies Alice's PA → Alice
  - Triggers payment workflow
```

#### Business Intelligence
```
CEO: "How are Q1 sales tracking against target?"
PA:
  - Queries sales data
  - Compares to Q1 target
  - Generates summary with charts
  - Identifies top performers & underperformers
  - Suggests actions

PA → CEO:
"Q1 Sales Progress (as of Feb 9):
  Actual: $1.2M (60% of target)
  Target: $2M
  Projection: $1.8M (90% of target) ⚠️
  
  Top performers:
  - Alice (120% of quota)
  - Bob (115% of quota)
  
  At risk:
  - Charlie (45% of quota)
  
  Suggested actions:
  1. Sales coaching for Charlie
  2. Increase marketing in Region B
  
[View Details] [Schedule Review]"
```

### 5.4 Cross-Department Coordination

#### Inter-Department Workflows
```
Sales Rep's PA → Logistics PA:
"New urgent order ORD-789:
  Customer: Priority Client Inc
  Delivery: By Feb 12 (3 days)
  Items: 500 units Widget A
  Shipping: Express to Singapore
  
Can fulfill?"

Logistics PA → Warehouse Manager:
[Checks inventory, shipping capacity]

Logistics PA → Sales Rep's PA:
"✓ Can fulfill. Confirmed ship date: Feb 11
  Tracking will be ready in 2 hours"

Sales Rep's PA → Sales Rep (WhatsApp):
"✓ Order ORD-789 confirmed for Feb 11 delivery"

Sales Rep's PA → Customer (Email via PA):
[Auto-generated confirmation email with tracking]
```

#### Escalation Chains
```
Junior Staff PA detects pattern:
"This invoice exceeds my approval limit ($10,000)"
  ↓
Escalates to Manager's PA
  ↓
Manager's PA → Manager (notification)
  ↓
If Manager doesn't respond in 2 hours
  ↓
Auto-escalates to Director's PA
```

### 5.5 Knowledge & Training

#### Policy Questions
```
Employee: "What's the policy on working from home?"
PA:
  - Retrieves from company policies (POLICIES.md skill)
  - Checks department-specific overrides
  - Personalizes based on user's role

PA → Employee:
"WFH Policy for Engineering Department:
  - Up to 3 days/week without approval
  - 4-5 days/week requires manager approval
  - Must attend Tuesday team meetings in-office
  - Equipment: Company laptop provided
  
Your current WFH: 2 days this week
Can still WFH: Thu or Fri without approval
  
Need to request WFH? [Yes] [Just Checking]"
```

#### Training & Onboarding
```
New Employee: "How do I submit an expense claim?"
PA:
  - Walks through process step-by-step
  - Asks clarifying questions
  - Shows example
  - Offers to help with first claim
  - Remembers user needed help (reduces future explanation)
```

### 5.6 Proactive Assistance

#### Deadline Reminders
```
PA → Employee (WhatsApp):
"⏰ Reminder: Monthly report due tomorrow
  Last month: Submitted 2 days late
  
Would you like me to:
  1. Generate draft report from data?
  2. Remind me again at 4pm?
  3. Snooze until tomorrow morning?"
```

#### Anomaly Detection
```
PA → Accounting Manager (Slack):
"🚨 Unusual pattern detected:
  Vendor: XYZ Supplies
  Normal monthly: ~$5,000
  This month: $15,000 (3x average)
  
  Invoices:
  - INV-001: $8,000 (approved by John)
  - INV-002: $7,000 (approved by Alice)
  
  Review? [Details] [Flag for Audit] [Mark as Expected]"
```

#### Workflow Optimization
```
PA notices user submitting same type of request weekly
  ↓
PA → User:
"I notice you order office supplies every Monday.
  
Should I:
  1. Auto-create weekly order (you approve)?
  2. Set up recurring order?
  3. Just remind you Mondays?
  
This could save ~10 minutes/week"
```

---

## 6. PA-to-PA Communication

### 6.1 Organizational Reporting Structure

**The same reporting structure as the organization is maintained.** Supervisor PAs can instruct subordinate PAs, mirroring the company hierarchy (e.g. manager → team member, department head → department). Instructions from a supervisor PA to a subordinate PA may include:

- **Assist users** – e.g. "Help John complete his expense report by EOD"
- **Take note of something** – e.g. "Note that the Q1 deadline was moved to Feb 28"
- **Delegate or escalate** – e.g. "Route this approval to Alice's PA when she's back"
- **Request status** – e.g. "Summarize progress on the onboarding tasks for the new hires"

Subordinate PAs respect these instructions within policy and permission bounds. This preserves organizational accountability and enables delegation and oversight through the PA layer.

### 6.2 Internal Communication Protocol

**PAs communicate to coordinate workflows across users:**

```
John's PA → Sarah's PA:
{
  "type": "leave_approval_request",
  "from_user_id": 123,
  "to_user_id": 456,
  "payload": {
    "leave_request_id": 789,
    "employee": "John Doe",
    "dates": "2026-02-15 to 2026-02-17",
    "days": 3,
    "type": "annual",
    "reason": "Family vacation",
    "team_capacity": "OK (2 others available)",
    "urgent": false
  },
  "actions": ["approve", "reject", "defer", "request_details"]
}
```

**Sarah's PA processes:**
- Checks Sarah's calendar (free time to review?)
- Checks Sarah's notification preferences (immediate vs batch?)
- Formats for Sarah's preferred channel (Telegram with buttons)
- Delivers with context

**Sarah responds:**
```
Sarah (Telegram): "Approve"
```

**Sarah's PA → John's PA:**
```json
{
  "type": "leave_approval_response",
  "status": "approved",
  "approved_by_user_id": 456,
  "approved_at": "2026-02-09T10:30:00Z",
  "leave_request_id": 789
}
```

**John's PA updates:**
- Updates leave request status in database
- Adds to calendar
- Generates confirmation number
- Notifies John

### 6.3 PA-to-PA Use Cases

#### Multi-Level Approvals
```
Purchase Requisition > $5,000
  ↓
Employee PA → Manager PA (approval)
  ↓
Manager PA → Finance PA (budget check)
  ↓
Finance PA → CFO PA (final approval)
  ↓
CFO PA → Procurement PA (create PO)
```

#### Department Handoffs
```
Sales PA: Order confirmed
  ↓
Warehouse PA: Pick & pack
  ↓
Logistics PA: Schedule shipment
  ↓
Finance PA: Generate invoice
  ↓
Customer Service PA: Send tracking to customer
```

#### Information Gathering
```
Manager's PA needs data for report
  ↓
Queries multiple Department PAs in parallel:
  - Sales PA: Revenue data
  - Operations PA: Fulfillment metrics
  - Finance PA: Cost data
  - HR PA: Headcount
  ↓
Aggregates responses
  ↓
Generates consolidated report
```

#### Conflict Resolution
```
Two employees request same meeting room
  ↓
Their PAs detect conflict
  ↓
PAs negotiate based on:
  - Request priority
  - Meeting importance
  - User seniority
  - Alternative availability
  ↓
Suggest compromise or escalate to human
```

---

## 7. Messaging Channels as Primary Interface

### 7.1 Why Messaging Channels?

**Critical insight from OpenClaw:** Users interact with AI through channels they already use daily.

**Benefits:**

| Aspect | Traditional Web UI | Messaging Channels |
|--------|-------------------|-------------------|
| **Access** | Must open browser, log in, navigate | Already in WhatsApp/Telegram all day |
| **Mobile UX** | Desktop forms don't work on phones | Native mobile messaging apps |
| **Adoption** | Learning curve for new system | Familiar chat interface |
| **Availability** | VPN required, office hours | Anywhere, anytime, any device |
| **Notifications** | Email (often ignored) | Push notifications (immediate) |
| **Context** | Stateless page reloads | Conversational context maintained |
| **Multi-modal** | Upload forms, limited | Voice, photos, documents, location |

### 7.2 Supported Channels (from OpenClaw)

**Core Channels:**
- WhatsApp (via WhatsApp Web automation)
- Telegram (Bot API)
- Slack (App API)
- Discord (Bot API)
- Signal (Signal-CLI)
- iMessage (macOS only)
- Google Chat
- Microsoft Teams (extension)
- Web Chat (built-in UI)

**For BLB:**
- **Priority:** WhatsApp (dominant in Asia, global reach)
- **Business:** Slack, Microsoft Teams (corporate environments)
- **Fallback:** Web chat (built into BLB UI)
- **Future:** Voice calls (mobile-first countries)

### 7.3 Channel Interaction Patterns

#### Text Messages
```
User: "Create invoice for PO-123"
PA: [Creates invoice, returns confirmation]
```

#### Rich Messages (Buttons/Quick Replies)
```
PA: "Invoice INV-456 ready for approval
     Amount: $12,450
     Vendor: ABC Corp
     Due: Feb 20
     
     [Approve] [Reject] [View Details]"
```

#### Document Upload
```
User: [Uploads CSV file]
     "Import these customers"
     
PA: "Found 150 rows, 3 columns
     Looks like: name, email, phone
     
     Preview:
     1. John Doe, john@example.com, +1234567890
     2. Jane Smith, jane@example.com, +0987654321
     
     Import all 150? [Yes] [Preview More] [Cancel]"
```

#### Voice Messages (Future)
```
User: [Voice note] "Apply for leave next week Monday to Wednesday"
PA: [Transcribes, processes]
     "Leave request created for Feb 12-14 (3 days)
      [Voice reply available]"
```

#### Photos/OCR
```
User: [Photo of receipt]
     "Claim this expense"
     
PA: [OCR extracts]
     "Receipt detected:
      Vendor: Restaurant XYZ
      Amount: $68.50
      Date: Feb 8, 2026
      Category: Client Entertainment?
      
     [Confirm] [Edit] [Cancel]"
```

### 7.4 Channel Routing

**User preferences:**
```php
PersonalAssistant:
  - preferred_channel: 'whatsapp'
  - notification_channels: ['whatsapp', 'email']
  - working_hours: '09:00-18:00'
  - timezone: 'Asia/Singapore'
  - do_not_disturb: false
```

**Smart routing:**
- Urgent approvals → WhatsApp push notification
- Daily summaries → Email
- Routine confirmations → Slack
- After hours → Queue until working hours (unless urgent)

---

## 8. Security & Safety Model

### 8.1 Multi-Tenant Isolation

**Company-Level Isolation:**
```php
// Every PA scoped to company
$pa->company_id;  // Enforced at database level

// All queries scoped automatically
Customer::query()  // Automatically adds where('company_id', $currentCompany)
```

**User-Level Permissions:**
```php
// PA inherits user's role-based permissions
if (!$pa->user->can('approve.invoice')) {
    return ['error' => 'You do not have permission to approve invoices'];
}
```

### 8.2 Tool Safety Levels

| Level | Description | Examples | Requirements |
|-------|-------------|----------|--------------|
| **Safe** | Read-only, no side effects | `customer_lookup`, `report_view` | All users |
| **Standard** | CRUD within user's permissions | `leave_apply`, `expense_claim` | Authenticated users |
| **Privileged** | Admin operations | `user_create`, `permission_grant` | Admin role |
| **Dangerous** | Irreversible, high impact | `database_drop`, `company_delete` | Require explicit approval |

### 8.3 Approval Workflows

**Two-stage safety:**

1. **Tool-level approval:**
   ```php
   'invoice_approve' => [
       'require_approval' => true,
       'approval_method' => 'interactive',  // Ask user to confirm
       'audit_log' => true,
   ]
   ```

2. **Business-rule approval:**
   ```php
   // Within tool execution
   if ($invoice->amount > 10000) {
       return [
           'requires_additional_approval' => true,
           'approver' => $user->supervisor,
           'reason' => 'Amount exceeds approval limit',
       ];
   }
   ```

### 8.4 Audit Trail

**Every PA action logged:**
```php
pa_audit_log:
  - id
  - pa_id (which PA)
  - user_id (which user)
  - tool_name
  - parameters (JSON)
  - result (JSON)
  - success (boolean)
  - executed_at
  - session_id (link to conversation)
```

**Audit queries:**
```php
// Who approved this invoice?
// What data did this PA access today?
// Which PAs executed high-risk operations?
// Trace workflow: leave request → approval → calendar update
```

### 8.5 Data Access Controls

**Row-Level Security:**
```php
// PAs can only access data within their company
// Further restricted by user's role/department

// Example: HR PA can see all employees
// Example: Manager PA can only see their department
// Example: Employee PA can only see their own data
```

**Field-Level Redaction:**
```php
// Salary data redacted for non-HR users
// Personal info masked based on role
// Sensitive fields require explicit permission
```

---

## 9. Implementation Considerations

### 9.1 Technology Stack

**Option A: Embedded TypeScript Runtime (OpenClaw Pattern)**
- Embed Node.js runtime in Laravel
- Use OpenClaw's pi-mono agent engine
- TypeScript for tool/skill definitions
- Laravel as data layer

**Option B: Pure PHP Implementation**
- Laravel-native implementation
- OpenAI/Anthropic SDKs for PHP
- Custom tool/skill registry
- Tighter Laravel integration

**Option C: Hybrid**
- PHP for business logic & tools
- Separate Node.js service for agent runtime
- RPC communication between services

**Recommendation:** Start with Option B (pure PHP), evaluate Option C when scaling needs arise.

### 9.2 Channel Integration

**Messaging Gateway (per OpenClaw pattern):**

```php
// app/Base/AI/Gateway/ChannelRouter.php

interface ChannelAdapter {
    public function receiveMessage(): InboundMessage;
    public function sendMessage(string $recipientId, string $text, array $options): bool;
    public function getChannelType(): string;  // 'whatsapp', 'telegram', etc.
}

class WhatsAppAdapter implements ChannelAdapter {
    // Integrate with WhatsApp Business API or Web API
}

class TelegramAdapter implements ChannelAdapter {
    // Integrate with Telegram Bot API
}

class WebChatAdapter implements ChannelAdapter {
    // Built-in web chat using Livewire
}
```

**Message Flow:**
```
WhatsApp → Webhook → ChannelRouter
  ↓
Identify user (phone number → PersonalAssistant)
  ↓
Enqueue message in user's PA queue
  ↓
PA processes message
  ↓
Response → ChannelRouter → WhatsApp
```

### 9.3 Session Management

```php
// pa_sessions table
PASession:
  - id
  - pa_id
  - user_id
  - company_id
  - channel_type (whatsapp, telegram, web, etc.)
  - channel_identifier (phone number, chat_id, etc.)
  - context (JSON - current conversation state)
  - created_at, updated_at, last_activity_at
  
// pa_messages table (JSONL equivalent)
PAMessage:
  - id
  - session_id
  - role (user, assistant, tool)
  - content (JSON - message content)
  - tool_calls (JSON - if role=assistant)
  - tool_results (JSON - if role=tool)
  - created_at
```

### 9.4 Skills Registry

```php
// app/Base/AI/Skills/SkillRegistry.php

class SkillRegistry {
    protected array $skills = [];
    
    public function discover(): void {
        // Scan storage/app/ai-personal-agent/company_{id}/skills/
        // Parse SKILL.md files
        // Validate requirements
        // Cache skill content
    }
    
    public function getSkillsForPA(PersonalAssistant $pa): array {
        // Filter by:
        // - Company-specific skills
        // - User role/permissions
        // - Department relevance
        // - Gating requirements (bins, env, config)
    }
}
```

### 9.5 Tools Registry

```php
// app/Base/AI/Tools/ToolRegistry.php

class ToolRegistry {
    protected array $tools = [];
    
    public function register(string $name, Tool $tool): void {
        $this->tools[$name] = $tool;
    }
    
    public function getToolsForPA(PersonalAssistant $pa): array {
        // Filter by:
        // - User permissions
        // - Company policies
        // - Tool safety level
        // - Department restrictions
    }
}

// Auto-discover tools from modules
// app/Modules/Business/HR/AI/Tools/LeaveApplicationTool.php
// app/Modules/Business/Finance/AI/Tools/InvoiceApprovalTool.php
```

---

## 10. Future Possibilities

### 10.1 Multi-Modal Interactions

**Voice-First Countries:**
```
User (voice call): "Check my leave balance"
PA (voice response): "You have 12 days of annual leave remaining, expiring December 31st"
```

**Vision/OCR:**
```
User: [Photo of business card]
PA: "New contact detected. Add to CRM?
     Name: Alice Johnson
     Company: Tech Corp
     Email: alice@techcorp.com
     Phone: +65 9123 4567"
```

**Location-Aware:**
```
Sales Rep: [Shares location]
          "Which customers are nearby?"
PA: "3 customers within 5km:
     1. ABC Corp (2.3km) - Last visit: 3 weeks ago
     2. XYZ Ltd (4.1km) - High priority (overdue follow-up)
     3. DEF Inc (4.8km) - New lead
     
     Suggested route: XYZ → ABC → DEF
     [Navigate] [Call XYZ] [Skip]"
```

### 10.2 Learning & Personalization

**Pattern Recognition:**
```
PA notices: User always claims taxi fare on Fridays
PA suggests: "Set up automatic Friday taxi claim reminder?"
```

**Shortcut Creation:**
```
User: "Do the monthly close"
PA: Remembers this means:
  1. Generate sales report
  2. Reconcile accounts
  3. Email to CFO
  4. Archive documents
  
Next month: "Do the monthly close" → executes all steps
```

**Context Learning:**
```
First time:
User: "Create invoice for ABC"
PA: "Which ABC? ABC Corp or ABC Supplies?"

After learning:
User: "Create invoice for ABC"
PA: "Creating invoice for ABC Corp (your usual ABC)"
```

### 10.3 Advanced Workflows

#### Autonomous Agents
```
Procurement PA (autonomous):
  - Monitors inventory levels
  - Predicts stockouts (ML)
  - Auto-creates POs with preferred suppliers
  - Only escalates for exceptions
  
CFO just reviews summary:
"This month: 23 POs auto-created, $45K total
 All within policy, 0 exceptions"
```

#### Collaborative Problem Solving
```
Complex customer complaint requiring multiple departments
  ↓
Customer Service PA coordinates:
  - Sales PA: Order history
  - Logistics PA: Shipment tracking
  - Finance PA: Payment status
  - Product PA: Technical specs
  ↓
Synthesizes complete picture
  ↓
Proposes resolution
  ↓
Routes to appropriate manager PA for approval
```

#### Predictive Assistance
```
PA notices pattern: End of month → always rush orders
  ↓
PA → Manager (3 days before month-end):
"Based on last 3 months, you'll likely have 15-20 rush orders
 next week. Should I:
 1. Alert warehouse to prepare capacity?
 2. Schedule overtime in advance?
 3. Notify customers about potential delays?"
```

### 10.4 Industry-Specific PAs

**Manufacturing PA:**
- Monitor production line status
- Alert on quality issues
- Optimize scheduling
- Predict maintenance needs

**Retail PA:**
- Inventory optimization
- Pricing recommendations
- Customer behavior insights
- Promotion planning

**Logistics PA:**
- Route optimization
- Load planning
- Carrier selection
- Delay prediction & mitigation

**Healthcare PA (compliance-heavy):**
- HIPAA-compliant communication
- Patient scheduling
- Medical record retrieval (with strict access controls)
- Regulatory compliance checks

---

## 11. Open Questions & Design Decisions

### 11.1 Critical Questions (Must Resolve Before Implementation)

| ID | Question | Options | Notes |
|----|----------|---------|-------|
| Q1 | PHP-native or TypeScript runtime? | A) Pure PHP<br>B) Embedded Node.js<br>C) Separate service | Affects architecture significantly |
| Q2 | Which messaging channels to prioritize? | A) WhatsApp + Telegram<br>B) Slack + Teams<br>C) Web only first | Depends on target market |
| Q3 | Session storage format? | A) Database (pa_messages table)<br>B) JSONL files<br>C) Both | Compatibility vs performance |
| Q4 | PA deployment model? | A) One PA process per company<br>B) One PA per user<br>C) Shared PA pool | Resource implications |

### 11.2 Important Questions (Resolve During Implementation)

| ID | Question | Options | Notes |
|----|----------|---------|-------|
| Q10 | How to handle PA-to-PA communication? | A) Direct method calls<br>B) Message queue<br>C) Event bus | Affects scalability |
| Q11 | Where to store company skills? | A) Database<br>B) Filesystem<br>C) Both (DB for runtime, files for git) | Git-native workflow consideration |
| Q12 | Sandboxing strategy? | A) Docker (OpenClaw pattern)<br>B) PHP sandbox<br>C) No sandbox (trust + audit) | Security vs complexity |
| Q13 | Voice integration approach? | A) Speech-to-text → PA → TTS<br>B) Native voice models<br>C) Defer | Multi-modal priority |

### 11.3 Business Logic Questions

| ID | Question | Options | Notes |
|----|----------|---------|-------|
| Q20 | Can PAs execute on behalf of users without approval? | A) Always require approval<br>B) Approval for high-risk only<br>C) Configurable per company | Balance safety vs UX |
| Q21 | How to handle mistakes/reversals? | A) Undo command<br>B) Approval workflow<br>C) Immutable log + compensation | Audit trail implications |
| Q22 | Multi-company users (contractors)? | A) Separate PA per company<br>B) Single PA with context switching | Related to User-Employee model |
| Q23 | External partner PAs (suppliers, customers)? | A) Limited guest PAs<br>B) API integration only<br>C) Defer | Security boundary |

---

## 12. Alignment with BLB Vision

**From `docs/brief.md`:**

> **AI-Native Architecture:** AI is the foundation, not a feature.

**How PA System Delivers:**

1. **Democratizes ERP Access**
   - No training needed (natural conversation)
   - Mobile-first (works on any device)
   - Reduces IT support burden

2. **Enables SMB DIY**
   - PAs teach users company processes
   - Reduces need for dedicated trainers
   - Self-service reduces operational costs

3. **Framework Extension Point**
   - Adopters can add custom skills (company-specific procedures)
   - Modules provide tools automatically
   - Extensions can contribute skills/tools

4. **Quality Obsessed**
   - Natural language reduces user errors
   - PAs validate before executing
   - Audit trail for compliance

5. **Git-Native Workflow**
   - Skills stored in filesystem (git-managed)
   - Deploy skills with code
   - Version control for company procedures

---

## 13. Success Metrics (When Implemented)

### Adoption Metrics
- % of employees using their PA weekly
- % of forms replaced by PA interactions
- Active PAs per company

### Efficiency Metrics
- Time to complete common tasks (leave application: 30s vs 5 min)
- Reduction in support tickets (HR queries handled by PA)
- Mobile task completion rate

### Business Metrics
- Approval cycle time reduction
- Error rate in data entry
- User satisfaction (NPS)

---

## 14. Implementation Phases (Future)

### Phase 1: Foundation (MVP)
- PA entity model (one PA per user)
- Basic tool registry (5-10 core tools)
- Web chat channel only
- Simple skill system
- Session management
- Audit logging

**Deliverable:** Users can interact with PA via web chat, execute 5-10 common tasks

### Phase 2: Messaging Channels
- WhatsApp integration
- Telegram integration
- Channel routing & preferences
- Rich messages (buttons, media)

**Deliverable:** Users can interact via WhatsApp/Telegram

### Phase 3: Advanced Workflows
- PA-to-PA communication protocol
- Multi-level approval chains
- Proactive notifications
- 20+ business tools

**Deliverable:** Complex cross-department workflows automated

### Phase 4: Intelligence
- Learning from interactions
- Predictive assistance
- Workflow optimization suggestions
- Voice integration

**Deliverable:** PAs become genuinely intelligent agents

---

## 15. Research References

**OpenClaw Architecture:**
- Agent system: `openclaw/src/agents/` - Agent runtime, session management
- Skills: `openclaw/skills/` - AgentSkills-compatible instruction packs
- Tools: `openclaw/src/agents/tools/` - Tool implementations
- Channels: `openclaw/src/{telegram,discord,slack,signal,imessage,web}/` - Channel adapters
- Security: `openclaw/docs/gateway/security/` - Access control, sandboxing, audit
- Config: `openclaw/docs/gateway/configuration.md` - Configuration options

**Key Files Reviewed:**
- `openclaw/src/agents/pi-embedded-runner/run.ts` - Agent execution loop
- `openclaw/src/agents/pi-tools.ts` - Tool creation patterns
- `openclaw/docs/concepts/agent.md` - Agent concepts
- `openclaw/docs/concepts/agent-loop.md` - Execution flow
- `openclaw/docs/tools/skills.md` - Skill system

**Pattern:** Skills (teach) + Tools (execute) + Policies (constrain) + Channels (interface)

---

## 16. Appendix: PA Vision Scenarios

The following scenarios illustrate the **primary intent**: PAs help users plan and achieve daily goals (and longer-horizon objectives) so that employees can better achieve the goals set by the company.

### Scenario A: New Employee First Day

*Example: PA helps a new employee plan and achieve first-day goals (onboarding checklist).*

```
New Employee (WhatsApp):
"Hi, I'm John, starting today. What do I need to do?"

PA:
"Welcome to ABC Corp, John! 👋

Your onboarding checklist:
✓ Employee profile created
✓ Email account provisioned
□ Complete HR forms (5 forms, ~15 min)
□ IT equipment pickup (Building A, Room 101)
□ Department orientation (2pm today with Sarah)
□ Watch safety training video (20 min)

Let's start with HR forms?
[Start Now] [Do Later] [Help]"

John: "Start now"

PA:
"Form 1/5: Emergency Contact
I'll ask questions, you answer naturally.

Who should we contact in an emergency?"

John: "My wife, Jane Doe, +65 9123 4567"

PA: 
"✓ Added Jane Doe as emergency contact
  
Form 2/5: Tax Declaration
What's your tax file number?"

[Conversational form completion - no traditional UI needed]
```

### Scenario B: Manager's Daily Workflow

```
Manager's PA (Proactive - Morning Briefing via Telegram):
"☀️ Good morning Sarah!

Daily briefing:
📅 3 meetings today (Calendar synced)
📋 5 pending approvals (Leave: 2, Expense: 3)
📊 Department metrics:
   - Sales: 105% of weekly target ✓
   - Inventory: 2 items low stock ⚠️
   
🔔 Urgent items:
   - Client A invoice overdue 3 days
   - Project X milestone due today
   
[Approve Leave Requests] [View Low Stock] [All Clear]"

Sarah: "Approve leave requests"

PA:
"Leave Request 1/2:
  Employee: John Doe
  Dates: Feb 15-17 (3 days, Annual)
  Team capacity: OK (2 backups available)
  Previous leave: Last taken 2 months ago
  
[Approve] [Reject] [Defer]"

Sarah: "Approve"

PA:
"✓ Approved. John notified.

Leave Request 2/2:
  Employee: Alice Wang
  Dates: Feb 20-21 (2 days, Sick)
  Medical cert: Required (will submit today)
  
[Approve] [Reject] [Defer]"

Sarah: "Approve"

PA:
"✓ All leave requests processed.
  
Low stock alert:
  1. Widget A (45/50 units) - Auto PO suggested
  2. Part B (12/20 units) - Order in transit (arrives Feb 11)
  
[Auto-Order Widget A] [Monitor] [Dismiss]"
```

### Scenario C: Finance Close Process

```
Month-End Close (Multi-PA Orchestration):

Finance PA (Autonomous):
  Day 28: Start pre-close checklist
  
Finance PA → Department PAs:
"Month-end approaching. Please ensure:
  - All invoices entered by Day 30
  - Expense claims submitted
  - Revenue recognized
  
[Acknowledge] [Issues]"

Department PAs → Finance PA:
"Sales Dept: ✓ Ready
 Operations: ⚠️ 3 pending supplier invoices
 Marketing: ✓ Ready"

Finance PA → Finance Manager:
"Pre-close status:
  ✓ 2/3 departments ready
  ⚠️ Operations: 3 invoices pending
  
  Action needed?
  [Contact Operations] [Extend Deadline] [Proceed]"

Manager: "Contact operations"

Finance PA → Operations Manager's PA:
"Finance close in 2 days. Still need:
  - Invoice from Supplier X ($5,000)
  - Invoice from Supplier Y ($3,200)  
  - Invoice from Supplier Z ($1,800)
  
Can these be entered today?"

[Cross-PA coordination resolves blockers]

Day 31:
Finance PA:
  - Auto-generates close reports
  - Runs validation checks
  - Identifies discrepancies
  - Notifies relevant PAs to fix
  
Finance PA → CFO:
"Month-end close complete:
  Revenue: $1.2M (vs $1.1M budget) ✓
  Expenses: $850K (vs $900K budget) ✓
  Variance: 2 items (details attached)
  
  Reports ready:
  - P&L Statement
  - Balance Sheet  
  - Cash Flow
  
[Download] [Review Variances] [Approve]"
```

### Scenario D: Customer Service Excellence

```
Customer (Email to sales@company.com):
"Order ORD-456 hasn't arrived. Expected Feb 5, it's now Feb 9."

Customer Service PA:
  - Detects complaint
  - Auto-retrieves order data
  - Checks shipment tracking
  - Identifies issue (carrier delay)
  
CS PA → Logistics PA:
"Order ORD-456 delayed. Customer contacted. Status?"

Logistics PA → CS PA:
"Tracking shows: Customs hold, cleared yesterday
 New delivery: Feb 11
 Compensation: Free shipping next order (auto-applied)"

CS PA → Customer (Email):
"Dear [Customer],

We apologize for the delay with order ORD-456.

Status: Cleared customs Feb 8, out for delivery Feb 11
Cause: Customs inspection (standard procedure)
Compensation: Free shipping on your next order

Track: [link]

Is there anything else I can help with?

Best regards,
ABC Corp Customer Service"

[Human review optional before send, or auto-send for standard scenarios]
```

### Scenario E: Continuous Improvement

```
PA System (Analytics):
Notices across all company PAs:
  - 45% of leave requests are for single days
  - 80% are submitted < 2 days in advance
  - 60% are approved within 1 hour
  - Managers annoyed by "trivial" approvals

PA System → HR Director's PA:
"Process improvement suggestion:

Pattern: Most leave requests are last-minute, single days
Current: Requires manager approval (causes delays)

Proposed policy change:
  - Auto-approve single-day leave if:
    • Submitted at least 1 day advance
    • Team capacity OK (≥2 others present)
    • Employee has balance
    • Not during blackout dates
  - Manager just gets FYI notification
  
Estimated impact:
  - 60% of requests auto-approved
  - Manager time saved: ~2 hours/month
  - Employee approval wait: 1 hour → instant
  
[Pilot Test] [Review Data] [Dismiss]"

HR Director: "Pilot test"

PA System:
  - Implements new policy for 1 department
  - Monitors outcomes for 1 month
  - Reports results
  - Recommends company-wide rollout if successful
```

---

## 17. Competitive Advantage

**Why This Matters for BLB:**

**Traditional ERP:**
- "Learn our complex UI"
- "Fill out these 47 fields"
- "Click Save, then Submit, then Approve"
- "Desktop only, good luck on mobile"

**BLB with PA:**
- "Just tell me what you need"
- "I'll handle the forms"
- "Works on your phone, anywhere"
- "I know company policies, you don't have to"

**Result:** 
- **10x faster** task completion for common operations
- **90% reduction** in user training time
- **100% mobile-capable** (no VPN, no desktop required)
- **24/7 available** (PAs never sleep)

**This is BLB's AI-native differentiator.**

---

## 18. Next Steps (When Ready to Implement)

### Prerequisites
1. Core business modules exist (HR, Finance, Inventory, etc.)
2. Workflow system operational
3. Permission system implemented
4. Basic company setup complete

### Discovery Phase
1. Interview target users (what tasks are painful?)
2. Map 10-15 highest-value workflows
3. Design PA-to-PA communication protocol
4. Choose messaging channel(s) to support first

### MVP Scope
1. Web chat channel only (built into BLB)
2. 5-10 core tools (leave, expense, invoice, customer lookup, reports)
3. Basic skills (company policies, common procedures)
4. Simple approval workflows
5. Audit logging

**Timeline:** 4-6 weeks after core business modules stable

---

## 19. Related Documents

- `docs/brief.md` - BLB vision (AI-native architecture)
- `docs/architecture/user-employee-company.md` - User/employee model
- OpenClaw repository: `/home/kiat/repo/openclaw/` - Reference implementation

---

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 0.1 | 2026-02-09 | AI + Kiat | Initial research findings and vision |

---

**Status:** This document captures the vision and architectural research. When ready to implement, expand into full PRD with detailed requirements, technical specifications, and implementation plan.
