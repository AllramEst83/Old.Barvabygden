---
title: 'htaccess protection and redirects'
type: 'chore'
created: '2026-11-09'
status: 'done'
baseline_commit: 'c0f5bc1ee490c3fc9e2382b0bf4a4ba6b6be66a2'
route: 'oneshot'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/SPEC.md'
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/migration-rules.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** After the cutover, repo-only folders and files could be reached if uploaded, and old page and newsletter-PDF URLs would 404.

**Approach:** Add the three 404 `RedirectMatch` rules from `migration-rules.md` to `.htaccess`; add `scripts/build-redirects.ps1`, which generates one `Redirect 301` line per moved page/newsletter PDF from `scripts/path-manifest.csv` between marker comments (pages moved to `attic/` or `migration-sources/` redirect to `/`; no media redirects); add `scripts/check-live.ps1` to verify old URL → 301 → new URL 200 and the 404s after cutover. Never touch `web/` or `.ilosoft/`.

</frozen-after-approval>

## Implementation Notes

- `.htaccess`: three 404 `RedirectMatch` rules inserted after the HTTPS block; generated block (130 `Redirect 301` lines, quoted so names with spaces/non-ASCII work, targets %-encoded) between BEGIN/END markers at the end. Re-running `build-redirects.ps1` is idempotent (hash-verified).
- Redirect scope: `.htm/.html/.pdf` rows except fragment/tooling/docs/junk. Targets under `attic/` or `migration-sources/` (stale pages, pending issues 37–46 and their PDFs, duplicate/orphan PDFs) redirect to `/`; CAP-10 repoints an issue's line when it is migrated.
- `check-live.ps1` parses those lines, checks 301 + Location + 200 on the new URL, the home page, and 404 for ten repo-only URLs. Only the `-ListOnly` parse path was run; it makes real HTTP requests otherwise and the site is not cut over yet. The `RedirectMatch 404` syntax and one.com's mod_alias support are unverified until cutover.
- `scripts/server-delete-list.txt` (runbook step) is not produced here; left for story 7.
- Link check unchanged at baseline 128; subagent review skipped.
