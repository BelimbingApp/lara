# Documentation Organization Guide

This guide helps agents understand where to place different types of documentation files in the `docs/` directory.
 - When generating implementation plans, use the `docs/development/{module}/` directory.
 - When generating tutorials, use the `docs/tutorials/` directory.
 - and so on.

## Directory Structure

```
docs/
├── architecture/          # System architecture and design documents
├── development/           # Development-related documentation
│   └── company/          # Module-specific development docs
├── extensions/            # Extension and plugin documentation
├── installation/          # Installation and setup guides
├── modules/               # Module documentation (overviews, APIs, usage)
│   └── company/          # Module-specific documentation
├── todo/                  # TODO lists and planning documents
└── tutorials/             # Tutorials and how-to guides
```
