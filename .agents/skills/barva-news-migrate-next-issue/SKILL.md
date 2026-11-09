---
name: barva-news-migrate-next-issue
description: Orchestrate migrating one Barva-Bladet issue end to end — pick the next un-migrated issue (or a given issue number), run the barva-news-archive-migration workflow, conditionally run the fix-swedish-questionmarks-agent only if the source is actually corrupted, and verify the result before touching newsletter.html. Use when asked to migrate the next Barva-Bladet issue, continue the newsletter archive migration, or process legacy issues one at a time.
---

# Migrate Next Barva-Bladet Issue

A thin **orchestrator** over two existing playbooks. It does not re-explain their
mechanics — it decides *which issue*, *whether* the mojibake step is needed, and
*how to verify* the result independently rather than trusting a sub-step's
self-report.

- Mechanics of moving files/images/links: see [[barva-news-archive-migration]]
  (`.agents/skills/barva-news-archive-migration/`).
- Mechanics of fixing corrupted Swedish text: see the fix-swedish-questionmarks
  agent (`.agents/agents/fix-swedish-questionmarks-agent.mdc`).

Don't rely on any static inventory of "already-migrated issues" — the archive
grows with each run and any such list goes stale immediately. Always
re-derive current state live, per Step 0.

---

## Step 0 — Pick the target issue

If the user names an issue number, use it. Otherwise find "the next one":

1. List the `bb-*.htm` / `bb-*.html` files in `migration-sources/barva-bladet/`
   (the pending sources; page, PDF and images sit side by side, flat) and
   extract each one's issue number (from the filename, or from the page's own
   "Barva-Bladet nr N" title if the filename is irregular).
2. Cross-reference against every issue number already present in
   `fragments/newsletter.html`. A source whose number is **already** listed
   there is a stale leftover, not a migration candidate — leave it alone and
   flag it to the user (already-migrated leftovers normally live in
   `attic/pages/`, not here).
3. Among the remaining candidates, pick the **lowest** issue number.
4. Determine the year from the source content itself (look for an explicit
   "Nr X år YYYY" marker in the body text). Never guess the year from
   proximity to neighboring issues — if it's genuinely unclear, stop and ask
   rather than guessing (same rule as the archive-migration skill).

Report the chosen issue/year before proceeding, so the user can redirect if
it's not what they wanted.

---

## Step 1 — Structural migration

Read the source from `migration-sources/barva-bladet/` and write to
`barva-bladet/<year>/`. Follow `barva-news-archive-migration` for canonical paths/naming, and use an
already-migrated sibling issue from the same era as the literal template for
the modern HTML shell (doctype, Bootstrap 5 head, `.bb-topbar` back-link,
reader-mode button, `<main class="container-lg bb-issue my-3">` wrapper,
closing scripts) — e.g. `barva-bladet/2008/barva-news-2008-issue-32.htm`.

Watch for content that legacy files sometimes hide **inside the malformed
`<head>`** (a logo `<table>` with the windmill/coat-of-arms images before
`</head>`) — that table is real page content and must move into `<main>`,
not get silently dropped when you extract "the body."

A reference that is not found next to the page may have been moved earlier:
look the file name up in the `old_path` column of `scripts/path-manifest.csv`
(or `git log --follow`) and point at its current location under
`assets/images/` (e.g. `karta.gif` → `../../assets/images/maps/karta.gif`).
Title images (`./barva-bladet/title/…`) become `../title/…`.

Rename any image with a space or URL-encoded character in its filename when
copying it into `barva-bladet/<year>/images/issue-<N>/` (e.g.
`33 1926.jpg` → `33-1926.jpg`), and update every reference to match. Swap the
legacy `logan.gif`/`vapen.gif` references for the shared
`assets/images/branding/wind-mill-colorful-text.png` /
`coat-of-arms.png`, using the same width/height convention already used by
sibling issues (187×145 / 105×130) rather than the old file's own declared
size.

---

## Step 2 — Decide whether mojibake-fixing is needed

Count U+FFFD in the newly created archive file:

```powershell
$t = [System.IO.File]::ReadAllText("<path>", [System.Text.Encoding]::UTF8)
($t.ToCharArray() | Where-Object { [int]$_ -eq 0xFFFD }).Count
```

