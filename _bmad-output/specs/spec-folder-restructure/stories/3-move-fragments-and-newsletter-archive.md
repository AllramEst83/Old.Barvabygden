---
title: 'Move fragments and newsletter archive'
type: 'chore'
created: '2026-11-09'
status: 'done'
baseline_commit: '25613be'
route: 'oneshot'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/SPEC.md'
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/migration-rules.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** `assets/pages` fragments and `assets/barva-news` still sit under `assets/`, mixing content with site code.

**Approach:** `git mv` `assets/pages/{events,newsletter}.html` to `fragments/` and `assets/barva-news/` to `barva-bladet/` (verified against `scripts/path-manifest.csv`, 207 rows), rewriting references per `migration-rules.md`. Link check stays at or under baseline. Never touch `web/` or `.ilosoft/`.

</frozen-after-approval>

## Implementation Notes

- Rewrites (byte-safe, Latin-1 round trip so legacy encodings survive): archive `../../../` -> `../../` and `../../(./)images` -> `../../assets/images` (42 issue pages); `fragments/newsletter.html` and `bladen.htm` `./assets/barva-news/` -> `./barva-bladet/`; `site.js`/`site-legacy.js` fetch paths -> `./fragments/`.
- Not in the rules, but needed to avoid new breakage: issue 47's `../../assets/barva-news/title/` -> `../title/`, and the title image path in root `bb-31…49x` pages (`./assets/barva-news/` -> `./barva-bladet/`).
- `stiftelsen-barva-bygdegard.htm` and its two `index.html` links stay for story 4 (live page move).
- `check-links.ps1` now resolves references in `fragments/` against the repo root, since they are injected into `index.html`; this removed 52 false positives, so the baseline fell 254 -> 202. No new broken references versus the path-mapped old baseline.
- Absolute `http://www.barvabygden.se/assets/barva-news/...` link in `Välkommen till Barva och Barvabygden.htm` left; the CAP-8 redirects cover it.
- Not verified in a browser (a local server run was blocked); verified by link check only. Subagent review skipped as before.
