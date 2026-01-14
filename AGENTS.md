# Belimbing (BLB) Architect & Agent Guidelines

## 1. Project Context
Belimbing (BLB) is a Laravel application leveraging the TALL stack evolution:
- **Framework:** Laravel 12+
- **Frontend/Logic:** Livewire Volt + MaryUI
- **Testing:** Pest PHP
- **Linting:** Laravel Pint
- **Dependencies:** Use the latest available versions for all packages and dependencies.

## 2. Development Philosophy: Early & Fluid
**Context:** Initialization phase. No external users. No production deployment.

### Core Principles
- **Destructive Evolution:** Prioritize the best current design over backward compatibility. Drop tables, refactor schemas, and rewrite APIs freely; do not create migration paths for data.
- **Strategic Programming:** Invest in design quality to lower future development costs. Refactor immediately upon discovering design flaws (Zero Tolerance for Technical Debt).
- **Deep Modules:** Modules should provide powerful functionality through simple interfaces. Hide complexity; do not leak implementation details.
- **Design Iteratively:** You rarely get it right the first time. It is acceptable to "design it twice" to achieve a cleaner interface.

## 3. License Parameters
Add license and copyright headers to **newly created** files:
- License and copyright headers must be the **first lines** of the file.
- **Canonical SPDX ID**: `AGPL-3.0-only`
- **Canonical copyright**: `(c) Ng Kiat Siong <kiatsiong.ng@gmail.com>`

### Common comment styles
- **C-style / Slash-style** (PHP, JavaScript, TypeScript, Go, Rust, C, C++):
```javascript
// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>
```

- **Script-style / Hash-style** (Python, Shell, YAML, TOML, Makefile, Dockerfile):
```python
# SPDX-License-Identifier: AGPL-3.0-only
# (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>
```

- **Styling**:
  - CSS: `/* ... */`
  - HTML/XML/SVG: `<!-- ... -->`
  - SQL: `-- ...`

### Practical notes
- Do NOT add license headers to existing files.
- Keep the SPDX line as the very first legal line, after any required shebang.
- Match the comment style to the file type; do not introduce invalid syntax.
- If a file is generated, ensure the generator embeds or preserves the SPDX notice.

## ⚠️ Exception
Do NOT add license or copyright headers to files:
- in dot directories (e.g., `.env`, `.gitignore`, `.vscode`)
- in `vendor` directory
- published from vendor packages, even if they've been customized. These files are derived from vendor code and should retain their original licensing context.

### Examples of vendor-published files (via `php artisan vendor:publish`)
- config files: `config/*.php` from packages (e.g., `config/geonames.php`)
- migrations: `database/migrations/*` from packages (e.g., `database/migrations/2020_06_06_400000_create_cities_table.php`)
- models: `app/Models/*` from packages (e.g., `app/Models/Geo/City.php`)
- seeders: `database/seeders/*` from packages (e.g., `database/seeders/Geo/CitySeeder.php`)

## 4. Nested AGENTS.md Files
This project uses nested AGENTS.md files for specialized guidance. Agents should read the nearest AGENTS.md in the directory tree for context-specific instructions:
