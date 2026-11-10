---
title: 'Image redirects and live check'
type: 'feature'
created: '2026-11-09'
status: 'in-review'
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
