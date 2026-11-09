---
title: 'Docs, agent instructions and deploy runbook'
type: 'chore'
created: '2026-11-09'
status: 'done'
baseline_commit: 'f4db5d8'
route: 'oneshot'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/SPEC.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** CLAUDE.md, GEMINI.md, README.md, docs/ and .agents/ still describe the pre-move layout, and there is no written cutover procedure.

**Approach:** Update path references and layout descriptions (CAP-6), write `docs/deploy-runbook.md` (CAP-9), and run the CAP-7 workflow checks. Never touch `web/`, `.ilosoft/` or `bmad-*` skills.

</frozen-after-approval>

## Implementation Notes

- Mechanical replacements (`assets/barva-news` → `barva-bladet`, `assets/pages/*.html` → `fragments/`, `assets/powershell` → `scripts`, docs paths, archive depth `../../../` → `../../`) across CLAUDE.md, GEMINI.md, README.md, docs/ and non-bmad .agents files; then hand edits to the structure sections, rules, the assets-and-paths skill and the modernization plan. CAP-6 grep is clean.
- Dropped the CLAUDE.md claim that root legacy files are "intentionally left in place"; the root now holds only the allowlist.
- The two migration skills got path renames only; pointing them at `migration-sources/barva-bladet/` and the redirect repoint is story 8.
- Added `scripts/build-server-delete-list.ps1` and its output `scripts/server-delete-list.txt` (4 folders + 78 root pages/PDFs; root media never listed) because the runbook's step 4 depends on it.
- Runbook follows target-layout.md with topic folders instead of the stale `assets/images/legacy/` mention; adds pre-flight, rollback and the `check-live.ps1` step.
- CAP-7: `update-version.ps1` ran from the root (updated `index.html`; reverted afterwards); a local `http.server` returned 200 for index, both fragments, a page, an archive issue, the title image, site.js and a moved image. Events-card edit target (`fragments/events.html`) is what `site.js` fetches. Newsletter-issue migration is exercised by story 8 (issue 37). Not checked in a browser.
- Subagent review skipped; link check at baseline (128).
