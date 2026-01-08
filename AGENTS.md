# Belimbing Agent Guidelines

- License and copyright information are always the first lines of the file.
- No license or copyright information is needed in the `docs/` directory.

## Add SPDX license notice to source files

- **Canonical SPDX ID**: `AGPL-3.0-only`
- **Canonical copyright**: `Copyright (c) <Year> Ng Kiat Siong`

### Rules for Agents
1. **New Files**: Always add the header with the **current year** of creation, except md files in the docs directory.
2. **Existing Files**:
   - **If missing**: Add the header using the **current year**.
   - **If exists**: Update the year if the file is edited.

### Common comment styles

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
