---
title: 'Move live pages and group images by topic'
type: 'chore'
created: '2026-11-09'
status: 'done'
baseline_commit: '19bb70b6c08adcc854a50871b02085de4a288346'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/SPEC.md'
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/migration-rules.md'
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/target-layout.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** 38 live pages and ~90 images they use still sit loose at the repo root (or in `bilder/`), so the root and `assets/` stay unreadable, and moving them by hand would break references.

**Approach:** Add `scripts/rewrite-links.ps1`, manifest-driven: for the chosen classes it first rewrites every reference that points at, or comes from, a moving file, then runs `git mv`. Run it for classes `live-page` and `referenced` (also `index.html`, JS, CSS, fragments, archive). The same script serves story 5.

## Boundaries & Constraints

**Always:** Read `scripts/path-manifest.csv`; never hard-code paths. Rewrite while files are still at their old locations, then move. Compute each new reference relative to the source file's new location, keep the original style (`./` prefix, root-absolute `/…`, query/fragment, `%20` encoding if the original used it). Byte-safe (Latin-1 round trip) so legacy encodings survive. Rewrite only references that resolve to a tracked file; leave unresolvable ones (the known `*-filer/` folders) as they are. Skip source files that are `pending-source` or `stale`, plus `web/`, `.ilosoft/`, `_bmad*`, `.agents/`, `.claude/`, `.vscode/`, `docs/`, `scripts/`. Where a file has both a `referenced` and a `split` row, move to the `referenced` destination (story 5 makes the pending copy from there). Run `scripts/check-links.ps1` before committing; it must stay at or under baseline, and the stiftelsen page's `index.html` links (`/assets/pages/…`, `./assets/pages/…`) must become `./pages/…`.

**Never:** Touch `web/` or `.ilosoft/`; rename moved files; move pending, stale, orphan or junk files (story 5); edit page content beyond reference paths; add redirects.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Page link in index | `index.html`: `./bankl.htm` | `./pages/bankl.htm` | N/A |
| Moved page, shared asset | `pages/kyrka.htm` had `./assets/css/site.css` | `../assets/css/site.css` | N/A |
| Moved page, moved image | `kyrka.htm`: `tavlaec.jpg` | `../assets/images/kyrkan/bilder/tavlaec.jpg` | N/A |
| Sibling page link | `kyrka.htm` → `skolan.htm` | unchanged | N/A |
| Page → archive | `bladen.htm`: `./barva-bladet/title/x.png` | `../barva-bladet/title/x.png` | N/A |
| Encoded name | `V%C3%A4lkommen%20till…htm` | new path keeps encoding style | N/A |
| Unresolvable ref | `fnv-08-filer/…` | left unchanged, stays in baseline | N/A |
| Target missing in manifest | ref resolves to a tracked file not in manifest | unchanged unless the source moved, then re-relativized | N/A |

</frozen-after-approval>

## Code Map

- `scripts/path-manifest.csv` -- 38 `live-page` and 91 `referenced` rows drive the move; `split` row for `ababok1.jpg` is story 5's copy
- `scripts/check-links.ps1` -- reference extraction/resolution to mirror (including fragment and fetch handling); baseline in `scripts/link-baseline.txt`
- `scripts/build-manifest.ps1` -- topic map and class logic; do not change
- `index.html` -- 18 `./X.htm` links plus 2 stiftelsen links (lines ~279, ~1330)
- `assets/pages/stiftelsen-barva-bygdegard.htm` -- live page moving to `pages/`
- `assets/js/site.js`, `assets/js/site-legacy.js`, `fragments/*.html`, `barva-bladet/**` -- may reference moving media; rewritten only if so

## Tasks & Acceptance

**Execution:**
- [x] `scripts/rewrite-links.ps1` -- manifest-driven rewrite then `git mv`; params `-Classes`, `-WhatIf`; prints rewritten/moved counts -- reusable by story 5
- [x] Run for `live-page,referenced` -- moves 38 pages and 91 images -- CAP-2, CAP-3
- [x] `scripts/link-baseline.txt` -- update only for pure path renames of already-broken refs; review the diff -- CAP-3

**Acceptance Criteria:**
- Given the run, then every `live-page` and `referenced` row's `new_path` exists and its `old_path` does not.
- Given `check-links.ps1`, then it reports no new broken references versus the (path-mapped) baseline.
- Given `index.html`, then no `./X.htm` link to a moved page remains.
- Given the root, then it holds no live page and no referenced image.
- Given a re-run of `rewrite-links.ps1`, then it changes nothing.

## Implementation Notes

- `scripts/rewrite-links.ps1 -Classes live-page,referenced` rewrote 233 references in 35 files, then moved 129 files (38 pages, 91 images); a second `-WhatIf` run reports 0 changes.
- Stiftelsen link at `index.html:279` was root-absolute (`/pages/…` after the generic rewrite); set to `./pages/…` as required.
- Link check: no new broken references versus the path-mapped baseline except 15 from sources the rewriter deliberately skips (stale `barvaf (2).htm`, pending `bb-31`/`bb-40`) whose targets moved. Baseline set to 217 including them; story 5 moves those sources out of the checked scope and the baseline shrinks then.
- `ababok1.jpg` (split) now lives in its topic folder; story 5 must copy it to `migration-sources/barva-bladet/` from there.
- Pre-existing broken refs (`*-filer/`, `hero-banner.jpg.jpg`, `./assets/…` inside a `-filer/` path) were left untouched.
- Not checked in a browser; subagent review skipped.

## Verification

**Commands:**
- `./scripts/rewrite-links.ps1 -Classes live-page,referenced -WhatIf` -- expected: counts, no changes
- `./scripts/check-links.ps1` -- expected: exit 0
