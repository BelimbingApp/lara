## Belimbing: Agent and Contributor Guidelines

- **License**: AGPL-3.0-only
- **Copyright**: © 2025 Ng Kiat Siong
- **Primary references**: `LICENSE`, `NOTICE`, and `CLA.md` at the repository root

### Add SPDX license notice to every new source file
Always add an SPDX identifier and copyright line at the top of new
files. Use the appropriate comment syntax for the language.

- **Canonical SPDX ID**: `AGPL-3.0-only`
- **Canonical copyright**: `Copyright (c) 2025 Ng Kiat Siong`

#### Examples by language

- JavaScript / TypeScript:
```javascript
// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2025 Ng Kiat Siong
```

- PHP:
```php
<?php
// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2025 Ng Kiat Siong
```

- Python / Shell / YAML / TOML / INI:
```python
# SPDX-License-Identifier: AGPL-3.0-only
# Copyright (c) 2025 Ng Kiat Siong
```

- Go / C / C++ / Rust:
```c
// SPDX-License-Identifier: AGPL-3.0-only
// Copyright (c) 2025 Ng Kiat Siong
```

- CSS:
```css
/* SPDX-License-Identifier: AGPL-3.0-only */
/* Copyright (c) 2025 Ng Kiat Siong */
```

- HTML / XML / SVG:
```html
<!-- SPDX-License-Identifier: AGPL-3.0-only -->
<!-- Copyright (c) 2025 Ng Kiat Siong -->
```

- SQL:
```sql
-- SPDX-License-Identifier: AGPL-3.0-only
-- Copyright (c) 2025 Ng Kiat Siong
```

- Makefile:
```make
# SPDX-License-Identifier: AGPL-3.0-only
# Copyright (c) 2025 Ng Kiat Siong
```

- Dockerfile:
```dockerfile
# SPDX-License-Identifier: AGPL-3.0-only
# Copyright (c) 2025 Ng Kiat Siong
```

### Practical notes
- Keep the SPDX line as the very first legal line, after any required shebang.
- Match the comment style to the file type; do not introduce invalid syntax.
- If a file is generated, ensure the generator embeds or preserves the SPDX notice.

### Contributor License Agreement (CLA)
All contributors must agree to the terms in `CLA.md`.

- Contributions are only accepted from authors who agree to the CLA.
- If contributing on behalf of an employer, ensure you are authorized to do so.

### Third-party code
- If you include third-party code, preserve original notices and add a
  reference in a `THIRD_PARTY_NOTICES.md` or a per-component NOTICE as appropriate.

### Questions
- For licensing questions, refer to `LICENSE` and `NOTICE`.
- If unsure, open an issue and tag it with `licensing`.
