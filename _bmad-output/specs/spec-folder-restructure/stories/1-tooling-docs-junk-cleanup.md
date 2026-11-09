---
title: 'Tooling, docs and junk cleanup with link-check baseline'
type: 'chore'
created: '2026-11-09'
status: 'done'
route: 'oneshot'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/SPEC.md'
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/migration-rules.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** Later restructure phases need a way to prove no local references break, and the repo carries tooling/docs under `assets/` plus tracked junk (`Thumbs.db`, `WS_FTP.LOG`, `provbilder/`, empty `tools/`).

**Approach:** Add `scripts/check-links.ps1` and record the pre-move broken-reference baseline; `git mv` `assets/powershell/*` to `scripts/` and docs to `docs/` (`PROJECT_DOCUMENTATION.md` to `docs/project-documentation.md`, `goals.md` to `docs/modernization-plan.md`, `todo.md` to `docs/todo.md`); remove and gitignore junk. One commit on `feature/folder-restructure`. Never touch `web/` or `.ilosoft/`.

</frozen-after-approval>

## Implementation Notes

- Baseline recorded in `scripts/link-baseline.txt`; the check must stay at or under it.
- Path references in CLAUDE.md/README/docs/.agents are updated in story 7, not here; only the moved scripts' own self-references are fixed.

- `docs/todo.md` was gitignored (`assets/todo.md`), so it was moved with a plain `mv` and the ignore entry updated to `docs/todo.md`.
- Baseline: 254 broken references (146 in the archive, rest in root pages incl. the known `*-filer/` folders).
- Blind Hunter subagent layer skipped (no explicit permission to spawn subagents in this session); self-reviewed instead.
