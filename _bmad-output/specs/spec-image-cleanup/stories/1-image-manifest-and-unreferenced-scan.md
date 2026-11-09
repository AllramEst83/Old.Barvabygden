---
title: 'Image manifest and unreferenced-image scan'
type: 'feature'
created: '2026-11-09'
status: 'in-review'
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
- [x] `scripts/build-image-manifest.ps1` -- survey live images (size, dimensions via System.Drawing, WebP header, PNG alpha scan, SHA-256), classify, normalize names, write CSV and print a summary -- single source of truth for stories 2-6
- [x] `scripts/build-image-manifest.ps1` -- stronger reference scan (basename in decoded/encoded/lowercased forms across the sources above) -- confirms CAP-8 quarantine rows
- [x] `scripts/image-manifest.csv` -- generate and commit; review the quarantine set against the 32 in `image-inventory.md` and explain differences in Implementation Notes
- [x] `_bmad-output/specs/spec-image-cleanup/image-inventory.md` -- refresh totals now that issues 38-46 are in `barva-bladet/`

**Acceptance Criteria:**
- Given the repo, when the script runs twice, then both CSVs are byte-identical and the working tree has no moved or modified image.
- Given the manifest, when scanned, then every live image has exactly one row, no `new_path` repeats, and every non-quarantine `new_path` matches the naming pattern in `image-rules.md`.
- Given the quarantine rows, when each is checked against the scan, then none has a path-resolved reference, none is named by an already-broken link, none has its bare name in script text, and none has `parent-folder/name` in any source. (A bare name in an HTML page that resolves to a different existing file does not keep it live.)
- Given `scripts/check-links.ps1`, when run, then it reports at or under the baseline.

## Implementation Notes

- Added `scripts/build-image-manifest.ps1` and generated `scripts/image-manifest.csv` (542 rows: 370 convert, 129 keep, 30 quarantine, 8 merge, 5 rename; no resize rows, the only live webp files are under the caps). Two runs are byte-identical; `check-links.ps1` stays at 128.
- Unreferenced rule: an image is live if a path-resolved reference hits it, if `parent-folder/name` occurs in any source, if its bare name occurs in script text, or if an already-broken link names it (e.g. `pages/skolan.htm` -> `skola.jpg`; repointing is out of scope). Quarantine is 30, not the inventory's 32: the three issue-35 images with non-ASCII names are referenced by the issue-35 page via encoded paths, and `majstang/majst.gif` is quarantined although the inventory did not list it (32 - 3 + 1).
- Merge keep-rule: most distinct referencing sources, then `shared/`, then shortest path.
- `6-k-axsn.gif` (issue 6) is a 0-byte file; kept with a warning, not part of this story.
- Windows is case-insensitive: story 3 must do case-only renames (e.g. `Skolkort.jpg`) with a two-step `git mv`.

## Spec Change Log

- Review pass 1, finding "AC 3 says no quarantined basename is found in any scan source", but `kaffet46.jpg`, `skol.jpg`, `skol2.jpg`, `Skolkort.jpg`, `bif-1.gif` appear by bare name in archive pages where the reference resolves to a different, existing file. Amended the acceptance criterion to the path-aware rule the script implements. Known-bad state avoided: a name-only scan keeps those files live and misses 6 of the 30. KEEP: path-resolved refs, dangling-link guard, script-text bare-name guard. The script was patched in place rather than reverted and re-derived; its quarantine and merge output did not change.

## Review Triage Log

| Finding | Verdict | Route | Evidence |
|---|---|---|---|
| `-ne` is case-insensitive, so Gran1.GIF x2 and VAPEN4.GIF are `keep` with a changed name | high | patch | Reproduced in CSV; changed to `-cne` (3 rows now `rename`). |
| Indexed PNG with tRNS is classed opaque | medium | patch | `IsAlphaPixelFormat` is false for 8bpp; now also checks `ImageFlags.HasAlpha`. |
| Implied `.webp` siblings not in collision check | medium | patch | foo.jpg and transparent foo.png both give foo.webp; convert rows now claim the sibling. |
| 0-byte/unreadable files enter dedupe | low | patch | Filtered by Readable and Bytes > 0. |
| Deleted tracked file aborts the run | low | patch | Missing files are skipped with a warning. |
| Scan sources omit .htaccess/json/xml/svg/txt/php | medium | patch | Widened to every tracked text file outside non-live areas. |
| No data-*/content/srcset handling; entities not decoded | medium | patch | Patterns widened, srcset split, HtmlDecode variant added. |
| Sort and `-lt` culture-sensitive; O(n^2) loop | low | patch | Ordinal sort, dictionary-free single-pass loop. |
| No automated post-conditions | medium | patch | Script now exits 1 on row-count, name-pattern and quarantine-reference violations. |
| Merge keep-rule differs from image-rules.md | medium | patch | Now: only referenced member, else `shared/`, then ref count, then shortest. Output unchanged. |
| AC 3 contradicted by bare-name matches in pages | medium | bad_spec | See Spec Change Log; AC amended. |
| Inventory still lists 32 / 63.5 MB and the issue-35 images | low | patch | Superseded note added to the inventory. |
| Substring match `1.jpg` in `11.jpg`, `x/a.jpg` in `xx/a.jpg` | low | defer | Errs towards keeping files live; fix needs token-boundary matching. |
| No reason column or broken-reference report | low | defer | Useful for audit, not required by the intent. |
| Case-mismatched refs count as valid | low | defer | Pre-existing; Apache would 404 them. |
| `new_bytes` empty for convert/resize | low | defer | Spec allowed an estimate or empty; story 4 fills it. |
| Resize/WebP-header paths never exercised | low | defer | No live webp is under the caps; revisit when one is. |
| Short name kept live by a coincidental script match | false | none | Conservative direction, not a defect. |
| Alpha scan slow on large PNGs | false | none | Whole run takes about 5 s. |

## Design Notes

Rows with several needs (e.g. rename plus resize plus WebP) are one `convert` row: `old_path` to fallback `new_path`; precedence keeps the CSV one-row-per-file. Opaque-versus-transparent PNG is decided by scanning alpha values, not the pixel format flag.

## Verification

**Commands:**
- `powershell -File scripts/build-image-manifest.ps1` -- expected: exit 0, summary of counts per action
- `powershell -File scripts/check-links.ps1` -- expected: at or under 128
- `git status --short` -- expected: only the script, CSV and inventory changed
