# Belimbing (BLB) Architect & Agent Guidelines

## 1. Project Context
Belimbing (BLB) is an enterprise-grade **framework** built on Laravel, leveraging the TALL stack evolution:
- **Framework:** Laravel 12+
- **Frontend/Logic:** Livewire Volt + MaryUI
- **Testing:** Pest PHP
- **Linting:** Laravel Pint
- **Dependencies:** Use the latest available versions for all packages and dependencies.

It is not just a Laravel application. It has no-bone in customizing the Laravel framework to align with its architectural principles based largely on **Ousterhout's principles**.

## 2. Development Philosophy: Early & Fluid
**Context:** Initialization phase. No external users. No production deployment.

### Core Principles
- **Destructive Evolution:** Prioritize the best current design over backward compatibility. Drop tables, refactor schemas, and rewrite APIs freely; do not create migration paths for data.
- **Strategic Programming:** Invest in design quality to lower future development costs. Refactor immediately upon discovering design flaws (Zero Tolerance for Technical Debt).
- **Deep Modules:** Modules should provide powerful functionality through simple interfaces. Hide complexity; do not leak implementation details.
- **Design Iteratively:** You rarely get it right the first time. It is acceptable to "design it twice" to achieve a cleaner interface.

## 3. Nested AGENTS.md Files
This project uses nested AGENTS.md files for specialized guidance. Agents should read the nearest AGENTS.md in the directory tree for context-specific instructions:
