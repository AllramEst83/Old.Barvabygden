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
