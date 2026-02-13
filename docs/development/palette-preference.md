# User palette preference

Users can choose the **main palette** (e.g. Arid Camouflage vs Neutral) as a personal preference. This doc describes how to implement it so the feature works correctly now and when adding new palettes later.

## Goal

- User can select a main palette (e.g. Arid Camouflage, Neutral, or future options).
- Preference is persisted (logged-in: DB; guest: localStorage).
- The same semantic tokens are used everywhere; only their **values** change per palette.
- Palette choice is independent of light/dark mode (e.g. Arid + dark, Neutral + light).

## How it works

1. **One set of semantic tokens** — All UI uses the same names: `surface-page`, `surface-sidebar`, `surface-bar`, `accent`, `accent-hover`, `accent-on`, etc. (see `resources/css/app.css`).
2. **Palette = different values for the same tokens** — Each palette (e.g. `arid`, `neutral`) defines the **same** CSS custom properties with **different** primitive values. No class names in Blade change.
3. **Root attribute** — Apply the choice as a attribute on the root element (e.g. `data-palette="arid"` or `data-palette="neutral"` on `<html>`). CSS rules scoped to `[data-palette="…"]` override the default semantic token values.

## CSS structure

- **Default (e.g. Arid)** — Define semantic tokens in `@theme` (or `:root`) as today. This is the default palette.
- **Other palettes** — For each additional palette, add a block that redefines **only** the palette-dependent tokens:

```css
/* Default: Arid Camouflage (in @theme / :root) — values from theme primitives */
--color-surface-page: …;
--color-surface-sidebar: …;
--color-surface-bar: …;
--color-accent: …;
--color-accent-hover: …;
/* ... */

/* User preference: Neutral */
[data-palette="neutral"] {
  --color-surface-page: var(--color-zinc-100);
  --color-surface-sidebar: var(--color-zinc-200);
  --color-surface-bar: var(--color-zinc-200);
  --color-accent: var(--color-zinc-700);
  --color-accent-hover: var(--color-zinc-800);
  /* Only tokens that should change with palette; leave ink, muted, border-default, surface-card, etc. unless the palette defines them too */
}
```

- **Dark mode** — Keep using `.dark { ... }` to override semantic tokens for contrast. Palette and dark are independent: `.dark` can coexist with `[data-palette="neutral"]`; define `.dark` and, if needed, `[data-palette="neutral"].dark` overrides for any token that must differ when both apply.

## Which tokens are palette-dependent

- **Typically vary per palette:** `surface-page`, `surface-sidebar`, `surface-bar`, `accent`, `accent-hover`, `accent-on`. These define the “main” look (chrome + primary actions).
- **Typically fixed or shared:** `ink`, `muted`, `link`, `surface-card`, `surface-subtle`, `border-default`, `border-input` — unless a palette is designed to change the whole UI (e.g. full neutral theme). Document in `app.css` which tokens each palette overrides.

## Storing the preference

| User type   | Storage        | How to apply                                                                 |
|------------|----------------|-------------------------------------------------------------------------------|
| Logged-in  | User table     | Add column e.g. `preferred_palette` (`string`, nullable, default `'arid'`). On each request, output `data-palette="{{ auth()->user()->preferred_palette ?? 'arid' }}"` on `<html>`. Settings page: let user choose; update via Livewire and refresh or re-apply class. |
| Guest      | localStorage   | Key e.g. `blb.palette`. On load, a small script reads it and sets `document.documentElement.dataset.palette`. When guest changes palette in UI, write to localStorage and update `document.documentElement.dataset.palette`. |

Ensure the root element has `data-palette` set before first paint when possible (e.g. server-rendered for logged-in; script in `<head>` or early for guest) to avoid flash of wrong palette.

## Implementation checklist

- [ ] **Migration:** Add `preferred_palette` to users table (e.g. `arid`, `neutral`); default `arid`.
- [ ] **Root attribute:** In the main layout, set `<html data-palette="{{ auth()->user()->preferred_palette ?? 'arid' }}" ...>` (and ensure dark class is also set if using dark mode preference).
- [ ] **CSS:** In `resources/css/app.css`, add `[data-palette="neutral"] { ... }` (and any other palettes) with overrides for the palette-dependent semantic tokens. Add a short comment in `app.css` that palette options are documented in `docs/development/palette-preference.md`.
- [ ] **Settings UI:** Add a “Palette” or “Main theme” control (e.g. in appearance or profile settings). Options: Arid Camouflage, Neutral. On save, persist `preferred_palette` for logged-in users; for guest, persist to localStorage and set `data-palette` on `<html>`.
- [ ] **Guest script:** If guests can change palette, add a tiny script that reads `localStorage.getItem('blb.palette')` and sets `document.documentElement.dataset.palette` on load, and updates both when the user changes the option.

## Adding a new palette later

1. Define the new palette’s primitive colors in `app.css` if needed (or reuse existing primitives).
2. Add a new block `[data-palette="new-name"] { ... }` that overrides the same semantic tokens (surface-page, surface-sidebar, surface-bar, accent, accent-hover, accent-on, and any others that should change).
3. Add the option to the settings UI and to the list of allowed values (e.g. in a config or enum).
4. Update this doc with the new palette name and any special notes (e.g. “Ocean uses blue primitives; define `--color-ocean-*` in theme”).

## References

- Semantic tokens and strategy: `resources/css/app.css` (comments), `.cursor/rules/ui-architect.mdc` (Semantic color strategy).
- Dark mode: same `app.css`, `.dark` block; ensure palette and dark can combine without conflicts.
