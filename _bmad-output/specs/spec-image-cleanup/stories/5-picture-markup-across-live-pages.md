---
title: 'Picture markup across live pages'
type: 'feature'
created: '2026-11-09'
status: 'draft'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/specs/spec-image-cleanup/SPEC.md'
  - '{project-root}/_bmad-output/specs/spec-image-cleanup/image-rules.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** The WebP files and resized fallbacks exist (story 4) but no page uses them, so visitors still download the old files, and 33 opaque PNGs now have a `.jpg` fallback that pages do not reference (CAP-4).

**Approach:** Add `scripts/apply-image-pictures.ps1`. Step one repoints every reference to a superseded PNG (`src`, `href`, `data-full`, CSS `url()`, `background`) at its `.jpg` and removes the PNGs with `git rm`, through `scripts/rewrite-links.ps1`. Step two wraps each `<img>` of a converted image in `index.html`, `pages/`, `fragments/` and `barva-bladet/` as `<picture><source type="image/webp" srcset="..."><img ...></picture>`. CSS keeps the fallback only.

## Boundaries & Constraints

**Always:** Keep every existing `<img>` attribute unchanged on the inner `<img>`; byte-safe edits (detect UTF-8 or Windows-1252, keep line endings); keep the original path style (`./`, `/`, `../`, `%`-encoding); wrap only images whose `.webp` sibling exists; idempotent (an `<img>` already inside a `<picture>` is skipped); lightbox `data-full` and CSS `url()` use the fallback.

**Never:** Wrap GIFs or images without a WebP sibling; add `srcset` sizes, lazy-loading or width/height; touch `web/`, `attic/`, `migration-sources/`, `docs/`; remove a PNG that is still referenced anywhere.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Plain img | `<img src="a/x.jpg" alt="t">` | picture with `x.webp` source and the same img | N/A |
| Opaque PNG | `<img src="a/y.png" class="c">` | picture; img `src` is `y.jpg`, attributes kept | N/A |
| Quoting and case | `<IMG SRC=x.jpg border=0>` | wrapped; attributes untouched | N/A |
| No WebP sibling | `<img src="a.gif">` | unchanged | N/A |
| Already wrapped | img inside picture | unchanged (re-run is a no-op) | N/A |
| Reference outside img | `data-full`, CSS `url()` to a superseded PNG | repointed to the `.jpg` | N/A |
| Broken ref | `src` that resolves to nothing | unchanged | counted in report |

</frozen-after-approval>

## Code Map

- `scripts/rewrite-links.ps1` -- regex rewriting, byte-safe read/write, `-ManifestPath`, `-DeleteClasses`; its patterns lack `data-full`, add it
- `scripts/apply-image-renames.ps1` -- template for building a temp plan and calling rewrite-links
- `scripts/image-manifest.csv` -- the 33 `convert` rows with `.png` old path and `.jpg` new path are the PNGs to supersede
- `index.html` (18 `data-full`), `pages/`, `fragments/`, `barva-bladet/**/*.htm` -- 1,445 `<img>` in 93 files, 0 `<picture>` today
- `assets/css/site.css:32,434` -- two CSS refs; `background-pattern-1.png` is superseded, repoint to the `.jpg`
- `pages/stiftelsen-barva-bygdegard.htm` -- 15 `data-full="../images/barvagarden/..."` already point nowhere since the restructure; fix them to `../assets/images/barvagarden/` (they also name `aa-stora-salen2-.jpg`, renamed in story 3)

## Tasks & Acceptance

**Execution:**
- [ ] `scripts/rewrite-links.ps1` -- add `data-full` to the reference patterns
- [ ] `scripts/apply-image-pictures.ps1` -- step one (repoint and remove superseded PNGs) and step two (wrap images), with `-WhatIf` and a summary
- [ ] live pages and `assets/css/site.css` -- run the script and commit the result

**Acceptance Criteria:**
- Given the live pages, when scanned, then every `<img>` of a converted image sits in a `<picture>` whose WebP source exists on disk, and no page, fragment, CSS or JS references a removed PNG.
- Given a second run, when it finishes, then no file changes.
- Given `scripts/check-links.ps1`, when run, then it is at or under 128 (fixed `data-full` refs may lower it, never raise it).
- Given `index.html`, one page per topic and three archive issues opened in a browser, when viewed, then all images show, with and without WebP support.

## Implementation Notes

## Spec Change Log

## Review Triage Log

## Verification

**Commands:**
- `powershell -File scripts/apply-image-pictures.ps1 -WhatIf` -- expected: counts of references repointed and images wrapped
- `powershell -File scripts/check-links.ps1` -- expected: at or under 128

**Manual checks:**
- Open `index.html` and a few pages in a browser; confirm images and the lightbox show.
