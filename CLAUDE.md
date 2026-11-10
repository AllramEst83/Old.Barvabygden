# Barvabygden Community Website

A static website for Barvabygden (Barva village), a Swedish community between
Eskilstuna and Strängnäs. Live at [barvabygden.se](https://barvabygden.se).
Content and UI copy are in Swedish.

## Stack
- Plain HTML5 / CSS3 / vanilla JS (ES6+) — no framework, no build tools.
- Bootstrap 5.0.2 for layout and components.
- Google Fonts: Cookie, Crimson Text, EB Garamond.
- Deployment/versioning via PowerShell scripts in `scripts/`.

## Structure
- `index.html` — main landing page and entry point.
- `assets/css/` — stylesheets (`site.css` main, plus `lightbox.css`, `fallbacks.css`).
- `assets/js/` — scripts (`site.js` main, `event-countdown.js`, `lightbox.js`).
- `fragments/` — HTML fragments fetched at runtime via `fetch()` (`events.html`, `newsletter.html`).
- `pages/` — standalone HTML pages (flat; filenames kept), e.g. `kyrka.htm`, `skolan.htm`.
- `barva-bladet/<year>/` — digital archive of the Barva-Bladet newsletter.
- `assets/images/` — image collections grouped by topic (branding, hero, events, museum, kyrkan, folkdans, `shared/`, etc.).
- `scripts/` — deployment/versioning/maintenance scripts (`update-version.ps1`, `check-links.ps1`, manifest and redirect builders). Not deployed.
- `docs/` — project docs (`project-documentation.md`, `modernization-plan.md`, `deploy-runbook.md`). Not deployed.
- `migration-sources/barva-bladet/` — staging folder for pending newsletter issues (PDFs and images) waiting for the migration skill; currently empty/absent since issues 37–46 are migrated. Not deployed.
- `attic/` — quarantined unreferenced media and stale pages (see `attic/README.md`). Not deployed; nothing live may reference it.
- The repo root holds only `index.html`, `.htaccess`, `.gitignore` and the top-level docs (`README.md`, `CLAUDE.md`, `GEMINI.md`). `scripts/path-manifest.csv` records where every file moved from.
- `web/` contains a vendored WordPress export (legacy reference) — not part of
  the active static site, do not edit it.

## Working conventions
- Prefer Bootstrap utilities/components before writing custom CSS or JS.
- Vanilla HTML/CSS/JS only — do not introduce frameworks or build tooling unless explicitly requested.
- New standalone pages go in `pages/`, fragments in `fragments/`, images in a topic folder under `assets/images/`; don't add new loose top-level files.
- Run `scripts/check-links.ps1` after moving files or editing paths; it must stay at or under `scripts/link-baseline.txt`. After adding, moving or renaming images also run `scripts/check-images.ps1`.
- Keep diffs focused; when touching legacy files, improve only what you touch rather than rewriting broadly.
- Images: names are lowercase ASCII with hyphens, matching `^[a-z0-9]+(-[a-z0-9]+)*\.(webp|jpg|png|gif|ico)$` (å/ä become a, ö becomes o; no spaces, underscores or uppercase). Long edge at most 1920 px, file at most 500 KB. Photos are JPG, not PNG; PNG only for transparency or line art; GIFs stay as they are.
- Every `.jpg`/`.png` has a `.webp` sibling and is used as `<picture><source type="image/webp" srcset="x.webp"><img src="x.jpg" ...></picture>` (all `<img>` attributes stay on the inner `<img>`; CSS `url()` and lightbox `data-full` use the fallback). For new images: name them, run `scripts/optimize-images.ps1 -Folder <dir>`, `git add` them, run `scripts/apply-image-pictures.ps1`, then `scripts/check-images.ps1` (must pass).
- Renaming or moving an already published image breaks its old URL: record it as a row in `scripts/image-manifest.csv` and run `scripts/build-redirects.ps1` (image redirect block). Unreferenced images go to `attic/unreferenced-images/`, not into the live tree.
- Events: add new cards to `fragments/events.html` following the existing card structure.
- Newsletters: add new issues to `barva-bladet/<year>/` and update `fragments/newsletter.html`.
- Cache busting is handled by `scripts/update-version.ps1`, run from the repo root.

## Server access (read-only SSH MCP)
The live server (one.com, SFTP) can be inspected with the `ssh-readonly` MCP, which has only `list_dir`, `stat`
and `read_text` and cannot change anything. Deploys stay manual in WinSCP (`docs/deploy-runbook.md`).
- Web root on the server: `/customers/b/5/4/cypv0xbon/webroots/www` (the `/webroots/...` and `/www` paths are symlinks, which the MCP refuses).
- Never ask for, type or print credentials; the login lives in the user's own MCP registration (`C:\Users\kaywi\.claude.json`). Details, uses and troubleshooting: `.agents/skills/server-ssh-readonly/SKILL.md`. The server's code and setup are in `C:\Users\kaywi\dev\CodedByKay.SSH.MCP` (outside this repo).

## Local development
Open `index.html` directly, or serve the folder (e.g. `python -m http.server`, `npx serve`) for full `fetch()`-based fragment loading to work.

## Detailed guidance: `.agents/`
Deeper, topic-specific instructions live under `.agents/` (shared across coding
agents, formerly `.cursor/`):
- `.agents/rules/` — always-apply conventions (Bootstrap-first, layout patterns).
- `.agents/skills/` — task-triggered playbooks (asset placement, read-only server
  access over SSH, newsletter
  archive migration, migrating the next newsletter issue end-to-end, legacy
  page modernization, reader mode, Swedish a11y/content, vanilla JS patterns,
  site layout sections).
- `.agents/agents/` — specialized one-off agent instructions (e.g. fixing
  Swedish mojibake/encoding artifacts in migrated newsletter HTML).

Check there before large or unfamiliar tasks (newsletter migration, legacy
page modernization, encoding fixes) — those playbooks encode hard-won details
that aren't obvious from the code alone.
