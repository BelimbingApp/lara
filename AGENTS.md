## Belimbing Agent Guidelines

- **License**: AGPL-3.0-only
- **Copyright**: © 2025-2026 Ng Kiat Siong
- **Primary references**: `LICENSE`, `NOTICE`, and `CLA.md` at the repository root

### Add SPDX license notice to source files

- **Canonical SPDX ID**: `AGPL-3.0-only`
- **Canonical copyright**: `Copyright (c) <Year> Ng Kiat Siong`

#### Rules for Agents
1. **New Files**: Always add the header with the **current year** of creation.
2. **Existing Files**:
   - **Do not update** the year if a header already exists, even if editing the file.
   - **If missing**: Add the header using the **current year**.

#### Common comment styles

- **C-style / Slash-style** (PHP, JavaScript, TypeScript, Go, Rust, C, C++):
```javascript
// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2026 Ng Kiat Siong
```

- **Script-style / Hash-style** (Python, Shell, YAML, TOML, Makefile, Dockerfile):
```python
# SPDX-License-Identifier: AGPL-3.0-only
# Copyright (c) 2026 Ng Kiat Siong
```

- **Markup & Styling**:
  - CSS: `/* ... */`
  - HTML/XML/SVG: `<!-- ... -->`
  - SQL: `-- ...`

### Practical notes
- Keep the SPDX line as the very first legal line, after any required shebang.
- Match the comment style to the file type; do not introduce invalid syntax.
- If a file is generated, ensure the generator embeds or preserves the SPDX notice.
