---
title: 'Move unreferenced images to attic'
type: 'chore'
created: '2026-11-09'
status: 'in-progress'
route: 'oneshot'
review_loop_iteration: 0
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** 30 live images that no page, fragment, archive issue, CSS or JS uses (61.9 MB) still sit in `assets/images/` and ship with the site (CAP-8).

**Approach:** `git mv` every `quarantine` row of `scripts/image-manifest.csv` to `attic/unreferenced-images/<original path>` and add the folder to `attic/README.md`. No redirects for these files; the manifest stays as the record of the move.

</frozen-after-approval>

## Implementation Notes

- Moved the 30 `quarantine` rows with `git mv` to `attic/unreferenced-images/<original path>` (renames, history kept); removed 10 emptied folders under `assets/images/`; added a row to `attic/README.md`.
- Re-running `build-image-manifest.ps1` on the new tree finds 512 live images and no quarantine rows. `scripts/image-manifest.csv` was deliberately left as generated: its `quarantine` rows record this move, and later stories must skip them. A later re-run drops those rows.
- `check-links.ps1`: 128, equal to baseline. No redirects, per the spec.
