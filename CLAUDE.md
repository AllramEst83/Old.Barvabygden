# Barvabygden Community Website

A static website for Barvabygden (Barva village), a Swedish community between
Eskilstuna and Strängnäs. Live at [barvabygden.se](https://barvabygden.se).
Content and UI copy are in Swedish.

## Stack
- Plain HTML5 / CSS3 / vanilla JS (ES6+) — no framework, no build tools.
- Bootstrap 5.0.2 for layout and components.
- Google Fonts: Cookie, Crimson Text, EB Garamond.
- Deployment/versioning via PowerShell scripts in `assets/powershell/`.

## Structure
- `index.html` — main landing page and entry point.
- `assets/css/` — stylesheets (`site.css` main, plus `lightbox.css`, `fallbacks.css`).
- `assets/js/` — scripts (`site.js` main, `event-countdown.js`, `lightbox.js`).
- `assets/pages/` — HTML fragments fetched at runtime via `fetch()` (e.g. `events.html`, `newsletter.html`).
- `assets/barva-news/<year>/` — digital archive of the Barva-Bladet newsletter.
- `assets/images/` — organized image collections (branding, hero, events, museum, etc.).
- `assets/powershell/` — deployment/versioning scripts.
- `assets/docs/PROJECT_DOCUMENTATION.md` — deeper project documentation.
- Repo root also holds a large collection of legacy historical photos/PDFs
  (`1926.jpg`, `bb-*.htm`, etc.) — these are intentionally left in place; don't
  reorganize them unless asked.
- `web/` contains a vendored WordPress export (legacy reference) — not part of
  the active static site, do not edit it.

## Working conventions
- Prefer Bootstrap utilities/components before writing custom CSS or JS.
- Vanilla HTML/CSS/JS only — do not introduce frameworks or build tooling unless explicitly requested.
- Put new non-root assets under `assets/`; don't add new loose top-level files.
- Keep diffs focused; when touching legacy files, improve only what you touch rather than rewriting broadly.
- Events: add new cards to `assets/pages/events.html` following the existing card structure.
- Newsletters: add new issues to `assets/barva-news/<year>/` and update `assets/pages/newsletter.html`.
- Cache busting is handled by `assets/powershell/update-version.ps1`, run from the repo root.

## Local development
Open `index.html` directly, or serve the folder (e.g. `python -m http.server`, `npx serve`) for full `fetch()`-based fragment loading to work.

## Detailed guidance: `.agents/`
Deeper, topic-specific instructions live under `.agents/` (shared across coding
agents, formerly `.cursor/`):
- `.agents/rules/` — always-apply conventions (Bootstrap-first, layout patterns).
- `.agents/skills/` — task-triggered playbooks (asset placement, newsletter
  archive migration, migrating the next newsletter issue end-to-end, legacy
  page modernization, reader mode, Swedish a11y/content, vanilla JS patterns,
  site layout sections).
- `.agents/agents/` — specialized one-off agent instructions (e.g. fixing
  Swedish mojibake/encoding artifacts in migrated newsletter HTML).

Check there before large or unfamiliar tasks (newsletter migration, legacy
page modernization, encoding fixes) — those playbooks encode hard-won details
that aren't obvious from the code alone.
