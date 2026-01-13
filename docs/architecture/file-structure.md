# Belimbing File Structure Proposal

**Document Type:** Architecture Specification
**Purpose:** Define the file and directory structure for Belimbing framework
**Based On:** Project Brief v1.0.0, Ousterhout's "A Philosophy of Software Design"
**Last Updated:** 2025-12-01

---

## Overview

This document proposes a file structure that supports Belimbing's core principles:
- **Customizable Framework** - Deep extension system with hooks at every layer
- **Git-Native Workflow** - Development → Staging → Production branches
- **AI-Native Architecture** - AI integration points throughout
- **Quality-Obsessed** - Deep modules with simple interfaces
- **Extension Management** - Registry, validation, runtime safety

---

## Root Structure

```
belimbing/
├── .belimbing/              # Belimbing-specific configuration (git-ignored)
│   ├── branches/            # Git branch management (dev/staging/prod)
│   ├── extensions/          # Installed extensions (symlinks or copies)
│   ├── ai/                  # AI model cache, generated code templates
│   └── deployment/          # Deployment state, rollback points
│
├── app/                     # Core application code
│   ├── Base/                # Base framework layer (infrastructure, extension points)
│   ├── Modules/             # Business process modules
│   ├── Extensions/          # Extension integration layer
│   ├── Admin/               # Admin panel (git workflow, extensions, config)
│   ├── AI/                  # AI services and code generation
│   ├── Infrastructure/      # Infrastructure services (cache, queue, etc.)
│   └── Support/            # Helpers, utilities, base classes
│
├── bootstrap/               # Framework bootstrapping
│   ├── app.php              # Application configuration (middleware, exceptions)
│   └── providers.php        # Service provider registration
│
├── config/                  # Configuration files (minimal, mostly in DB)
│   ├── app.php             # Application bootstrap config
│   ├── database.php        # Database connection only
│   ├── redis.php           # Redis connection only
│   └── extensions.php      # Extension registry and discovery
│
├── database/                # Database schema and migrations
│   ├── migrations/         # Core and module migrations
│   ├── seeders/            # Core seeders
│   ├── schemas/            # Schema definitions (for extensions)
│   └── scripts/            # Database scripts (config updates, etc.)
│
├── extensions/             # Extension packages (vendor or custom)
│   ├── vendor/             # Third-party extensions
│   └── custom/             # Custom business-specific extensions
│
├── resources/               # Frontend resources
│   ├── views/              # Blade/Livewire templates
│   ├── js/                 # JavaScript (with WebAssembly support)
│   ├── css/                # Stylesheets
│   ├── wasm/               # WebAssembly modules (performance-critical)
│   └── ai/                 # AI-generated UI components
│
├── routes/                  # Route definitions
│   ├── web.php             # Web routes
│   ├── callback.php        # API/Callback routes
│   ├── console.php         # Artisan commands (replaces Console/Kernel)
│   └── extensions.php      # Extension routes (auto-loaded)
│
├── storage/                 # Storage (logs, cache, sessions)
│   ├── logs/               # Application logs
│   ├── cache/              # File cache
│   ├── sessions/           # Session files
│   ├── ai/                 # AI model cache, generated code
│   └── git/                # Git repository state
│
├── tests/                   # Test suite
│   ├── Unit/               # Unit tests
│   ├── Feature/            # Feature tests
│   ├── Integration/       # Integration tests
│   ├── Performance/        # Performance benchmarks
│   ├── AI/                 # AI-generated code tests
│   └── Pest.php            # Pest configuration
│
├── docs/                    # Documentation
│   ├── architecture/       # Architecture docs (this file)
│   ├── modules/            # Module documentation
│   ├── extensions/         # Extension development guide
│   └── deployment/         # Deployment guides
│
├── scripts/                 # Utility scripts
│   ├── install.sh          # Installation script
│   ├── deploy.sh           # Deployment script
│   ├── migrate.sh          # Migration runner
│   └── ai/                 # AI-related scripts
│
└── public/                  # Public web root
    ├── index.php           # Application entry point
    ├── assets/              # Compiled assets
    └── .well-known/         # Well-known paths (health checks, etc.)
```

