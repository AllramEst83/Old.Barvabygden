# Migration Rules

## Classification (drives the manifest, CAP-4)

| Source | Rule | Destination |
|---|---|---|
| Root `.htm`/`.html` linked from `index.html` or another live page | live page | `pages/` |
| Root stale copies (`old_index.htm`, `öld_index.html`, `barvaf (2).htm`, `bfa_temp.htm`) | stale | `attic/pages/` |
| Root `bb-31`, `bb-32`, `bb-33`, `bb-48.html`, `bb-49x` (issue already migrated) | stale leftover | `attic/pages/` |
| Root `bb-37` … `bb-46` (issue not yet migrated) | pending source | `migration-sources/barva-bladet/` |
| Root PDFs for pending issues 37–46 | pending source | `migration-sources/barva-bladet/` |
| Root PDFs for migrated issues | duplicate | `attic/pdfs/` |
| Root media referenced by a pending source only | pending source | `migration-sources/barva-bladet/` |
| Root media referenced by a live page | referenced | `assets/images/<topic>/` (see map) |
| Root media referenced by both a live page and a pending source | split | topic folder **and** a copy in `migration-sources/barva-bladet/` |
| Root media referenced by nothing (~418 files) | orphan | `attic/media/` |
| Other root PDFs referenced by live pages | referenced | `assets/files/`* |
| `bilder/` referenced files / unreferenced files | split | `assets/images/<topic>/` / `attic/bilder-unreferenced/` |
| `assets/pages/events.html`, `newsletter.html` | fragment | `fragments/` |
| `assets/pages/stiftelsen-barva-bygdegard.htm` | page | `pages/` |
| `assets/barva-news/**` | archive | `barva-bladet/**` |
| `assets/docs/`, `assets/goals.md`, `assets/todo.md` | docs | `docs/` |
| `assets/powershell/*` | tooling | `scripts/` |
| `assets/unused-images/`, `onewebmedia/`, `images/coffee-beans/`, `upload.tmp/` | junk | `attic/…` |

\* Referenced non-image root PDFs: the destination folder is fixed when the manifest is built. Default is `assets/files/`.

"Referenced" means: matched by href/src/background/CSS `url()` in any live page, fragment, archive issue or JS file, compared case-insensitively and URL-decoded.

Pending sources keep the flat layout the skill expects (page, PDF and images side by side). Their references are left unchanged.

## Page-topic map (image grouping)

An image's topic is the topic of the live page or pages that reference it. If it is used by pages from two or more topics, it goes to `shared/`. This map is a proposal; it is confirmed when the manifest is reviewed.

| Live page(s) | Topic folder under `assets/images/` |
|---|---|
| `kyrka.htm`, `kyrkan.htm` | `kyrkan/` |
| `skolan.htm`, `skolm.htm` | `skolan/` (exists) |
| `utst.htm` | `museum/` (exists) |
| `prost-sk.htm` | `prostokna/` |
| `hagaborg.htm` | `hagaborg/` |
| `spackelf.htm` | `spackelfabriken/` |
| `bron.htm` | `bron/` |
| `gruv.htm` | `gruvan/` |
| `bankl.htm` | `banklekmen/` |
| `folkd.htm`, `dans*.htm`, `danser*.htm` | `folkdans/` |
| `bfa.htm`, `bf.htm` | `barva-framtid/` |
| `lucia.htm` | `lucia/` |
| `luren.htm` | `luren/` |
| `tromb.htm` | `tromben/` |
| `gardsg.htm` | `gardsgrupper/` |
| `macken.htm`, `motor.htm` | `macken/` |
| `bib.htm` | `biblioteket/` |
| `barvaif_2011.htm` | `barva-if/` (exists) |
| `vatten.htm` | `vatten/` |
| `bdag-08.htm`, `fnv-08.htm` | `events/` (exists) |
| `index.html`, `barva.htm`, `bh.htm`, `bladen.htm`, `enket.htm`, `Välkommen till Barva och Barvabygden.htm` | `shared/` |

The manifest-review step catches any page missing from this map. Images already under `assets/images/` stay where they are.

## Manifest

`scripts/build-manifest.ps1` writes `scripts/path-manifest.csv` with the columns `old_path,new_path,class`. It is generated, reviewed and committed before any move. Every later step reads the manifest; nothing hard-codes paths.

## Reference rewrites (CAP-3)

