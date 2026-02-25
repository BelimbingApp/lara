# Foundation Agent Guidelines

## Service Provider Independence

`App\Base\Foundation\Providers\ProviderRegistry` auto-discovers providers under `app/Base/*/ServiceProvider.php` and `app/Modules/*/*/ServiceProvider.php`.

Because discovery is automatic, providers must be **independent by default**:
- Do not rely on another provider being manually registered in bootstrap.
- Prefer contracts and adapter bindings over direct module-to-module coupling.
- Provide safe local defaults in each module so it can boot in isolation.
- Treat provider ordering as a deterministic framework contract, not a hidden dependency.

When cross-module integration is needed, invert dependencies through contracts owned by the consuming module.
