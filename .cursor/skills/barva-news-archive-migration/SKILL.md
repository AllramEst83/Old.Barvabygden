---
name: barva-news-archive-migration
description: Migrate legacy Barva-Bladet issues into the modern archive by matching issue numbers to years, moving html/pdf files into assets/barva-news/<year>/ with canonical names, and updating assets/pages/newsletter.html year groups. Use when user asks to move old Barva-Bladet/Barva news, archive issues, or fix archive links.
---

# Barva News Archive Migration

## Use this skill when
- The task is to move old Barva-Bladet issues into the current archive.
- You need to group issues by year in `assets/pages/newsletter.html`.
- You need to locate both `.htm` and `.pdf` variants and fix links.
- You need to repair broken image links in moved issue `.htm` files.
- You need to insert archive back-links and fix encoding artifacts (`�`, mojibake).

## Current architecture
- `index.html` renders the `#archive` section shell only.
- `assets/js/site.js` and `assets/js/site-legacy.js` load `./assets/pages/newsletter.html` into `#archiveAccordion`.
- `assets/pages/newsletter.html` is the source of truth for displayed issue cards.
- Archive files belong in `assets/barva-news/<year>/`.

## Canonical naming and links
- HTML: `assets/barva-news/<year>/barva-news-<year>-issue-<issue>.htm`
- PDF: `assets/barva-news/<year>/barva-news-<year>-issue-<issue>.pdf`
- Images for one issue: `assets/barva-news/<year>/images/issue-<issue>/...`
- In `assets/pages/newsletter.html`, link as:
  - `./assets/barva-news/<year>/barva-news-<year>-issue-<issue>.htm`
  - `./assets/barva-news/<year>/barva-news-<year>-issue-<issue>.pdf`
- In each migrated issue HTML, use image links:
  - `./images/issue-<issue>/<filename>`
- In each migrated issue HTML, include a back-link near the top:
  - `<a href="../../../index.html" class="back-link">← Tillbaka till Barvabygden.se</a>`

## Migration workflow
1. Inventory existing archive entries from `assets/pages/newsletter.html`.
2. Discover legacy candidates:
   - `bb-*.htm` in repo root
   - old pages with Barva-Bladet links (`old_index.htm`, `Välkommen till Barva och Barvabygden.htm`, `bladen.htm`)
3. Build per-issue record:
   - issue number
   - year
   - html source path (if found)
   - pdf source path (if found)
4. Move/copy to canonical archive location and naming.
5. For each moved HTML issue, collect and migrate image assets:
   - parse every `<img src>` and resolve path from original file context
   - copy image file into `assets/barva-news/<year>/images/issue-<issue>/`
   - update issue HTML `src` paths to `./images/issue-<issue>/<filename>`
   - if two files share same name, keep both with safe suffixes and update references
6. Insert/normalize back-link in each moved issue HTML using the standard pattern:
   - `href="../../../index.html"`
   - `class="back-link"`
   - visible text: `← Tillbaka till Barvabygden.se`
7. Normalize encoding in moved issue HTML and fix mojibake:
   - eliminate replacement char `�`
   - restore Swedish letters (`å`, `ä`, `ö`, `Å`, `Ä`, `Ö`) or safe entities
8. Update year accordion and issue cards in `assets/pages/newsletter.html`.
9. Validate every issue and image link points to an existing file.

## Year mapping rules
- Reuse existing mappings already present in `assets/pages/newsletter.html`.
- If issue is not mapped yet, read candidate html content for explicit year markers.
- Use old-index context only as fallback.
- If year remains uncertain, do not guess: report it as unresolved.

## Card format rules
- Keep existing Bootstrap markup style (`archive-category`, badges, metadata line, button group).
- Show `Format: HTML & PDF` when both exist.
- Show `Format: PDF` or `Format: HTML` when only one exists.
- Keep issues sorted within each year (ascending or consistent with local section style).

## Validation checklist
- [ ] Every `href` in `assets/pages/newsletter.html` exists on disk.
- [ ] Every `<img src>` in migrated issue HTML exists on disk.
- [ ] Every migrated issue HTML has the back-link to `../../../index.html`.
- [ ] No obvious mojibake remains (especially `�`) in migrated issue HTML.
- [ ] Each issue appears under exactly one year group.
- [ ] No duplicate issue numbers in multiple years.
- [ ] New files are under `assets/barva-news/` only.
