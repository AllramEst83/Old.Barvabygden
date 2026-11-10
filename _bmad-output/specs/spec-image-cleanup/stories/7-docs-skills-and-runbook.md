---
title: 'Docs, skills and runbook for the image rules'
type: 'chore'
created: '2026-11-10'
status: 'in-review'
route: 'oneshot'
review_loop_iteration: 0
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** The naming, size, format and `<picture>` rules exist only in the scripts and specs, so the next image or newsletter issue would reintroduce spaces, uppercase names and unoptimized PNGs (CAP-7, CAP-6).

**Approach:** State the rules in `CLAUDE.md`, `GEMINI.md`, the assets and newsletter-migration skills and `docs/deploy-runbook.md` (image upload and the WinSCP delete note), and check a newly migrated issue's images against them with a runnable check.

</frozen-after-approval>

## Implementation Notes

- Added the rules (names, size, JPG versus PNG, WebP sibling and `<picture>` markup, how to add images, redirects for renamed images) to `CLAUDE.md`, `GEMINI.md` and the `assets-and-paths` skill. The two newsletter skills now tell the migrator to name images per the rules, run the optimizer on the issue's image folder, wrap the tags, and run the check. The runbook has an Images section: upload size, old server copies and the optional limited "Delete files" synchronize, the 16 case-only renames, verification, cache, and later images.
- Added `scripts/check-images.ps1`: fails on a bad name, a file over 500 KB or 1920 px, a `.jpg`/`.png` without a `.webp`, byte-identical files in `assets/images/`, and an `<img>` of a converted image outside `<picture>`. It passes on the whole repo (874 images, 93 pages); a deliberately bad file made it report name, missing WebP and duplicate. `-Path` limits the file checks to one folder; it passes for issue 46's images (a recently migrated issue).
- Added `-Folder` to `scripts/optimize-images.ps1` so new images that are not in the manifest can be optimized: resizes in place, writes the `.webp`, keeps PNG as PNG with a lossless WebP, skips names that break the rule. Tested on a temporary folder (idempotent, bad name skipped); the temporary files were removed. A large photo saved as PNG stays large and is flagged, so the skills say to convert photos to JPG first.
- `check-links.ps1` stays at 128.
