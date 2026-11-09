# Image Inventory (state after the folder restructure, 2026-11-09)

Regenerate before work starts; numbers drift. Method: tracked files under `assets/images/` and `barva-bladet/`, `.jpg/.jpeg/.gif/.png/.webp`; "referenced" means the file name appears in `index.html`, `pages/`, `fragments/`, `barva-bladet/`, `assets/css` or `assets/js`.

**Drift warning:** counts below are as committed on `main` (issues 1–37 migrated). When this was written, issues 38–46 were being migrated in the working tree (staged, uncommitted), which adds their images under `barva-bladet/2011` to `2015` and removes the same files from `migration-sources/`. Re-run the survey after that lands, and apply the naming rules to those new issue images too.

## Totals

| Area | Files | Size |
|---|---|---|
| `assets/images/` | 213 | 114.6 MB |
| `barva-bladet/` (issue images) | 175 | 5.5 MB |
| Live total | 387 | 120 MB |
| `attic/` (out of scope) | 497 | 34.7 MB |

By type (live): png 99.7 MB, jpg 18.4 MB, gif 1.2 MB, webp 0.6 MB, jpeg 0.1 MB. No animated GIFs.

By size (live): under 100 KB 331 files (9.4 MB); 100–500 KB 35 (7.3 MB); 500 KB–2 MB 10 (8.2 MB); over 2 MB 11 (95.1 MB).

## Over 2000 px on a side (15 files, 94 MB)

| Size | Pixels | File | Referenced |
|---|---|---|---|
| 16.4 MB | 4000×2250 | `assets/images/museum/fence-2.png` | yes |
| 16.3 MB | 4000×2250 | `assets/images/museum/fence-1.png` | yes |
| 10.1 MB | 2752×1536 | `assets/images/events/midsommar/midsommar.png` | no |
| 10.0 MB | 2976×1428 | `assets/images/events/bingo/barva-bingo.png` | no |
| 9.4 MB | 2752×1536 | `assets/images/events/barvaloppet/barva-loppet.png` | no |
| 8.7 MB | 2816×1536 | `assets/images/events/family-evenings/barva-if-kids-playing.png` | no |
| 8.5 MB | 2912×1440 | `assets/images/events/loppis/bakluckeloppis-2026.png` | no |
| 8.3 MB | 2912×1440 | `assets/images/barva-if/bif-fotboll-2026.png` | no |
| 3.1 MB | 1792×2400 | `assets/images/events/christmas/2026/christmas-2026-background.jpg` | yes |
| 2.2 MB | 4340×960 | `assets/images/events/christmas/2026/swag_garland.jpg` | no |
| 0.8 MB | 2500×1667 | `assets/images/events/easter/easter-eggs-2026.jpg` | no |
| 0.3 MB | 2048×2895 | `assets/images/events/halloween/halloween-disco-2026.jpg` | no |
| 0.2 MB | 1367×2048 | `assets/images/events/seniorer/seniorer-barva.jpg` | no |
| 0.1 MB | 1447×2048 | `assets/images/events/family-evenings/family-evning-updated.jpg` | no |
| 0.1 MB | 2048×1152 | `assets/images/events/barvadagen-2025.jpeg` | no |

## Names

Live files with spaces 5, non-ASCII 3, uppercase 16 (all repo images: 37, 24, 58). 29 base names occur in more than one live folder. Examples: `assets/images/barva-if/BIF 1966.jpg`, `assets/images/skolan/skolbarn f.jpg`, `barva-bladet/2010/images/issue-35/bygdegården--73.jpg`, `.../vägen2.jpg`, `.../österlida2.jpg`, `.../Jesper.jpg`, `.../Gran1.GIF`, `assets/images/majstang/majst.jpg-for-web-NORMAL.jpg`.

## Duplicates (byte-identical, live)

36 groups, 43 extra files. Two kinds:

- Inside `assets/images/` (about 11 groups): e.g. `barva-framtid/bygdeg.jpg` = `shared/bygdeg.jpg`; `barvagarden/danshall.jpg` = `folkdans/danshall.jpg`; `kaffetaren/kaffetf.jpg` = `kaffetf48.jpg`; `skolan/skolbarnen.jpg` = `skolbarnen48.jpg`; `barva-if/bif46.gif` = `bif50.gif`; and the three restructure collisions `kyrkan/bilder/{dopfatm.gif,tavlaec.jpg}` and `museum/bilder/grf.jpg` against their twins in the parent folder.
- Archive issue copies of an `assets/images/` file or of another issue's file (about 25 groups), e.g. `bygdeg.jpg` in issues 3 and 29, `20-grangren.gif` in issues 17, 20, 25. These follow the self-contained issue-folder convention.

Across live, attic and migration-sources, 80 further duplicate groups exist; they are out of scope.

## Unreferenced live images (32 files, 63.5 MB) - decided: move to attic (CAP-8)

Moving them takes live weight from 120 MB to about 56 MB before any processing; the remaining weight is mostly `museum/fence-1.png` and `fence-2.png` (32.7 MB) and `christmas-2026-background.jpg` (3.1 MB). Not named in any page, fragment, archive issue, CSS or JS. Most are 2026 event art kept for future use: the six large PNGs and the event JPGs above, plus `barva-if/BIF 1966.jpg`, `bif-1.gif`, `barvagarden/{barvagarden-watercolor-2026.webp,br.gif}`, `beredda-tillsammans/beredda-tillsammans.webp`, `donations/swish.png`, `events/seniorer/pilangen.png` (2.1 MB), `kaffetaren/{kaffet,kaffet46}.jpg`, `lrf/lrf-logo-trans-bg.png`, `majstang/{maj,maj1,majst.jpg-for-web-NORMAL}.jpg`, `skolan/{Skolkort,skol,skol2,skolbarn f}.jpg`, and three issue-35 images with non-ASCII names.

## Tooling on this machine

PowerShell 5.1, Git Bash, Python and Node are present. Absent: `cwebp`, ImageMagick, Pillow. `.NET System.Drawing` can resize and recompress JPEG/PNG but cannot write WebP.

## References that must follow any change

`index.html`, `pages/*.htm`, `fragments/events.html`, `fragments/newsletter.html`, `barva-bladet/**/*.htm`, `assets/css/*.css` (2 image references), `assets/js` (none found). The `.htaccess` redirect block covers pages and PDFs only, not images.
