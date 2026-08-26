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

1. List root-level `bb-*.htm` / `bb-*.html` files and extract each one's issue
   number (from the filename, or from the page's own "Barva-Bladet nr N" title
   if the filename is irregular, e.g. `bb-49x.htm`).
2. Cross-reference against every issue number already present in
   `assets/pages/newsletter.html`. A root file whose number is **already**
   listed there is a stale leftover source, not a migration candidate — leave
   it alone (it may be worth flagging to the user for cleanup, but don't touch
   or delete it as part of this skill).
3. Among the remaining candidates, pick the **lowest** issue number.
4. Determine the year from the source content itself (look for an explicit
   "Nr X år YYYY" marker in the body text). Never guess the year from
   proximity to neighboring issues — if it's genuinely unclear, stop and ask
   rather than guessing (same rule as the archive-migration skill).

Report the chosen issue/year before proceeding, so the user can redirect if
it's not what they wanted.

---

## Step 1 — Structural migration

Follow `barva-news-archive-migration` for canonical paths/naming, and use an
already-migrated sibling issue from the same era as the literal template for
the modern HTML shell (doctype, Bootstrap 5 head, `.bb-topbar` back-link,
reader-mode button, `<main class="container-lg bb-issue my-3">` wrapper,
closing scripts) — e.g. `assets/barva-news/2008/barva-news-2008-issue-32.htm`.

Watch for content that legacy files sometimes hide **inside the malformed
`<head>`** (a logo `<table>` with the windmill/coat-of-arms images before
`</head>`) — that table is real page content and must move into `<main>`,
not get silently dropped when you extract "the body."

Rename any image with a space or URL-encoded character in its filename when
copying it into `assets/barva-news/<year>/images/issue-<N>/` (e.g.
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
5. **Back-link present**, pointing at `../../../index.html`.

---

## Step 4 — Update the newsletter page

Add the issue to `assets/pages/newsletter.html` following
`barva-news-archive-migration`'s card-format rules exactly (new year
accordion group if needed, `Format: HTML` / `PDF` / `HTML & PDF` as
appropriate, issues sorted within their year group).

---

## Step 5 — Clean up and report

Once Step 3 has verified the migrated issue is good (zero U+FFFD, case sane,
links resolve, back-link present) and Step 4's newsletter.html update is in
place, the root legacy source is fully superseded — remove it:

- Delete the root `bb-N.htm` (and its `.pdf` companion, if any) — it's now
  duplicated by the canonical archive copy.
- Delete every root image copied into
  `assets/barva-news/<year>/images/issue-<N>/`, after checksumming each root
  file against its archive copy to confirm it's an exact match (not just a
  same-named file).
- Use `git rm` for anything tracked (check with `git ls-files` first).
- Don't delete a root image if its filename is ambiguous/reused across
  issues and you haven't confirmed no *other* still-unmigrated issue depends
  on that exact root file — issue-numbered filenames (`33gras.gif`) are
  unambiguous; a generic name is not.
- Do **not** commit anything unless the user explicitly asks — this skill's
  job ends at a clean, verified working-tree change.
- Report: issue number and year chosen, files created, image count migrated,
  U+FFFD before → after (or "skipped, source was clean"), the case-sanity
  check result, the exact newsletter.html section added, and the list of
  root files deleted.