- **Zero** → skip the fix-swedish-questionmarks-agent entirely. Some sources
  (e.g. issues 36, 47–52) use HTML entities (`&aring;`, `&ouml;`, …) or were
  never corrupted, and never need this step.
- **Non-zero** → delegate to the fix-swedish-questionmarks-agent. Read that
  file's "CRITICAL" section on PowerShell variable-name collisions before
  running anything — a prior run silently uppercased nearly every å/ä/ö in a
  whole issue because `$a`/`$A` are the *same* PowerShell variable. The agent
  file's example script and sanity check already guard against this; don't
  skip the sanity-check line.

---

## Step 3 — Verify independently (don't trust the sub-step's self-report)

Whichever path Step 2 took, check the result yourself before moving on:

1. **Zero U+FFFD remain.** Use the Grep tool or a fresh `ReadAllText` scan —
   not the same script that just ran, in case it has a self-consistent bug.
2. **Case sanity.** Count lowercase vs. uppercase å/ä/ö independently (Grep
   tool, one pattern per letter/case). Lowercase should vastly outnumber
   uppercase in normal prose. If uppercase is anywhere near parity with
   lowercase, the case-collision bug (or something like it) has struck again —
   do not proceed to Step 4 until this is fixed.
3. **Word-initial vs. sentence-initial capitals.** A letter-reconstruction
   pass will correctly capitalize a word's first letter but can't tell
   word-initial from sentence-initial. Spot-check with:
   ```
   grep -n "\b[A-Z][ÅÄÖ]\b" <file>          # 2-letter words: på/då/får/så/…
   ```
   Any hit that is **not** immediately after `.`/`!`/`?`/`:` (skipping
   whitespace, `&nbsp;`, and HTML tags) or the start of a paragraph is
   mid-sentence and should have only its first letter capitalized, if any
   (e.g. `PÅ` mid-sentence → `på`; `PÅ` at a real sentence start → `På`).
   Genuine multi-word ALL-CAPS headings (`RIKSTEATERN PÅ VISIT I BARVA`) are
   the one case to leave alone.
4. **Links resolve.** Every `src`/`href` added or changed in this issue
   points to a file that actually exists on disk.
5. **Back-link present**, pointing at `../../index.html`.

---

## Step 4 — Update the newsletter page

Add the issue to `fragments/newsletter.html` following
`barva-news-archive-migration`'s card-format rules exactly (new year
accordion group if needed, `Format: HTML` / `PDF` / `HTML & PDF` as
appropriate, issues sorted within their year group).

---

## Step 5 — Clean up and report

Once Step 3 has verified the migrated issue is good (zero U+FFFD, case sane,
links resolve, back-link present) and Step 4's newsletter.html update is in
place, the pending source is fully superseded — remove it from
`migration-sources/barva-bladet/`:

- Delete `bb-N.htm` (and its `.pdf` companion, if any) — it's now duplicated
  by the canonical archive copy.
- Delete every source image copied into
  `barva-bladet/<year>/images/issue-<N>/`, after checksumming each one
  against its archive copy to confirm it's an exact match (not just a
  same-named file).
- Use `git rm` for anything tracked.
- Don't delete an image if another remaining `bb-*.htm` in that folder still
  references it. Generic names (`1.jpg`) need a real check: grep the other
  pages for the exact `src` value, not a substring.
- **Repoint the redirects.** The redirect block in `.htaccess` is generated,
  so don't edit it. In `scripts/path-manifest.csv` set, for the issue's page
  and PDF rows (old_path `bb-N.htm`, `Barva-Bladet-N.pdf` etc.), `new_path` to
  the archive path and `class` to `migrated`, then run
  `./scripts/build-redirects.ps1`. Only those issue's lines should change in
  `git diff .htaccess` (from `/` to the migrated page). Don't re-run
  `scripts/build-manifest.ps1`; the manifest is the record of the moves now.
- Do **not** commit anything unless the user explicitly asks — this skill's
  job ends at a clean, verified working-tree change.
- Report: issue number and year chosen, files created, image count migrated,
  U+FFFD before → after (or "skipped, source was clean"), the case-sanity
  check result, the exact newsletter.html section added, and the list of
  source files deleted and the redirect lines repointed.