---

## Core Application Structure (`app/`)

### `app/Base/` - Base Framework Layer

**Note on Fluidity:** While architecturally intended to be immutable to ensure stability for extensions, the Base layer is currently in an **active specialized development phase** (see `AGENTS.md`). Refactoring is expected.

The base layer provides framework infrastructure, extension points, and core abstractions.

```
app/Base/
├── Foundation/             # Base classes and interfaces
│   ├── Model.php           # Base model with extension hooks
│   ├── Controller.php      # Base controller
│   ├── Service.php         # Base service class
│   └── ExtensionPoint.php  # Base extension point interface
│
├── Events/                 # Core event system
│   ├── EventDispatcher.php
│   ├── EventListener.php
│   └── hooks/              # Hook registration system
│
├── Configuration/           # Configuration management
│   ├── ConfigManager.php    # Scope-based config with hierarchical fallback
│   ├── ScopeResolver.php    # Resolves config scope (company/department/etc.)
│   └── ConfigStore.php      # Database + Redis storage
│
├── Extension/               # Extension system core
│   ├── Registry.php         # Extension registry
│   ├── Validator.php        # Pre-installation validation
│   ├── Loader.php           # Runtime extension loader
│   ├── Sandbox.php          # Runtime safety (resource limits, isolation)
│   └── Contracts/           # Extension contracts/interfaces
│
├── Workflow/                 # Workflow engine (status-centric)
│   ├── WorkflowEngine.php
│   ├── StatusManager.php
│   ├── TransitionValidator.php
│   └── Hooks/               # Workflow hooks (before/after transitions)
│
├── Database/                # Database abstraction
│   ├── MigrationManager.php
│   ├── SchemaBuilder.php    # Dynamic schema extensions
│   └── QueryBuilder.php     # Extended query builder
│
└── Security/                  # Security foundation
    ├── AuthManager.php
    ├── PermissionManager.php
    └── AuditLogger.php
```

### `app/Modules/` - Business Process Modules

Each module is a self-contained business process (ERP, CRM, HR, etc.).

```
app/Modules/
├── Core/                    # Core framework modules
│   ├── User/                # User management module
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Controllers/
│   │   ├── Livewire/
│   │   ├── Events/
│   │   └── Hooks/           # Extension hooks for this module
│   │
│   ├── Workflow/            # Workflow management module
│   │   ├── Models/
│   │   │   └── StatusConfig.php
│   │   ├── Services/
│   │   ├── Controllers/
│   │   └── Livewire/
│   │
│   └── Admin/               # Admin panel module
│       ├── Git/             # Git workflow management
│       ├── Extensions/      # Extension management UI
│       ├── Configuration/   # Configuration UI
│       └── Deployment/      # CI/CD UI
│
└── Business/                # Business process modules (examples)
    ├── ERP/                 # ERP module
    ├── CRM/                 # CRM module
    ├── HR/                  # HR module
    └── Custom/              # Custom business processes
```

**Module Structure Template:**

```
app/Modules/{ModuleName}/
├── Models/                   # Eloquent models
├── Services/                 # Business logic services
├── Controllers/              # HTTP controllers
├── Livewire/                 # Livewire Volt components
├── Events/                   # Module-specific events
├── Listeners/                # Event listeners
├── Hooks/                    # Extension hooks for this module
├── Migrations/               # Module migrations
├── Seeders/                  # Module seeders
├── Routes/                   # Module routes
├── Views/                    # Module views
├── Config/                   # Module configuration schema
└── Tests/                    # Module tests
```

### Core vs Business: Directory Structure Rationale

The separation of modules into `Core/` and `Business/` directories is a **behavioral distinction**, not just organizational. This design aligns with Ousterhout's principles from "A Philosophy of Software Design."

#### Behavioral Differences

Core and Business modules differ in fundamental ways:

