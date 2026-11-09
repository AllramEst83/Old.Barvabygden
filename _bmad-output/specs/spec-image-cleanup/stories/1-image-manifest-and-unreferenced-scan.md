---
title: 'Image manifest and unreferenced-image scan'
type: 'feature'
created: '2026-11-09'
status: 'in-progress'
baseline_commit: '01618b5ff493c315359c1e8dc57eeb90df45ec26'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/specs/spec-image-cleanup/SPEC.md'
  - '{project-root}/_bmad-output/specs/spec-image-cleanup/image-rules.md'
  - '{project-root}/_bmad-output/specs/spec-image-cleanup/image-inventory.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Later stories (attic move, rename/dedupe, WebP conversion, redirects) need one reviewed, deterministic plan for every live image. None exists, and the 32-image unreferenced list rests on a weak file-name search.

**Approach:** Add `scripts/build-image-manifest.ps1` (modelled on `scripts/build-manifest.ps1`) that writes `scripts/image-manifest.csv` for all live images per `image-rules.md`, with a stronger unreferenced scan. No file is moved, renamed or edited.

## Boundaries & Constraints

**Always:** Live scope is tracked images under `assets/images/` and `barva-bladet/` (jpg, jpeg, png, gif, webp, ico). Columns `old_path,new_path,action,old_bytes,new_bytes` (`new_bytes` is an estimate or empty until story 4). Actions: `rename`, `merge`, `resize`, `convert`, `quarantine`, `keep`; precedence quarantine > merge > convert > resize > rename > keep. Output sorted, deterministic, re-runnable; paths forward-slash. Exit 1 on any `new_path` collision (collisions get a topic or numeric suffix first). Merges only inside `assets/images/` (equal SHA-256, keep the most specific topic folder a page references, else `shared/`); archive per-issue copies are never merged. A `convert` row's `new_path` is the fallback (opaque PNG becomes `.jpg`, transparent PNG stays `.png`; the `.webp` sibling is implied). GIF and `.ico` are `keep`/`rename` only. `resize` applies when the long edge exceeds 1920 px or the file exceeds 500 KB without being converted.

**Never:** Touch `web/`, `.ilosoft/`, `attic/`, `migration-sources/`; move, delete or write any image; add dependencies; hard-code paths of individual images.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Unreferenced | basename absent from all text sources in plain, `%`-encoded and lowercased forms | `quarantine` row; new_path `attic/unreferenced-images/<old path>` | N/A |
| Referenced only via CSS/JS/inline script | name found in `.css`, `.js` or `index.html` script | not quarantined | N/A |
| Name collision after normalizing | two files normalize to one name | second gets topic/numeric suffix | exit 1 if still colliding |
| Non-ASCII name | `bygdegården--73.jpg` | `bygdegarden-73.jpg` style, extension lowercased | N/A |
| Unreadable image | corrupt file | row marked `keep`, warning printed | warn, exit 0 |

</frozen-after-approval>

## Code Map

- `scripts/build-manifest.ps1` -- template: tracked-file listing, UTF-8/cp1252 `Read-Text`, ref regexes, collision exit code
- `scripts/rewrite-links.ps1` -- later consumer approach for manifest-driven rewrites (story 3); do not change
- `scripts/check-links.ps1`, `scripts/link-baseline.txt` -- must stay at or under baseline (128)
- `assets/css/site.css:32,434` -- the two CSS image references (count as references)
- `attic/README.md` -- extended in story 2, not here
- Sources for the scan: `index.html` (inline scripts), `pages/`, `fragments/`, `barva-bladet/**/*.htm*`, `assets/css`, `assets/js`

## Tasks & Acceptance

**Execution:**
- [ ] `scripts/build-image-manifest.ps1` -- survey live images (size, dimensions via System.Drawing, WebP header, PNG alpha scan, SHA-256), classify, normalize names, write CSV and print a summary -- single source of truth for stories 2-6
- [ ] `scripts/build-image-manifest.ps1` -- stronger reference scan (basename in decoded/encoded/lowercased forms across the sources above) -- confirms CAP-8 quarantine rows
- [ ] `scripts/image-manifest.csv` -- generate and commit; review the quarantine set against the 32 in `image-inventory.md` and explain differences in Implementation Notes
- [ ] `_bmad-output/specs/spec-image-cleanup/image-inventory.md` -- refresh totals now that issues 38-46 are in `barva-bladet/`

**Acceptance Criteria:**
- Given the repo, when the script runs twice, then both CSVs are byte-identical and the working tree has no moved or modified image.
- Given the manifest, when scanned, then every live image has exactly one row, no `new_path` repeats, and every non-quarantine `new_path` matches the naming pattern in `image-rules.md`.
- Given the quarantine rows, when each basename is grepped across the scan sources, then none is found.
- Given `scripts/check-links.ps1`, when run, then it reports at or under the baseline.

## Implementation Notes

## Spec Change Log

## Review Triage Log

## Design Notes

Rows with several needs (e.g. rename plus resize plus WebP) are one `convert` row: `old_path` to fallback `new_path`; precedence keeps the CSV one-row-per-file. Opaque-versus-transparent PNG is decided by scanning alpha values, not the pixel format flag.

## Verification

**Commands:**
- `powershell -File scripts/build-image-manifest.ps1` -- expected: exit 0, summary of counts per action
- `powershell -File scripts/check-links.ps1` -- expected: at or under 128
- `git status --short` -- expected: only the script, CSV and inventory changed
