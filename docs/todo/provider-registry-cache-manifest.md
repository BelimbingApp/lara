# Provider Registry Cache Manifest

## Goal
Avoid scanning `app/Modules/*/*/ServiceProvider.php` on every request by caching discovered providers.

## TODO
- [ ] Add `ProviderRegistry::resolveCached()` that reads a generated manifest from `bootstrap/cache/module-providers.php`.
- [ ] Add an Artisan command to build the manifest (for example: `providers:cache-modules`).
- [ ] Add an Artisan command to clear the manifest (for example: `providers:clear-modules`).
- [ ] Ensure bootstrap falls back to runtime discovery when cache manifest is missing.
- [ ] Add tests covering cache hit, cache miss fallback, and invalid manifest entries.
