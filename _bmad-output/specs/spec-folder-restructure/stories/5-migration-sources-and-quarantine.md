---
title: 'Migration sources and quarantine'
type: 'chore'
created: '2026-11-09'
status: 'done'
baseline_commit: '2c4cb05131314b2413913053ce7d6162c1980d66'
route: 'oneshot'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/SPEC.md'
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/target-layout.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Pending newsletter sources (bb-37…46), orphan media, stale pages and junk folders still clutter the root, so it does not match the CAP-1 allowlist.

**Approach:** Run `scripts/rewrite-links.ps1` for classes `pending-source,stale,orphan,duplicate,junk` (pending and stale sources keep their references unchanged), copy the one `split` image into `migration-sources/barva-bladet/`, and write `attic/README.md`. Never touch `web/` or `.ilosoft/`.

</frozen-after-approval>

## Implementation Notes

- Ran `rewrite-links.ps1 -Classes pending-source,stale,orphan,duplicate,junk`: 684 files moved, 0 references rewritten (pending/stale sources are skipped by design). Empty leftover dirs (`bilder/`, `images/`, `onewebmedia/`, `upload.tmp/`, `assets/unused-images/`, `assets/pages/`) removed.
- `ababok1.jpg` (split) copied from `assets/images/shared/` to `migration-sources/barva-bladet/` (169 files there now). Note: the manifest still lists it as a `split` row.
- Root now holds only `.gitignore`, `.htaccess`, `CLAUDE.md`, `GEMINI.md`, `README.md`, `index.html` (tracked files); `attic/README.md` written.
- Link check baseline 217 -> 128: stale and pending sources left the checked scope (89 entries gone), none new. No live file references `attic/` or `migration-sources/`.
- Not browser-tested; subagent review skipped.
