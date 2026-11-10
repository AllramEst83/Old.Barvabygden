---
title: 'Image redirects and live check'
type: 'feature'
created: '2026-11-09'
status: 'done'
route: 'oneshot'
review_loop_iteration: 0
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Renamed, merged and format-changed images changed URL, so old links, bookmarks and search results would 404 after upload (CAP-6).

**Approach:** Extend `scripts/build-redirects.ps1` with a second marker block of 301s generated from `scripts/image-manifest.csv` (rename, merge, convert rows whose URL changed; none for attic moves), and extend `scripts/check-live.ps1` so the image redirects are checked (301, then 200).

</frozen-after-approval>

## Implementation Notes

- `build-redirects.ps1` now writes `# BEGIN image redirects` to `# END image redirects` right after the page block: 76 `Redirect 301` lines (8 renames, 8 merges, 60 convert rows whose URL changed, of which 33 are opaque PNG to JPG). Sources keep the original characters (spaces and non-ASCII), targets are %-encoded, like the page block. Output is sorted; a second run leaves `.htaccess` unchanged. The script exits 1 if a target is not a tracked file, or if a redirect source is a live file or a prefix of one (Apache `Redirect` matches by prefix).
- Case-only changes (`Skolkort.jpg` to `skolkort.jpg`) get redirects too; the server is case-sensitive.
- `check-live.ps1` already read every `Redirect 301` line, so the image block is checked for 301 and 200 without a new parser. It now reports image redirects separately and requests three `.webp` files (first, middle, last image redirect target) that must answer 200 with Content-Type `image/webp`.
- Added `AddType image/webp .webp` and `ExpiresByType image/webp` to `.htaccess` (deferred from the story 4 and 5 reviews). No server delete list: the WinSCP synchronize with "Delete files" removes the old copies.
- Not run against the live site: that needs an upload first (`check-live.ps1 -ListOnly` parses the 206 redirects and 3 WebP samples). Run `scripts/check-live.ps1` after uploading.

## Review Triage Log

- 16 case-only renames (`Gran1.GIF` to `gran1.gif`, `Jesper.jpg`, ...) may not be picked up by a WinSCP synchronize run from Windows, because local names compare case-insensitively: the server could keep only the old-case file, and pages that now ask for the lowercase name would 404. Medium, defer to story 7 (deploy runbook): after upload, run `check-live.ps1` (it fails if a lowercase target answers 404); if one does, delete the old-case file on the server by hand and upload the new one. The redirects themselves are correct for a case-sensitive host.
- WebP content-type check samples only 3 files: low, no change. `AddType` is a single server setting, so three samples prove it; wrapping it in `<IfModule>` would hide a missing module.
- Quarantined image URLs are not asserted to answer 404, redirect-ordering comment, image-specific failure totals, no wildcard fallback for `bilder/` folders: low, no change. Spec decision: no redirects for attic moves; the build script already refuses a redirect source that is a live file or a prefix of one, and no chains or loops exist (checked: every target is a tracked file and no target is a source).
- Spec has no verification section: low, patched by the post-upload check above and the Implementation Notes (`check-live.ps1` is the acceptance run and needs an upload first).
