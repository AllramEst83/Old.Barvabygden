---
title: 'Normalize names and dedupe inside assets/images'
type: 'chore'
created: '2026-11-09'
status: 'in-progress'
route: 'oneshot'
review_loop_iteration: 0
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Live image names mix spaces, non-ASCII and uppercase, and byte-identical copies exist inside `assets/images/` (CAP-1, CAP-2, CAP-5).

**Approach:** Drive `scripts/rewrite-links.ps1` from `scripts/image-manifest.csv` through a new `scripts/apply-image-renames.ps1`: rename every live image to its normalized name keeping its format (format change and resize are story 4), merge the duplicate rows into the kept file with `git rm`, and rewrite every reference first. Quarantined rows are skipped.

</frozen-after-approval>

## Implementation Notes

- Added `scripts/apply-image-renames.ps1`: builds a temporary plan from `scripts/image-manifest.csv` and runs `scripts/rewrite-links.ps1` with two new options, `-ManifestPath` and `-DeleteClasses` (rewrite then `git rm`, used for merged duplicates). Rename target = old folder + stem of the manifest `new_path` + lowercased original extension, so a `convert` row keeps its format here; story 4 turns that file into the fallback and `.webp`.
- Fixed `rewrite-links.ps1` for case-only renames: `-eq` comparisons that decide whether to rewrite a reference are now `-ceq`, and a row only moves when the tracked path matches its case exactly (otherwise re-runs fail on `Gran1.GIF` -> `gran1.gif`).
- Result: 44 files changed (36 renamed, 8 duplicates removed), 67 references rewritten in 21 files. Re-run is a no-op. `check-links.ps1` stays at 128. Scans over the 504 live images: no name outside the pattern, no byte-identical pair in `assets/images/`.
- For story 4: `old_path` in `image-manifest.csv` is now the original path, not the current one. The current path of a row is the rename target above; `merge` and `quarantine` rows have no current file.
