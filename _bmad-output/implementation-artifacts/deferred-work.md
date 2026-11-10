- source_spec: `_bmad-output/specs/spec-image-cleanup/stories/1-image-manifest-and-unreferenced-scan.md`
  summary: Match image names on token boundaries in the unreferenced scan
  evidence: Substring matching keeps `1.jpg` live because of `11.jpg`; flagged in Story 1 review as low.
- source_spec: `_bmad-output/specs/spec-image-cleanup/stories/1-image-manifest-and-unreferenced-scan.md`
  summary: Add a reason column or report to the image manifest
  evidence: Audit of why each file is live or quarantined is currently impossible; Story 1 review, low.
- source_spec: `_bmad-output/specs/spec-image-cleanup/stories/1-image-manifest-and-unreferenced-scan.md`
  summary: Report case-mismatched image references
  evidence: Resolver lowercases paths so wrong-case links count as valid, but Apache on Linux would 404 them; Story 1 review, low.
- source_spec: `_bmad-output/specs/spec-image-cleanup/stories/1-image-manifest-and-unreferenced-scan.md`
  summary: Exercise the WebP header and resize paths of build-image-manifest.ps1
  evidence: No live webp is under the caps, so `Get-WebpSize` and the `resize` branch have never run on real data; Story 1 review.
- source_spec: `_bmad-output/specs/spec-image-cleanup/stories/6-image-redirects-and-live-check.md`
  summary: Document in the deploy runbook that the 16 case-only image renames need a manual check after a WinSCP synchronize.
  evidence: Local Windows names compare case-insensitively, so the server may keep only the old-case file; the runbook (story 7) should say to run check-live.ps1 and delete the old-case file by hand if a lowercase URL answers 404.