1. **Loading Order**
   - Core modules: Loaded first, always present
   - Business modules: Loaded after core, can be disabled

2. **Dependency Rules**
   - Core modules: Can depend on `app/Base/` base layer only
   - Business modules: Can depend on core modules + other business modules

3. **Lifecycle Management**
   - Core modules: Cannot be uninstalled, always active
   - Business modules: Can be installed/uninstalled via admin panel

4. **Extension Points**
   - Core modules: Provide foundational extension points
   - Business modules: Extend core modules, provide business-specific hooks

5. **Namespace Organization**
   - Core modules: `App\Modules\Core\User\`
   - Business modules: `App\Modules\Business\ERP\`

#### Why Directory Structure Over Metadata?

**1. Define Errors Out of Existence**

Directory structure prevents mistakes:
- Can't accidentally put business module in Core (filesystem enforces)
- Can't treat business module as core (path makes it explicit)
- Tooling can validate: "Core modules can't depend on Business modules"
- Autoloader can enforce loading order

**Example:**
```php
// With directory structure - compiler/static analysis can catch:
namespace App\Modules\Business\ERP;

use App\Modules\Core\User\Models\User; // ✅ Valid
use App\Modules\Core\Workflow\Models\StatusConfig; // ✅ Valid

// But this would be caught:
namespace App\Modules\Core\User;

use App\Modules\Business\ERP\Models\Order; // ❌ Error: Core can't depend on Business
```

**2. Reduce Cognitive Load**

Directory structure makes categorization immediately discoverable:
- Developer sees `app/Modules/Core/User/` → knows it's foundational
- Developer sees `app/Modules/Business/ERP/` → knows it's optional
- No need to read metadata files to understand structure
- File browser/navigator shows the distinction

**3. Enable Tooling**

Directory structure enables:
- **Module Loader**: `loadModules('app/Modules/Core')` then `loadModules('app/Modules/Business')`
- **Dependency Validator**: Can check paths to enforce rules
- **Code Generation**: AI can understand structure from paths
- **Static Analysis**: Tools can enforce architectural rules

**4. Deep Modules, Simple Interface**

The directory structure IS the interface:
- Simple: "Core modules go in Core/, Business modules go in Business/"
- Deep: Hides complexity of loading order, dependencies, lifecycle

**5. Strategic Programming**

Investing in directory structure upfront:
- Makes architecture explicit
- Prevents future mistakes
- Enables better tooling
- Reduces long-term complexity

**Note:** The directory structure provides all the behavioral distinction needed. If additional metadata (version, description, etc.) is required in the future, it can be added via `manifest.json` files similar to extensions, but this is not prescribed until there's a concrete need.

### `app/Extensions/` - Extension Integration Layer

```
app/Extensions/
├── Manager.php               # Extension lifecycle management
├── Loader.php                # Extension autoloader
├── Validator.php             # Extension validation
├── Registry.php              # Extension registry (from database)
├── Contracts/                # Extension contracts
│   ├── ModuleExtension.php
│   ├── ServiceExtension.php
│   └── HookExtension.php
└── Marketplace/              # Extension marketplace integration
    ├── Client.php
    └── Installer.php
```

### `app/Admin/` - Admin Panel

```
app/Admin/
├── Git/                     # Git workflow management
│   ├── BranchManager.php    # Dev/staging/prod branch management
│   ├── DeploymentManager.php # CI/CD pipeline
│   ├── RollbackManager.php  # Rollback capability
│   └── Livewire/
│       ├── Branches/
│       ├── Deployments/
│       └── Rollbacks/
│
├── Extensions/              # Extension management
│   ├── ExtensionManager.php
│   └── Livewire/
│       ├── Index.php        # Extension list
│       ├── Install.php      # Installation UI
│       ├── Configure.php    # Configuration UI
│       └── Uninstall.php
│
├── Configuration/           # Configuration management UI
│   ├── ConfigManager.php
│   └── Livewire/
│       ├── Scopes/
│       ├── Settings/
│       └── Hierarchy/
│
├── AI/                      # AI code generation UI
│   ├── CodeGenerator.php
│   └── Livewire/
│       ├── Generate/
│       ├── Templates/
│       └── Review/
│
└── System/                  # System management
    ├── Health/
    ├── Logs/
    ├── Cache/
    └── Backup/
