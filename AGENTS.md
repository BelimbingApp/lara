# Belimbing (BLB) Architect & Agent Guidelines

## 1. Project Context
Belimbing (BLB) is an enterprise-grade **framework** built on Laravel, leveraging the TALL stack evolution:
- **Framework:** Laravel 12+
- **Frontend/Logic:** Livewire Volt + MaryUI
- **Testing:** Pest PHP
- **Linting:** Laravel Pint
- **Dependencies:** Use the latest available versions for all packages and dependencies.

BLB is a higher-order framework layered on top of Laravel. It preserves compatibility where practical but will intentionally diverge when necessary to uphold BLB’s architectural principles. BLB extends and adapts Laravel internals accordingly, guided by Ousterhout’s design tenets: deep modules, simple interfaces, and clear boundaries.

Think of Laravel as the Level 0 foundation and BLB as a Level 1 framework built atop it — cohesive, opinionated, and extensible. BLB is not a mere Laravel application; it has no qualms about customizing Laravel to align with its architectural principles.

## 2. Development Philosophy: Early & Fluid
**Context:** Initialization phase. No external users. No production deployment.

### Core Principles
- **Destructive Evolution:** Prioritize the best current design over backward compatibility. Drop tables, refactor schemas, and rewrite APIs freely; do not create migration paths for data.
- **Strategic Programming:** Invest in design quality to lower future development costs. Refactor immediately upon discovering design flaws (Zero Tolerance for Technical Debt).
- **Deep Modules:** Modules should provide powerful functionality through simple interfaces. Hide complexity; do not leak implementation details.
- **Design Iteratively:** You rarely get it right the first time. It is acceptable to "design it twice" to achieve a cleaner interface.

## 3. Nested AGENTS.md Files
This project uses nested AGENTS.md files for specialized guidance. Agents should read the nearest AGENTS.md in the directory tree for context-specific instructions:
