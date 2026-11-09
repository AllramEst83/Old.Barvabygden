---
title: 'Optimize script, resize and convert to WebP'
type: 'feature'
created: '2026-11-09'
status: 'in-review'
baseline_commit: '6f1b182294352bed3dafb1ffde51eb157a474947'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/specs/spec-image-cleanup/SPEC.md'
  - '{project-root}/_bmad-output/specs/spec-image-cleanup/image-rules.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** The 370 `convert` images still ship at full size and without WebP, so live weight is about 66 MB (CAP-3, CAP-4).

**Approach:** Add `scripts/optimize-images.ps1`, which reads `scripts/image-manifest.csv` and, through a pinned `sharp-cli` run by `npx`, writes each `.webp` plus the resized fallback beside it. HTML is not touched.

## Boundaries & Constraints

**Always:** Resize and recompress only (long edge at most 1920 px, never upscaled, EXIF stripped, WebP quality 80, lossless WebP for transparent PNG). Fallback keeps its format except opaque PNG, which becomes a new `.jpg`. Idempotent: re-running writes nothing new and reports sizes. Files processed are at their story-3 names (old folder + stem of `new_path` + original extension). Nothing is added to the repo's package files.

**Decisions (human, 2026-11-09):** the encoder is `npx -y sharp-cli@6.1.0` (pins sharp 0.35.4), allowed to run as a pinned download; the quality defaults stand (1920 px, WebP 80, typical file under 300 KB, none over 500 KB).

**Never:** Edit HTML or CSS, crop or retouch, touch GIF/`.ico`, change `quarantine`/`merge` rows, delete an opaque PNG's original (pages still reference it until story 5 repoints them and removes it).

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Photo JPG | 4000 px JPG | JPG at 1920 px plus `.webp` | N/A |
| Opaque PNG photo | 16 MB PNG | `.jpg` fallback plus `.webp`; old PNG stays | N/A |
| Transparent PNG | logo PNG | optimized PNG plus lossless `.webp` | N/A |
| Small image | 300 px JPG | not upscaled, `.webp` added | N/A |
| Fallback over 500 KB | detail-heavy JPG | raise cap for that file, record reason | warning, exit 0 |
| Re-run | all outputs exist | no writes, same report | N/A |

</frozen-after-approval>

## Code Map

- `scripts/image-manifest.csv` -- input; `convert` rows (370, 46 PNG of which 33 opaque become `.jpg`)
- `scripts/apply-image-renames.ps1` -- `Get-RenameTarget` gives the current (story-3) path of a row; reuse its rule
- `scripts/build-image-manifest.ps1` -- already detects PNG alpha (`Test-PngAlpha`); reuse the same alpha rule or read the manifest's fallback extension
- `_bmad-output/specs/spec-image-cleanup/image-rules.md` -- caps and policy table

## Tasks & Acceptance

**Execution:**
- [ ] `scripts/optimize-images.ps1` -- read manifest, derive current paths, run pinned sharp-cli per image, write `.webp` and fallback, report before/after sizes
- [ ] images listed as `convert` -- run the script and commit the generated `.webp` and resized fallbacks

**Acceptance Criteria:**
- Given the manifest, when the script runs, then every `convert` row has a `.webp` sibling and a fallback within the caps.
- Given a second run, when it finishes, then no file changed.
- Given `scripts/check-links.ps1`, when run, then it stays at or under 128.

## Implementation Notes

## Spec Change Log

## Review Triage Log

## Verification

**Commands:**
- `powershell -File scripts/optimize-images.ps1` -- expected: exit 0, size report, none over 500 KB
- `powershell -File scripts/check-links.ps1` -- expected: at or under 128

- Added `scripts/optimize-images.ps1` (`npx -y sharp-cli@6.1.0`, which pins sharp 0.35.4). It groups images by folder, runs the encoder once per folder and format, applies EXIF orientation before stripping metadata, writes the fallback first and the `.webp` last (so a `.webp` marks an image as done), and fills `new_bytes` in `scripts/image-manifest.csv` with the fallback size.
- Run on all 370 `convert` rows: originals 61.5 MB became fallbacks 12.9 MB plus WebP 11.1 MB. Every new or recompressed file is under 500 KB. A second run processes 0 images. `check-links.ps1` stays at 128. Spot check: `museum/fence-1.jpg` is 1920 px wide, upright, 432 KB (16.7 MB PNG before).
- The 33 opaque PNGs now have a `.jpg` fallback beside the old `.png`, which stays tracked because pages still reference it. The picture-markup story (5) must repoint those references and then `git rm` the 33 superseded PNGs; only then does live weight drop to the target. Same-format files (JPG, transparent PNG) were recompressed in place only when the result was smaller.