```

### `app/AI/` - AI Services

```
app/AI/
├── Services/
│   ├── CodeGenerator.php    # AI code generation service
│   ├── ModelManager.php     # AI model management (local models)
│   ├── TemplateEngine.php   # Code template system
│   └── SafetyValidator.php  # AI code safety validation
│
├── Models/                  # AI model integrations
│   ├── LocalModel.php       # Local model interface
│   └── Adapters/            # Model adapters (llama.cpp, ONNX, etc.)
│
├── Templates/               # Code generation templates
│   ├── Module/
│   ├── Service/
│   ├── Controller/
│   └── Livewire/
│
├── Sandbox/                 # AI code sandboxing
│   ├── DevSandbox.php       # Development environment
│   ├── TestRunner.php       # Automated testing
│   └── ReviewSystem.php     # Code review workflow
│
└── Workflows/               # AI-assisted workflows
    ├── Development.php      # Dev → Staging → Prod workflow
    └── Review.php           # Code review workflow
```

### `app/Infrastructure/` - Infrastructure Services

```
app/Infrastructure/
├── Cache/                   # Caching layer
│   ├── CacheManager.php
│   └── Strategies/          # Memory, disk, distributed
│
├── Queue/                   # Queue system
│   ├── QueueManager.php
│   └── Workers/
│
├── Storage/                 # Storage abstraction
│   └── StorageManager.php
│
├── Monitoring/              # Health monitoring
│   ├── HealthChecker.php
│   └── MetricsCollector.php
│
└── Remote/                   # Remote management
    ├── TunnelManager.php     # Secure tunneling
    ├── Diagnostics.php       # Remote diagnostics
    └── UpdateManager.php     # Remote updates
```

### `app/Support/` - Support Classes

```
app/Support/
├── Helpers/                  # Helper functions
├── Traits/                   # Reusable traits
├── Exceptions/               # Custom exceptions
├── Validators/               # Custom validators
└── Utilities/                # Utility classes
```

---

## Extension Structure (`extensions/`)

```
extensions/
├── vendor/                    # Third-party extensions
│   └── {vendor-name}/
│       └── {extension-name}/
│           ├── composer.json
│           ├── manifest.json  # Extension manifest
│           ├── src/
│           ├── migrations/
│           ├── seeders/
│           ├── routes/
│           ├── views/
│           └── tests/
│
└── custom/                    # Custom business extensions
    └── {extension-name}/
        └── [same structure]
```

**Extension Manifest (`manifest.json`):**

```json
{
  "name": "vendor/extension-name",
  "version": "1.0.0",
  "description": "Extension description",
  "type": "module|service|hook",
  "dependencies": {
    "core": ">=1.0.0",
    "modules": ["user", "workflow"]
  },
  "hooks": [
    "user.created",
    "workflow.transition.before"
  ],
  "permissions": [
    "extension.permission.name"
  ],
  "config": {
    "schema": "config/schema.json"
  }
}
```

---

## Database Structure (`database/`)

```
database/
├── migrations/
│   ├── core/                # Core framework migrations
│   ├── modules/             # Module migrations
│   │   ├── user/
│   │   ├── workflow/
│   │   └── admin/
│   └── extensions/          # Extension migrations (auto-loaded)
│
├── seeders/
│   ├── core/                # Core seeders
│   └── modules/             # Module seeders
│
├── schemas/                 # Schema definitions
│   ├── core.json
│   └── modules/
│
└── scripts/                 # Database scripts
    ├── config-update.php    # Update configuration
    └── scope-migrate.php    # Migrate scope data
