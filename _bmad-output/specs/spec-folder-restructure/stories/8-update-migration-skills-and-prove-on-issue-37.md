---
title: 'Update migration skills and prove on issue 37'
type: 'chore'
created: '2026-11-09'
status: 'done'
baseline_commit: '9ba8aa811cfb709d84df8c9f3daf2162a311f601'
route: 'dispatch'
review_loop_iteration: 0
context:
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/SPEC.md'
  - '{project-root}/_bmad-output/specs/spec-folder-restructure/migration-rules.md'
  - '{project-root}/.agents/skills/barva-news-migrate-next-issue/SKILL.md'
  - '{project-root}/.agents/skills/barva-news-archive-migration/SKILL.md'
---

<frozen-after-approval reason="human-owned intent — do not modify unless human renegotiates">

## Intent

**Problem:** The two newsletter-migration skills still assume sources sit at the repo root and say nothing about `migration-sources/`, the manifest-driven redirects, or cleanup. Issue 37 has not been migrated against the new layout (CAP-10).

**Approach:** Point both skills at `migration-sources/barva-bladet/` (input), `barva-bladet/<year>/` and `fragments/newsletter.html` (output); make "on completion" delete the consumed sources and repoint that issue's redirects via the manifest. Then follow the updated skills to migrate issue 37 (year 2011) end to end, with no manual path fixes.

## Boundaries & Constraints

**Always:** `.htaccess` redirect lines are generated, so repoint by editing the issue's rows in `scripts/path-manifest.csv` (new_path = archive path, class `migrated`) and re-running `scripts/build-redirects.ps1`. Delete a source image only when it is byte-identical to its archive copy and no other remaining source page references it. Use `git rm` for deletions. Keep skill text concise; do not duplicate the two skills' content. Follow the skills' existing rules (modern shell from a sibling issue, branding images swapped for `logan.gif`/`vapen.gif`, canonical names). Leave `web/` and `.ilosoft/` alone.

**Never:** Migrate any issue other than 37; edit `attic/` stale pages; hand-edit the generated block in `.htaccess`; invent a year (37 is 2011 per "Nr 1 år 2011" in the source); commit before verification passes.

## I/O & Edge-Case Matrix

| Scenario | Input / State | Expected Output / Behavior | Error Handling |
|----------|--------------|---------------------------|----------------|
| Happy path | `migration-sources/barva-bladet/bb-37.htm`, `Barva-Bladet-37.pdf`, 22 images | `barva-bladet/2011/barva-news-2011-issue-37.{htm,pdf}`, images in `images/issue-37/`, new 2011 group in `fragments/newsletter.html` between 2010 and 2016 | N/A |
| Moved-earlier images | `karta.gif`, `ljus.gif` referenced but not in the sources folder | `../../assets/images/maps/karta.gif`, `../../assets/images/scenery/light-post.gif` (git history: renamed there) | Report as resolved from history |
| Branding images | `logan.gif`, `vapen.gif` | `wind-mill-colorful-text.png` 187×145, `coat-of-arms.png` 105×130 | N/A |
| Title image | `./barva-bladet/title/barva-bladet-title.png` | `../title/barva-bladet-title.png` | N/A |
| Cleanup | consumed page, PDF, images | removed from `migration-sources/` after checksum match; other issues' files untouched | Keep any image another source page uses |
| Redirects | `/bb-37.htm`, `/Barva-Bladet-37.pdf` currently → `/` | → `/barva-bladet/2011/…issue-37.htm` / `.pdf` | N/A |
| Mojibake | source has entities, 0 U+FFFD | skip the fix-swedish agent | Verify count independently |

</frozen-after-approval>

## Code Map

- `.agents/skills/barva-news-migrate-next-issue/SKILL.md` -- Step 0 (candidate discovery at root), Step 1 sources, Step 5 cleanup; update to the new locations, add redirect repoint
- `.agents/skills/barva-news-archive-migration/SKILL.md` -- workflow steps 2, 4, 5, validation checklist; add source folder, title-image rule, redirect/manifest note
- `migration-sources/barva-bladet/` -- bb-37.htm (windows-1252 declared, entities only), `Barva-Bladet-37.pdf` (8 MB), images used only by bb-37
- `barva-bladet/2010/barva-news-2010-issue-36.htm` -- sibling template for the modern shell
- `fragments/newsletter.html` -- add `<!-- Year 2011 -->` group after the 2010 group (before line ~1576), mirroring the 2010 markup
- `scripts/path-manifest.csv`, `scripts/build-redirects.ps1`, `.htaccess` -- rows for `bb-37.htm` and `Barva-Bladet-37.pdf`; generated redirect block