- `pages/*`: prefix shared paths with `../` (`../assets/…`, `../index.html`). Rewrite media references to their manifest destinations.
- `barva-bladet/<year>/*`: depth drops by one. `../../../assets/` → `../../assets/`, `../../images/` → `../../assets/images/`, `../../../index.html` → `../../index.html`. `./images/` and `../title/` stay unchanged.
- `index.html`: `./bankl.htm` → `./pages/bankl.htm` (all 19), `/assets/pages/stiftelsen…` → `./pages/stiftelsen…`, and `./assets/barva-news/` → `./barva-bladet/` (also inside `fragments/newsletter.html`).
- `site.js`, `site-legacy.js`: `./assets/pages/{events,newsletter}.html` → `./fragments/…`.
- Pre-existing broken references (`fnv-08-filer/`, `Välkommen…-filer/`) are recorded in the baseline and left unchanged.

## Phases (one commit each, on `feature/folder-restructure`)

1. Baseline: add `scripts/check-links.ps1`, record the current broken-reference list.
2. Tooling and docs: `assets/powershell` → `scripts/`, docs → `docs/`. Junk removal and `.gitignore`.
3. Manifest: build and review `path-manifest.csv`.
4. Fragments and newsletter archive: move them and rewrite references.
5. Live pages and referenced media: move them and rewrite references.
6. Quarantine: move orphans and stale files to `attic/`, write `attic/README.md`.
7. Exposure: add the `.htaccess` 404 rule for non-deployed folders.
8. Redirects: `scripts/build-redirects.ps1` generates the `.htaccess` 301 block from the manifest.
9. Docs, agent instructions and runbook: update path references (CAP-6), write `docs/deploy-runbook.md` (CAP-9) and run the CAP-7 workflow checks.
10. Migration skill: update `barva-news-migrate-next-issue` and `barva-news-archive-migration` (CAP-10).
    - Source: `migration-sources/barva-bladet/`.
    - Output: `barva-bladet/<year>/` and `fragments/newsletter.html`.
    - On completion: delete the consumed sources from the sources folder, and change that issue's `.htaccess` redirect from `/` to the migrated page.
    - Prove it by migrating issue 37.

## Follow-up (after this restructure, not in scope)

Normalize image names, remove duplicate images, compress/resize, convert to WebP.

The link check must pass the baseline at the end of every phase from 4 onward.

## Redirects (CAP-8)

- One explicit line per moved page and per moved newsletter PDF, generated from the manifest and placed between marker comments in `.htaccess`:
  `Redirect 301 /kyrka.htm /pages/kyrka.htm`
- Normalized names redirect from the URL-encoded old path to the new name.
- Pages moved to `attic/` redirect to `/`.
- Media files get no redirects. Their old copies stay on the server untouched, so external image links keep working.
- `Redirect` (mod_alias) fires whether or not an old copy is still on the server.

## Non-deployed exposure

```apache
# Repo-only folders
RedirectMatch 404 ^/(docs|scripts|attic|migration-sources|_bmad|_bmad-output|web)(/|$)
# Dot-folders (.agents, .claude, .git, .ilosoft, .vscode) — but keep .well-known for SSL/ACME
RedirectMatch 404 ^/\.(?!well-known)
# Repo-only file types anywhere (README/CLAUDE/GEMINI.md, scripts, manifest, logs)
RedirectMatch 404 \.(md|ps1|csv|log)$
```

Apache already refuses to serve `.htaccess` itself. These rules are the second safeguard: the WinSCP exclusion mask is the first, and stops the files being uploaded at all. Verify after cutover by requesting `/docs/`, `/attic/`, `/README.md` and `/.agents/`. Each should return 404.

## Cutover runbook (CAP-9) — `docs/deploy-runbook.md`

WinSCP over SSH to one.com. The user compares local against remote and checks or unchecks what to upload.

1. Synchronize (local → remote) with these options:
   - **Exclusion file mask:** `| docs/; scripts/; attic/; migration-sources/; _bmad/; _bmad-output/; .agents/; .claude/; .vscode/; .ilosoft/; web/; *.md; *.ps1; .git*`. Repo-only folders, including the ~420 `attic/` files, then never appear in the comparison list.
   - **"Delete files" off.** Otherwise the old root media that exist only on the server would be offered for deletion.
2. In the comparison preview, expect new files under `pages/`, `fragments/`, `barva-bladet/` and `assets/images/legacy/`, plus changed `index.html`, `assets/js/site*.js` and `.htaccess`. Check all of them. Check `.htaccess` last, or upload it on its own after the rest.
3. Verify: run the CAP-8 check (old page URLs → 301 → 200) and spot-check the home page, the fragments and one newsletter issue.
4. Delete the old root page copies and the old `assets/pages/`, `assets/barva-news/`, `assets/powershell/` and `assets/docs/` folders on the server, using the manifest-derived list (`scripts/server-delete-list.txt`). Leave root media files in place.
5. Re-run step 3.
