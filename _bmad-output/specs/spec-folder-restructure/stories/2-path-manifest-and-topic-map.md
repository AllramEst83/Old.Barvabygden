---
title: 'Path manifest and page-topic map'
type: 'chore'
created: '2026-11-09'
status: 'done'
baseline_commit: '49cc186c01bdf8fd1b297676cca6fe3137bcd0d0'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/SPEC.md'
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/migration-rules.md'
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/target-layout.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Stories 3–6 move ~700 files and rewrite references, but nothing yet records which file goes where. Hard-coding paths per story would drift and make redirects (CAP-8) and the server delete list (CAP-9) unreproducible.

**Approach:** Add `scripts/build-manifest.ps1`, which classifies every tracked file per `migration-rules.md` and writes `scripts/path-manifest.csv` (`old_path,new_path,class`). It also assigns each referenced image a topic folder from the page-topic map. No files move in this story; the CSV is committed for review.

## Boundaries & Constraints

**Always:** PowerShell only, run from repo root, deterministic (sorted, re-runnable, same output twice). "Referenced" = href/src/background/CSS `url()`/`fetch()` in any live page, fragment, archive issue, CSS or JS file, case-insensitive and URL-decoded (reuse the extraction logic of `scripts/check-links.ps1`). Live pages = root `.htm/.html` reachable from `index.html`/fragments by following links, minus the stale and `bb-*` rules. Images already under `assets/images/` stay put (not in manifest). Pending sources (bb-37…46, their PDFs, media referenced only by them) go to `migration-sources/barva-bladet/`; media referenced by both a live page and a pending source gets a topic row plus a second `split` row for the copy. Include story-1 moves (`scripts/`, `docs/`). Unmapped live pages and images used by 2+ topics resolve to `shared/`; unmapped pages are printed as a warning list.

**Never:** Move, edit or delete any file; touch `web/`, `.ilosoft/`, `_bmad*`, `.agents/`, `.claude/`, `.vscode/`; hard-code per-file paths (only the topic map and rule patterns); add rows for media redirects.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Live-page image | `kyrka.htm` references `1926.jpg` only | `1926.jpg,assets/images/kyrkan/1926.jpg,referenced` | N/A |
| Multi-topic image | `vapen.gif` used by `vatten.htm` and `prost-sk.htm` | `assets/images/shared/vapen.gif` | N/A |
| Live + pending | image used by `skolan.htm` and `bb-40.htm` | topic row and `migration-sources/barva-bladet/` `split` row | N/A |
| Orphan | root jpg referenced by nothing | `attic/media/<name>,orphan` | N/A |
| Non-ASCII name | `Välkommen till Barva och Barvabygden.htm` | row with exact UTF-8 filename kept | Script reads with UTF-8 output encoding |
| Unmapped page | live `.htm` not in topic map | page to `pages/`, images to `shared/` | Listed in warning output, exit 0 |
| Destination collision | two sources map to same `new_path` | Fail | Print both rows, exit 1 |

</frozen-after-approval>

## Code Map

- `scripts/check-links.ps1` -- reference-extraction regexes, UTF-8/cp1252 reading, skip list; reuse the approach
- `migration-rules.md` (spec folder) -- classification table and page-topic map to encode
- Tracked files: 704 root loose files (526 jpg, 70 gif, 53 htm, 22 pdf), `bilder/` (101), `onewebmedia/` (2), `upload.tmp/` (1), `images/coffee-beans/`, `assets/{pages,barva-news,unused-images}`
- `assets/images/` -- existing topic folders (branding, hero, events, museum, skolan, barva-if, …) that stay put
- Use `git -c core.quotepath=off ls-files` and UTF-8 console encoding so non-ASCII names are not octal-escaped

## Tasks & Acceptance

**Execution:**
- [x] `scripts/build-manifest.ps1` -- list tracked files, build reference map, classify per rules, resolve topics, detect collisions, write sorted CSV, print per-class counts and unmapped pages -- single source for later stories
- [x] `scripts/path-manifest.csv` -- generate and commit -- reviewed artifact driving stories 3–6
- [x] `scripts/link-baseline.txt` -- unchanged; confirm `check-links.ps1` still at baseline

**Acceptance Criteria:**
- Given a clean tree, when the script runs twice, then both CSVs are byte-identical.
- Given the manifest, when every `old_path` is checked, then each exists in git and none is under `web/` or `.ilosoft/`.
- Given the manifest, when `new_path` values are checked, then all are unique and every top folder is in `target-layout.md`.
- Given the root files, when the manifest is applied, then the only files left at root are the allowlist plus rows explicitly absent (reported count is zero unexpected).
- Given the working tree after the run, then `git status` shows only the two new/changed `scripts/` files.

## Implementation Notes

- 1026 rows: archive 205, orphan 490, pending-source 168, referenced 91, live-page 38, junk 13, stale 9, duplicate 4, docs 3, fragment 2, tooling 2, split 1.
- Decision: every root page that is not stale or pending is a `live-page` going to `pages/`, even if only old pages link to it (per target-layout "other live root .htm" and the topic map); 18 such pages are reported as not linked from any live page. Reachability is a report only.
- Decision: a `bilder/` file whose name equals a root file's goes to `assets/images/<topic>/bilder/` (4 cases: dopfatm.gif, dopfunt1.gif, grf.jpg, tavlaec.jpg). Needed to keep `new_path` unique.
- Media referenced only by stale/unlinked-from-live pages (e.g. 1926.jpg, only in bb-33) are orphans and go to `attic/media/`.
- Newsletter PDFs by number: 37-46 pending, other numbered ones `duplicate` to `attic/pdfs/`; non-newsletter PDFs referenced by live pages would go to `assets/files/` (none matched currently).
- Story 1 moves (`assets/powershell`, `assets/docs`, goals/todo) are static rows; their old paths no longer exist locally, so the "old_path exists in git" check exempts classes tooling/docs.
- Blind Hunter subagent skipped (no explicit subagent permission); self-reviewed. `check-links.ps1` still at baseline (254).

## Verification

**Commands:**
- `pwsh ./scripts/build-manifest.ps1` (or `powershell -File`) -- expected: exit 0, class counts printed
- `./scripts/check-links.ps1` -- expected: no new broken references
