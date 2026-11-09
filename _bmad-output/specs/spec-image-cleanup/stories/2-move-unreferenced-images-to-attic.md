---
title: 'Move unreferenced images to attic'
type: 'chore'
created: '2026-11-09'
status: 'done'
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

## Review Triage Log

- `scripts/path-manifest.csv` not updated: low, defer. That file records the folder restructure; the image manifest is the record for image work, and the README now points to it.
- Spec status still in-progress: low, patched (now done).
- No recorded verification of "unreferenced": medium, patched. Check made after the move: a re-run of the manifest scan finds 512 live images and zero quarantine rows; `check-links.ps1` stays at 128. Same-named files in archive issues (e.g. `kaffet46.jpg`, `skol.jpg`) resolve to other paths and do not count as references.
- Off-repo hotlinks to moved URLs: low, no change. The spec decided no redirects for attic moves; the files stay recoverable with `git mv`.
- Docs or skills still describing removed folders: false. A search of docs, `.agents/skills/assets-and-paths`, CLAUDE.md, GEMINI.md, README.md and `.htaccess` finds no mention of them.
- README wording, restore hint, difference from `unused-images/`: low, patched.
- Whether 2026 event art is pending: false. The inventory records the decision to move it ("decided: move to attic").
- 61.9 MB of binaries kept in the repo: low, no change. Attic pruning is a non-goal of the spec.