## Tasks & Acceptance

**Execution:**
- [x] `.agents/skills/barva-news-migrate-next-issue/SKILL.md` -- sources from `migration-sources/barva-bladet/`; stale leftovers live in `attic/pages/`; cleanup deletes from the sources folder; repoint redirects via manifest + `build-redirects.ps1`; look up moved-earlier assets in the manifest/git history
- [x] `.agents/skills/barva-news-archive-migration/SKILL.md` -- same paths; title image `../title/…`; add redirect step and validation items
- [x] `barva-bladet/2011/barva-news-2011-issue-37.htm` and `.pdf`, `barva-bladet/2011/images/issue-37/*` -- migrate per the updated skills
- [x] `fragments/newsletter.html` -- 2011 year group with issue 37 (`Format: HTML & PDF`, download `Barva-Bladet-Nr37-2011.pdf`)
- [x] `migration-sources/barva-bladet/` -- `git rm` consumed bb-37 files after verification
- [x] `scripts/path-manifest.csv`, `.htaccess` -- repoint the two redirects, regenerate

**Acceptance Criteria:**
- Given the migrated issue, then 0 U+FFFD, sane å/ä/ö case, back-link `../../index.html`, and every `src`/`href` resolves (`check-links.ps1` at or under baseline).
- Given `fragments/newsletter.html`, then issue 37 appears once, under 2011, and every href exists.
- Given `migration-sources/barva-bladet/`, then bb-37's page, PDF and exclusive images are gone and issues 38–46 are untouched.
- Given `.htaccess`, then `/bb-37.htm` and `/Barva-Bladet-37.pdf` redirect to the archive URLs and no other redirect changes.
- Given a fresh read of both skills, then no instruction refers to root-level `bb-*.htm` or the old paths.

## Implementation Notes

- Issue 37 (year 2011) migrated by a one-off script that wrapped the source body in the shell of the 2010 issue-36 page: branding images swapped, title image `../title/…`, 21 images copied to `barva-bladet/2011/images/issue-37/`, `karta.gif` and `ljus.gif` pointed at `assets/images/maps/karta.gif` and `assets/images/scenery/light-post.gif` (git history), self-URL text updated. 0 U+FFFD; the page uses entities (231 `&aring;` vs 6 `&Aring;`), so no fix-swedish step.
- Two source hrefs `www.barvacruze.se` / `www.magner.se` were relative (broken); prefixed `http://` so the link check stays at baseline.
- New 2011 accordion group in `fragments/newsletter.html` after 2010.
- Cleanup: `git rm` of bb-37.htm, Barva-Bladet-37.pdf and 21 images (checksums matched; `1.jpg`–`6.jpg` first looked "used elsewhere" by a substring match; an exact `src` grep showed no other page uses them). 146 files remain in `migration-sources/barva-bladet/`.
- Redirects: manifest rows for `bb-37.htm` and `Barva-Bladet-37.pdf` set to the archive paths with class `migrated`; `build-redirects.ps1` changed exactly those two `.htaccess` lines. Manifest edit is the repoint mechanism because the block is generated; skills say not to re-run `build-manifest.ps1`.
- Skills: paths, title-image rule, moved-earlier lookup, cleanup and redirect repoint added to both.
- Link check at baseline (128). Not viewed in a browser; subagent review skipped. The `Barva-Bladet- 39.pdf`/`nr 44.pdf`-style duplicate names in the sources folder are unchanged.

## Verification

**Commands:**
- `./scripts/check-links.ps1` -- expected: no new broken references
- `./scripts/build-redirects.ps1; git diff .htaccess` -- expected: only the two repointed lines
- `./scripts/check-live.ps1 -ListOnly` -- expected: parses the repointed lines
