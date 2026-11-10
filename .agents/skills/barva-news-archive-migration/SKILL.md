---
name: barva-news-archive-migration
description: Migrate legacy Barva-Bladet issues into the modern archive by matching issue numbers to years, moving html/pdf files into barva-bladet/<year>/ with canonical names, and updating fragments/newsletter.html year groups. Use when user asks to move old Barva-Bladet/Barva news, archive issues, or fix archive links.
---

# Barva News Archive Migration

## Use this skill when
- The task is to move old Barva-Bladet issues into the current archive.
- You need to group issues by year in `fragments/newsletter.html`.
- You need to locate both `.htm` and `.pdf` variants and fix links.
- You need to repair broken image links in moved issue `.htm` files.
- You need to insert archive back-links and fix encoding artifacts (`�`, mojibake).

## Current architecture
- `index.html` renders the `#archive` section shell only.
- `assets/js/site.js` and `assets/js/site-legacy.js` load `./fragments/newsletter.html` into `#archiveAccordion`.
- `fragments/newsletter.html` is the source of truth for displayed issue cards.
- Archive files belong in `barva-bladet/<year>/`.

## Canonical naming and links
- HTML: `barva-bladet/<year>/barva-news-<year>-issue-<issue>.htm`
- PDF: `barva-bladet/<year>/barva-news-<year>-issue-<issue>.pdf`
- Images for one issue: `barva-bladet/<year>/images/issue-<issue>/...`
- In `fragments/newsletter.html`, link as:
  - `./barva-bladet/<year>/barva-news-<year>-issue-<issue>.htm`
  - `./barva-bladet/<year>/barva-news-<year>-issue-<issue>.pdf`
- In each migrated issue HTML, use image links:
  - `./images/issue-<issue>/<filename>`
- In each migrated issue HTML, include a back-link near the top:
  - `<a href="../../index.html" class="back-link">← Tillbaka till Barvabygden.se</a>`

## Migration workflow
1. Inventory existing archive entries from `fragments/newsletter.html`.
2. Discover legacy candidates:
   - `bb-*.htm` in `migration-sources/barva-bladet/` (pending sources; their PDFs and images sit next to them, flat)
   - old pages with Barva-Bladet links (`attic/pages/old_index.htm`, `pages/Välkommen till Barva och Barvabygden.htm`, `pages/bladen.htm`)
3. Build per-issue record:
   - issue number
   - year
   - html source path (if found)
   - pdf source path (if found)
4. Move/copy from `migration-sources/barva-bladet/` to the canonical archive location and naming. Title image links become `../title/barva-bladet-title.png`.
5. For each moved HTML issue, collect and migrate image assets:
   - parse every `<img src>` and resolve path from original file context (the sources folder; files moved earlier are listed in `scripts/path-manifest.csv`)
   - copy image file into `barva-bladet/<year>/images/issue-<issue>/`
   - update issue HTML `src` paths to `./images/issue-<issue>/<filename>`
   - if two files share same name, keep both with safe suffixes and update references
   - apply the image rules (see `assets-and-paths`): lowercase ASCII hyphenated names, then `./scripts/optimize-images.ps1 -Folder barva-bladet/<year>/images/issue-<issue>`, `git add` the files, and `./scripts/apply-image-pictures.ps1` to wrap the `<img>` tags in `<picture>`
6. Insert/normalize back-link in each moved issue HTML using the standard pattern:
   - `href="../../index.html"`
   - `class="back-link"`
   - visible text: `← Tillbaka till Barvabygden.se`
7. Normalize encoding in moved issue HTML and fix mojibake:
   - eliminate replacement char `�`
   - restore Swedish letters (`å`, `ä`, `ö`, `Å`, `Ä`, `Ö`) or safe entities
8. Update year accordion and issue cards in `fragments/newsletter.html`.
9. Validate every issue and image link points to an existing file, and run `./scripts/check-images.ps1` (names, size, WebP sibling, `<picture>` markup); it must pass.
10. Remove the consumed sources from `migration-sources/barva-bladet/` and repoint the issue's redirects (manifest rows → `migrated`, then `./scripts/build-redirects.ps1`); see `barva-news-migrate-next-issue` Step 5.

## Year mapping rules
- Reuse existing mappings already present in `fragments/newsletter.html`.
- If issue is not mapped yet, read candidate html content for explicit year markers.
- Use old-index context only as fallback.
- If year remains uncertain, do not guess: report it as unresolved.

## Card format rules
- Keep existing Bootstrap markup style (`archive-category`, badges, metadata line, button group).
- Show `Format: HTML & PDF` when both exist.
- Show `Format: PDF` or `Format: HTML` when only one exists.
- Keep issues sorted within each year (ascending or consistent with local section style).

## Validation checklist
- [ ] Every `href` in `fragments/newsletter.html` exists on disk.
- [ ] Every `<img src>` in migrated issue HTML exists on disk.
- [ ] Every migrated issue HTML has the back-link to `../../index.html`.
- [ ] No obvious mojibake remains (especially `�`) in migrated issue HTML.
- [ ] Each issue appears under exactly one year group.
- [ ] No duplicate issue numbers in multiple years.
- [ ] New files are under `barva-bladet/` only.
- [ ] Consumed sources are gone from `migration-sources/barva-bladet/`; other issues' files are untouched.
- [ ] `/bb-N.htm` and the issue's PDF redirects in `.htaccess` point at the migrated URLs (no longer `/`).
- [ ] `./scripts/check-links.ps1` reports no new broken references.
