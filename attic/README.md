# attic/

Quarantine for files that no live page references or that are stale. Nothing here is deployed
(WinSCP exclusion mask plus a `.htaccess` 404 rule), and nothing is deleted yet: the fate of each
item is decided later. The old → new path of every file is in `scripts/path-manifest.csv`
(classes `orphan`, `stale`, `duplicate`, `junk`).

| Folder | What it holds | Why |
|---|---|---|
| `media/` | ~410 root images (jpg/gif/png) | Referenced by no live page, fragment, archive issue, CSS or JS. Some are used only by stale pages. |
| `bilder-unreferenced/` | Unreferenced files from the old `bilder/` folder | Same rule as `media/`. |
| `pages/` | `old_index.htm`, `öld_index.html`, `barvaf (2).htm`, `bfa_temp.htm`, and leftovers `bb-31`, `bb-32`, `bb-33`, `bb-48`, `bb-49x` | Stale page copies; the leftover issues are already migrated into `barva-bladet/`. Their references are left as they were and may be broken. |
| `pdfs/` | Root newsletter PDFs outside issues 37–46, plus unreferenced PDFs | Duplicates of PDFs in `barva-bladet/`, or unreferenced. |
| `unused-images/` | Former `assets/unused-images/` | Images the site no longer uses. |
| `onewebmedia/`, `coffee-beans/`, `upload.tmp/` | Former site-builder leftovers | Junk from the old hosting setup. |

Pending newsletter issues 37–46 are not here; they wait in `migration-sources/barva-bladet/`.