```

---

## Configuration System

### Scope-Based Configuration

Configuration is stored in database + Redis with hierarchical fallback:

```
Scope Hierarchy:
- Global (default)
  └── Company
      └── Department
          └── User
```

**Configuration Storage:**
- Database: Persistent configuration
- Redis: Runtime cache (fast lookup)
- Environment: Only bootstrap (DB, Redis connections)

---

## Git-Native Workflow Structure

```
.belimbing/branches/
├── development/             # Development branch
│   ├── .git/
│   └── state.json           # Branch state
│
├── staging/                 # Staging branch
│   ├── .git/
│   └── state.json
│
└── production/              # Production branch
    ├── .git/
    └── state.json

.belimbing/deployment/
├── history/                 # Deployment history
│   └── {timestamp}/
│       ├── commit.json
│       ├── migrations.json
│       └── rollback.sql
│
└── rollback/                # Rollback points
    └── {timestamp}/
```

---

## Testing Structure

```
tests/
├── Unit/                     # Unit tests
│   ├── Core/
│   ├── Modules/
│   └── Extensions/
│
├── Feature/                  # Feature tests
│   ├── Core/
│   ├── Modules/
│   └── Extensions/
│
├── Integration/              # Integration tests
│   ├── Modules/
│   └── Extensions/
│
├── Performance/              # Performance benchmarks
│   ├── Api/
│   ├── Database/
│   └── Frontend/
│
└── AI/                       # AI-generated code tests
    ├── Generated/
    └── Templates/
```

---

## Frontend Structure (`resources/`)

```
resources/
├── views/
│   ├── layouts/             # Layout templates
│   │   ├── app.blade.php
│   │   ├── admin.blade.php
│   │   └── auth.blade.php
│   │
│   ├── livewire/            # Livewire Volt components
│   │   ├── admin/
│   │   ├── modules/
│   │   └── extensions/
│   │
│   └── components/          # Blade components
│       ├── flux/            # Flux UI overrides
│       └── custom/          # Custom components
│
├── js/
│   ├── app.js               # Main application
│   ├── admin.js             # Admin panel
│   ├── modules/             # Module-specific JS
│   └── wasm/                # WebAssembly bindings
│
├── css/
│   ├── app.css              # Main stylesheet
│   └── admin.css            # Admin styles
│
└── wasm/                    # WebAssembly modules
    ├── performance/         # Performance-critical operations
    └── calculations/        # Complex calculations
```

---

## Key Design Principles

### 1. Deep Modules, Simple Interfaces

Each module/extension is self-contained with:
- Clear public API
- Hidden implementation complexity
- Extension hooks at strategic points

### 2. Extension Points

Hooks available at:
- **Events**: Before/after model operations
- **Middleware**: Request/response pipeline
- **Services**: Service method overrides
- **Workflows**: Workflow transition hooks
- **UI**: Component injection points
- **Database**: Schema extensions

### 3. Git-Native Workflow

- All code changes tracked in git
- Branch-based deployment (dev → staging → prod)
- Rollback capability at every level
- AI-generated code starts in dev, requires review

### 4. Single Source of Truth

- Configuration: Database + Redis
- Environment: Only bootstrap values
- Code: Git repository
- State: Database + Redis cache

### 5. Quality Assurance

- Tests at every level (unit, feature, integration, performance)
- AI-generated code must pass tests before promotion
- Code review required for production
- Performance benchmarks enforced

---

## Extension Development Workflow

1. **Development**: Create extension in `extensions/custom/`
2. **Validation**: Run pre-installation validation
3. **Testing**: Write and run tests
4. **Installation**: Install via admin panel
5. **Configuration**: Configure via admin panel
6. **Usage**: Extension hooks activated automatically

---

## Migration Path

This structure supports incremental adoption:
- Start with core framework
- Add modules as needed
- Install extensions for additional features
- Customize with business-specific extensions

---

**Document Status:** Proposal
**Next Steps:** Review and refine based on implementation experience
**Related Documents:** `docs/brief.md`, `docs/modules/*/`
