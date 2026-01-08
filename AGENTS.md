# Belimbing Agent Guidelines

- License and copyright information are always the first lines of the file.
- Use regular `id()` method for primary keys, not `uuid()`.

## Add SPDX license notice to source files

- **Canonical SPDX ID**: `AGPL-3.0-only`
- **Canonical copyright**: `Copyright (c) <Year> Ng Kiat Siong`

### Rules for Agents
1. **New Files**: Always add the header with the **current year** of creation.
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

## Database ID Standards

- **Primary Keys**: Use `id()` method which creates `UNSIGNED BIGINT` (auto-incrementing primary key)
- **Foreign Keys**: Use `foreignId()` for foreign key columns, which also creates `UNSIGNED BIGINT`
- **Rationale**: This is Laravel's standard convention and ensures type consistency between primary keys and foreign keys

### Example Migration

```php
Schema::create('companies', function (Blueprint $table) {
    $table->id();  // Creates UNSIGNED BIGINT auto-incrementing primary key
    $table->foreignId('parent_id')->nullable()->constrained('companies');
    $table->string('name');
    $table->timestamps();
});
```

**Note**: Laravel's `id()` method is an alias for `bigIncrements()` and creates an auto-incrementing UNSIGNED BIGINT primary key. The `foreignId()` method also creates UNSIGNED BIGINT columns, ensuring type compatibility. Do NOT use `uuid()` for primary keys unless explicitly required.
