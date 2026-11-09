---
id: SPEC-image-cleanup
companions:
  - image-inventory.md
  - image-rules.md
sources: []
---

> **Canonical contract.** This SPEC and the files in `companions:` are the complete, preservation-validated contract for what to build, test, and validate. Source documents listed in frontmatter are for traceability — consult them only if you need narrative rationale or prose color this contract intentionally omits.

# Image Cleanup

## Why

Pain, left over from the folder restructure (its Non-goals deferred this). The live images weigh 120 MB across 387 files. Fifteen files over 2000 px carry 94 MB of that, mostly PNGs of 8–16 MB. Names mix spaces, non-ASCII and uppercase, and 36 groups of byte-identical files exist. Visitors on mobile pay for the weight, and maintainers pay for the inconsistency whenever they add or reference an image.

## Capabilities

- **CAP-1**
  - **intent:** Every live image has a normalized filename.
  - **success:** All filenames under `assets/images/` and `barva-bladet/**/images/` match the pattern in `image-rules.md`; none contain spaces, non-ASCII or uppercase.
- **CAP-2**
  - **intent:** Identical live images exist once.
  - **success:** A hash scan of `assets/images/` finds no byte-identical pair (archive per-issue copies excepted, see Constraints); every former duplicate reference points at the kept file.
- **CAP-3**
  - **intent:** No live image is larger than the web needs.
  - **success:** No live image, fallback files included, exceeds the dimension and size caps in `image-rules.md`; total live image weight drops from about 120 MB to the target in the Success signal.
- **CAP-4**
  - **intent:** Browsers that support WebP get WebP; others get a fallback in the original format.
  - **success:** Every live `.jpg`/`.jpeg`/`.png` covered by the format policy in `image-rules.md` has a `.webp` sibling and a resized fallback, and each `<img>` using it in `index.html`, `pages/`, `fragments/` and `barva-bladet/` is a `<picture>` with the WebP source and the fallback `<img>`; a browser without `<picture>` support still shows the fallback; all images render.
- **CAP-5**
  - **intent:** Every change is traceable and no reference breaks.
  - **success:** `scripts/check-links.ps1` reports no new broken references after each phase, and the image manifest has one row per renamed, merged, resized or converted file (old path, new path, action).
- **CAP-6**
  - **intent:** The changes reach one.com without breaking the live site.
  - **success:** After an upload following the runbook, `scripts/check-live.ps1` passes, including every image redirect (old URL answers 301, new URL answers 200); the home page, one page per topic and one archive issue show all images.
- **CAP-7**
  - **intent:** New images follow the same rules.
  - **success:** CLAUDE.md, GEMINI.md and the assets and newsletter-migration skills state the naming, size, format and `<picture>` rules; a newly migrated issue's images pass the same checks.
- **CAP-8**
  - **intent:** Images no page uses leave the deployed tree.
  - **success:** After a stronger reference scan confirms them unreferenced, the 32 listed in `image-inventory.md` sit under `attic/unreferenced-images/<original path>`, and `assets/images/` and `barva-bladet/` hold no unreferenced image.

## Constraints

- The site stays plain HTML/CSS/JS with no build tooling. Image processing is a one-off local step driven by PowerShell scripts in `scripts/`; any encoder is a dev-machine tool, never part of the site.
- Scope is live images only: `assets/images/` and `barva-bladet/`. `attic/` only receives the CAP-8 files; `migration-sources/`, `web/` and `.ilosoft/` are untouched. Pending newsletter issues follow the rules when the migration skill processes them.
- Reuse the restructure method: `git mv`, one commit per phase on a feature branch, link check at or under its baseline (128), manifest-driven reference rewrites. Originals stay recoverable from git history; no originals folder.
- Archive issues keep their self-contained `images/issue-N/` folders, so duplicates across issues, or between an issue and `assets/images/`, are kept (names are still normalized).
- Pixel content is not edited: resize and recompress only, no cropping or retouching. GIFs are static and stay as they are.
- Renamed, merged or format-changed images get generated 301 redirects (a second marker block in `.htaccess`, built from the image manifest), overriding the restructure's no-media-redirects rule for these files; old server copies go away when the WinSCP synchronize runs with "Delete files" on, so no delete list is generated. Deploys are manual WinSCP, and the throttled `check-live.ps1` applies.
- No encoder is installed. `scripts/optimize-images.ps1` drives `sharp` through `npx` on demand (Node and npx are on the dev machine); nothing is added to the repo's package files and the site never depends on it.
- WebP always ships with a fallback, because the site still serves `site-legacy.js` for old browsers.

## Non-goals

- Responsive `srcset`, lazy loading or a CDN.
- Editing image content (crop, retouch, new artwork).
- Cleaning or deciding the fate of `attic/` files, and restoring old root media on the server.
- Moving or renaming folders, or redesigning pages.
- PDFs and newsletter-issue content.

## Success signal

Total live image weight, WebP and fallbacks together, is under 40 MB (from 120 MB), no live file is over 500 KB, and the site looks the same: `index.html`, a page from each topic and a sample of archive issues render with every image present, and `check-live.ps1` passes after upload.

## Assumptions

- Defaults proposed, awaiting your confirmation: long edge at most 1920 px, WebP quality about 80, typical file under 300 KB; the 40 MB and 500 KB figures above follow from these.
- GIFs (154 files, 1.2 MB) and `.ico` stay unchanged. Opaque PNG photos get a JPG fallback (their old PNG URL is redirected); transparent PNGs keep a PNG fallback and a lossless WebP.
- The two CSS image references use the fallback only, with no `image-set()`.
- Files moved to `attic/` get no redirect.
- Names are transliterated (å/ä to a, ö to o), lowercased, with spaces and underscores as hyphens; collisions get a topic or numeric suffix.
- Originals are recoverable from git history, so recompressed files are not archived separately.
