# Foundation Agent Guidelines

## ModuleConfigRegistry

`App\Base\Foundation\ModuleConfigRegistry` maps module names to Laravel config keys. Modules register in their ServiceProvider (`ModuleConfigRegistry::register('ModuleName', 'config_key')`). Used by test baseline seeding and any feature that needs config-by-module. See `app/Base/Database/AGENTS.md` (Test baseline) for usage.

## Service Provider Independence

`App\Base\Foundation\Providers\ProviderRegistry` auto-discovers providers under `app/Base/*/ServiceProvider.php` and `app/Modules/*/*/ServiceProvider.php`.

Because discovery is automatic, providers must be **independent by default**:
- Do not rely on another provider being manually registered in bootstrap.
- Prefer contracts and adapter bindings over direct module-to-module coupling.
- Provide safe local defaults in each module so it can boot in isolation.
- Treat provider ordering as a deterministic framework contract, not a hidden dependency.

When cross-module integration is needed, invert dependencies through contracts owned by the consuming module.
