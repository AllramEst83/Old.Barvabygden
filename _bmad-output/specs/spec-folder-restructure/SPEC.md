---
id: SPEC-folder-restructure
companions:
  - target-layout.md
  - migration-rules.md
sources: []
---

> **Canonical contract.** This SPEC and the files in `companions:` are the complete, preservation-validated contract for what to build, test, and validate.

# Repository Folder Restructure

## Why

Pain. The repo root holds 704 loose files: 534 jpg, 72 gif, 54 htm, 22 pdf and assorted junk. Only 224 of the 642 root media files are referenced by any page. Six legacy folders sit next to an `assets/` that mixes site code, content, tooling and docs. Maintainers and coding agents cannot tell what is live, what is legacy and what is dead. Every new page, newsletter migration or cleanup pays that cost. The goal is one home per file and one purpose per folder, so that legacy-page modernization and newsletter migration work on a predictable structure.

## Capabilities

- **CAP-1**
  - **intent:** The repo root contains only entry, config and top-level doc files.
  - **success:** The root file listing matches the allowlist in `target-layout.md` exactly.
- **CAP-2**
  - **intent:** Each top-level folder has a single purpose (site code, page content, fragments, newsletter archive, docs, scripts, quarantine).
  - **success:** The tree matches `target-layout.md`; every tracked file outside `web/` and `.ilosoft/` has exactly one home (except the copies allowed for pending newsletter sources); and every image used by a live page sits in a topic folder under `assets/images/`.
- **CAP-3**
  - **intent:** Every internal reference resolves after the move (href, src, CSS `url()`, `fetch()`, script/style paths).
  - **success:** The link-check script reports zero broken local references beyond the pre-move baseline.
- **CAP-4**
  - **intent:** A machine-readable old-path → new-path manifest records every moved file.
  - **success:** The manifest row count equals the number of moved files, and the manifest alone drives link rewriting, redirects and the cutover delete list.
- **CAP-5**
  - **intent:** Unreferenced media and legacy junk are quarantined, not deleted.
  - **success:** The quarantine is listed in the manifest, and no deployed page references anything inside it.
- **CAP-6**
  - **intent:** Docs and agent instructions describe the new layout.
  - **success:** A grep of CLAUDE.md, GEMINI.md, README.md, `docs/` and `.agents/` (excluding `bmad-*`) finds no stale pre-move paths.
- **CAP-7**
  - **intent:** Existing workflows keep working: newsletter-issue migration, adding an event card, `update-version.ps1` cache busting, and local serve.
  - **success:** Each workflow is run once after the move and completes without code changes beyond path updates.
- **CAP-8**
  - **intent:** Old public page and newsletter-PDF URLs keep working after the move.
  - **success:** On the live site, every old page/PDF path in the manifest returns a 301 to its new path, and the new path returns 200.
- **CAP-9**
  - **intent:** The cutover deploy to one.com is repeatable from a written runbook.
  - **success:** The runbook lists the upload set, the WinSCP exclusion mask and the old server copies to delete, and one cutover performed from it passes the CAP-8 check.
- **CAP-10**
  - **intent:** The in-progress Barva-Bladet migration (issues 37–46) continues against the new locations.
  - **success:** After the restructure, issue 37 migrates end to end with no manual path fixes. The skill reads it from `migration-sources/barva-bladet/`, writes `barva-bladet/<year>/`, updates `fragments/newsletter.html`, deletes the consumed sources and repoints that issue's redirect.

## Constraints

- Move files with `git mv`, in phased commits on `feature/folder-restructure`, so history is kept and each phase can be reverted on its own.
- No frameworks or build tooling. Manifest, rewrite, redirect and link-check scripts are PowerShell scripts in `scripts/`.
- The document root stays at the repo root. `index.html` and `.htaccess` stay at the root.
- `web/` and `.ilosoft/` are untouched: no moves, edits or link rewrites.
- `pages/` stays flat.
- Moved files keep their exact filenames. Renaming is deferred to the follow-up.
- Images are grouped by topic using the page-topic map in `migration-rules.md`. They are not grouped by file type or by date.
- Deploys are manual: WinSCP over SSH to one.com, comparing local against remote and checking or unchecking each file to upload. Old files stay on the server after a move, so the cutover must handle old server copies explicitly.
- Non-deployed folders (`docs/`, `scripts/`, `attic/`, `migration-sources/`, `_bmad*/`, `.agents/`, `.claude/`, `web/`) are kept off the live site twice: a WinSCP exclusion mask stops them being uploaded, and a `.htaccess` 404 rule blocks them if they get uploaded anyway.

## Non-goals

- Modernizing legacy page content or markup (covered by `docs/modernization-plan.md`).
- Image improvements. These are recorded as a follow-up for after this restructure: normalize image names, remove duplicate images, compress/resize, convert to WebP.
- Migrating newsletter issues 37–46. That stays with the migration skill; this restructure only relocates its inputs.
- Redesigning URLs beyond what the moves require.
- Redirects for media files. Old root media stay on the server, so external image links keep working.
- Cleaning up the server root, or deciding the final fate of individual quarantined files.
- Anything inside `web/` or `.ilosoft/`.

## Success signal

On `feature/folder-restructure`, the root lists only allowlisted files and the link check shows no new broken references. Served locally, `index.html`, every page it links, both fetched fragments and a sample of newsletter issues render with styles and images intact. After cutover, `barvabygden.se/kyrka.htm` lands on `/pages/kyrka.htm`.

## Assumptions

- Root `bb-31`, `bb-32`, `bb-33`, `bb-48.html` and `bb-49x` are leftovers of issues already migrated, so they go to `attic/pages/`. Root PDFs for migrated issues are duplicates and go to `attic/pdfs/`.
- `old_index.htm`, `öld_index.html`, `barvaf (2).htm` and `bfa_temp.htm` are stale copies and go to `attic/`.
- `Thumbs.db` and `WS_FTP.LOG` are removed from tracking and added to `.gitignore`.
- one.com honours `.htaccess` `Redirect` / `RedirectMatch` (mod_alias); the existing HTTPS rewrite rule suggests it does.
