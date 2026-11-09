# Target Layout

## Tree

```
/                              document root (unchanged)
├── index.html
├── .htaccess
├── .gitignore
├── README.md  CLAUDE.md  GEMINI.md
├── pages/                     standalone HTML pages (flat; filenames kept)
│   ├── kyrka.htm  skolan.htm  utst.htm  …   ← 19 index-linked legacy pages + other live root .htm
│   └── stiftelsen-barva-bygdegard.htm      ← from assets/pages/
├── fragments/                 fetch()-loaded partials
│   ├── events.html                         ← from assets/pages/
│   └── newsletter.html                     ← from assets/pages/
├── barva-bladet/              newsletter archive              ← from assets/barva-news/
│   ├── title/
│   └── <year>/  issue .htm/.pdf + images/issue-N/
├── assets/                    shared site code + curated media only
│   ├── css/
│   ├── js/
│   └── images/                grouped by topic (page-topic map in migration-rules.md)
│       ├── <existing: branding, hero, events/…, museum, skolan, barva-if, …>
│       ├── <new topics: kyrkan, kvarnen, gruvan, bron, folkdans, …>
│       └── shared/            images used by pages of 2+ topics
├── docs/                      NOT deployed
│   ├── project-documentation.md            ← assets/docs/PROJECT_DOCUMENTATION.md
│   ├── modernization-plan.md               ← assets/goals.md
│   └── todo.md                             ← assets/todo.md
├── scripts/                   NOT deployed                     ← assets/powershell/
│   ├── update-version.ps1  extract-broken-words.ps1
│   └── (new) build-manifest.ps1  rewrite-links.ps1  check-links.ps1
├── migration-sources/         NOT deployed — input for the newsletter migration skill
│   └── barva-bladet/          bb-37…46 .htm + their PDFs + images (flat); empties as issues migrate
├── attic/                     NOT deployed — quarantine (undecided items only)
│   ├── media/                 ~418 unreferenced root media
│   ├── pages/                 stale page copies, leftover bb-31/32/33/48/49x
│   ├── pdfs/                  root PDFs duplicated in barva-bladet/
│   ├── unused-images/         ← assets/unused-images/
│   ├── bilder-unreferenced/   onewebmedia/  coffee-beans/  upload.tmp/
│   └── README.md              what is here and why
├── _bmad/ _bmad-output/ .agents/ .claude/ .vscode/   NOT deployed (unchanged)
├── .ilosoft/                  untouched (left as-is)
└── web/                       untouched
```

## Root allowlist (CAP-1)

Files: `index.html`, `.htaccess`, `.gitignore`, `README.md`, `CLAUDE.md`, `GEMINI.md`.
Folders: those in the tree above. Anything else at the root fails CAP-1.

## Folder purposes

| Folder | Holds | Deployed |
|---|---|---|
| `pages/` | Full HTML documents a visitor navigates to | yes |
| `fragments/` | Partial HTML injected by `site.js` / `site-legacy.js` | yes |
| `barva-bladet/` | Newsletter issues with their own images | yes |
| `assets/` | CSS, JS, and images shared across pages | yes |
| `migration-sources/` | Pending newsletter issues waiting for the migration skill | no |
| `docs/`, `scripts/`, `attic/` | Repo-only material | no |

## Removed

- `provbilder/` (only `Thumbs.db`), `tools/` (empty), all `Thumbs.db`, `WS_FTP.LOG`.
