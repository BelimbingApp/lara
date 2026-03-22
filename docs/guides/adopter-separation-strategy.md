# Adopter Separation Strategy (SBG)

**Document Type:** Implementation Guide
**Purpose:** Separate customer-specific development from upstream BLB improvements without slowing delivery
**Audience:** BLB framework team and SBG implementation team
**Last Updated:** 2026-03-22

---

## 1. Problem Statement

SBG needs a custom quality workflow module now, while BLB must keep evolving reusable framework capabilities without mixing customer-only behavior into core.

---

## 2. Design Goals

1. Keep SBG velocity high.
2. Keep BLB upstream contribution clean.
3. Minimize merge conflict risk during upstream sync.
4. Make promotion from SBG-specific implementation to reusable BLB capability explicit.

---

## 3. Recommended Model

Use one SBG fork with two branch lanes and multiple worktrees.

### 3.1 Repository Topology

```text
upstream: BelimbingApp/lara
origin:   sb-group/lara (fork)
```

### 3.2 Branch Lanes

Use branch naming to signal intent before code review:

- `blb/*` for reusable framework or module improvements intended for upstream PRs.
- `sbg/*` for customer-specific behavior that remains in the SBG fork.

Examples:

- `blb/workflow-transition-guard-hooks`
- `blb/quality-status-timeline-ui`
- `sbg/quality-workflow-module`
- `sbg/quality-workflow-capa-rules`

### 3.3 Worktrees

Use separate worktrees so both lanes move in parallel without stashing:

```bash
git remote add upstream https://github.com/belimbingapp/lara.git
git fetch --all

git worktree add ../blb-core -b blb/quality-core upstream/main
git worktree add ../sbg-quality -b sbg/quality-workflow origin/main
```

This model matches BLB's git-native direction in `docs/brief.md` and `docs/architecture/file-structure.md`.

---

## 4. File Placement Boundaries

Follow `docs/architecture/file-structure.md` and keep boundaries strict.

| Change Type | Placement | Notes |
|---|---|---|
| Framework infrastructure and cross-module abstractions | `app/Base/{Module}/...` | Upstream candidate (`blb/*`) |
| Reusable core business capability | `app/Modules/Core/{Module}/...` | Upstream candidate (`blb/*`) |
| Reusable domain module (not SBG specific) | `app/Modules/Business/{Module}/...` | Upstream candidate (`blb/*`) |
| Reusable BLB UI primitives and framework pages | `resources/core/...` | Upstream candidate (`blb/*`) |
| SBG-only customization | `extensions/custom/{extension}/...` | SBG fork only (`sbg/*`) |

### 4.1 Extension Naming and Database Rules

For SBG extension tables, use vendor-prefixed naming:

- Table pattern: `sbg_{module}_{entity}`
- Migration year prefix for extensions: `2026+`

Reference: `app/Base/Database/AGENTS.md` and `docs/guides/extensions/database-migrations.md`.

### 4.2 Menu Discovery Constraint

Menu discovery currently scans `extensions/*/*/Config/menu.php`.

Keep SBG extension layout compatible with that pattern, for example:

```text
extensions/custom/sbg-quality/
  Config/menu.php
```

Reference: `app/Base/Menu/Services/MenuDiscoveryService.php`.

### 4.3 UI Boundary in `resources/`

BLB uses a core/licensee split for presentation:

| UI Concern | BLB Core Path | SBG Path |
|---|---|---|
| Design tokens | `resources/core/css/tokens.css` | `resources/{licensee}/css/tokens.css` |
| Shared component styles | `resources/core/css/components.css` | `resources/{licensee}/css/components.css` |
| Blade components | `resources/core/views/components/` | `resources/{licensee}/views/components/` |
| Livewire page templates | `resources/core/views/livewire/` | `resources/{licensee}/views/livewire/` |

Rules for SBG:

1. If the UI change improves BLB-wide usability or component ergonomics, implement it in `resources/core/...` on `blb/*`.
2. If the UI change is SBG branding, terminology, layout preference, or workflow-specific behavior, implement it in `resources/{licensee}/...` on `sbg/*`.
3. Prefer token override and component override before copying full page templates.

References: `docs/architecture/ui-layout.md`, `docs/guides/theming.md`, and `resources/core/views/AGENTS.md`.

---

## 5. Decision Rubric: BLB vs SBG

Before coding a change, ask:

1. Is the naming domain-neutral (not SBG terminology)?
2. Is the behavior useful for at least one non-SBG adopter scenario?
3. Can this be exposed as a small, stable interface with hidden complexity?
4. Can SBG-specific policy be implemented as configuration, extension, or guard class outside core?

If all answers are yes, implement in `blb/*` lane.
If any answer is no, implement in `sbg/*` lane.

---

## 6. Delivery Flow for SBG Quality Workflow

### 6.1 Build Split

1. Implement reusable workflow primitives first in `blb/*` (only when clearly generic).
2. Implement SBG policy, terminology, and integrations in `sbg/*` extension.
3. Avoid mixed commits that touch both lanes in one PR.

### 6.2 Promotion Flow

When SBG code proves reusable:

1. Extract generic behavior from `sbg/*` into `blb/*` branch.
2. Rename domain terms to neutral language.
3. Add tests in BLB core.
4. Open upstream PR from `blb/*`.
5. Rebase SBG branch on updated fork main.

---

## 7. Pull Request Rules

1. `blb/*` PRs must not include `extensions/custom/sbg-*` paths.
2. `sbg/*` PRs should not modify BLB core unless patching for urgent blocker.
3. If an urgent SBG fix touches core, immediately follow with either:
   - a cleanup PR moving reusable part to `blb/*`, or
   - a revert-and-reimplement in extension space.

---

## 8. Local Runtime Isolation

Each worktree should use its own local runtime values:

1. unique `.env`
2. unique database name
3. unique app/frontend ports or hostnames

BLB start scripts are designed to avoid collisions for parallel local instances.

---

## 9. Suggested Initial Structure for SBG Quality

```text
extensions/sb-group/quality/
  Config/
    menu.php
    authz.php
    quality.php
  Database/
    Migrations/
      2026_03_22_000000_create_sbg_quality_work_items_table.php
      2026_03_22_000001_create_sbg_quality_status_history_table.php
    Seeders/
      QualityWorkflowSeeder.php
  Livewire/
    Quality/...
  Models/
    QualityWorkItem.php
  Routes/
    web.php
  ServiceProvider.php
```

Use this as the SBG-owned surface. Promote only neutral abstractions into BLB core modules.

---

## 10. Operational Checklist

1. Create `blb/*` and `sbg/*` branches at task start.
2. Keep feature intent explicit in branch names.
3. Review file paths before commit to enforce boundary.
4. Tag commits or PR titles with `[BLB]` or `[SBG]`.
5. Sync upstream regularly into SBG fork main.

---

## 11. Related References

- `docs/architecture/file-structure.md`
- `docs/development/agent-context.md`
- `docs/guides/extensions/config-overrides.md`
- `docs/guides/extensions/database-migrations.md`
- `app/Base/Database/AGENTS.md`
- `app/Base/Menu/Services/MenuDiscoveryService.php`
