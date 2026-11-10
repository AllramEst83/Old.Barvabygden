---
name: assets-and-paths
description: >-
  Where new static files go and how to link them. Use when adding images,
  extra pages, CSS, or JS, or when fixing broken relative URLs.
---

# Assets and paths (Barvabygden)

## Placement
- Put **new** non-entry assets under `assets/` (e.g. `assets/images/<topic>/…`, styles/scripts if you add them there); standalone pages go in `pages/` and fetched fragments in `fragments/`.
- Avoid new loose files at repo root unless the task is explicitly “main page only” or legacy compatibility.

## Linking
- From **`index.html` (repo root)**: prefix with `./assets/...` (or `/assets/...` if the server is rooted at site root).
- From **`fragments/*.html`**: paths are `./assets/...` or `./barva-bladet/...` **from site root**, because fragments are injected into `index.html` and resolve as the main document expects.
- From **`pages/*.htm`**: prefix shared paths with `../` (`../assets/images/<topic>/…`, `../index.html`, `../barva-bladet/…`); links to sibling pages stay bare (`skolan.htm`).
- From **`barva-bladet/<year>/*.htm`**: `../../assets/…`, `../../index.html`; the issue's own images are `./images/issue-<N>/…`.
- Prefer **consistent** URL style within a file; fix broken `src`/`href` after moves.

## Image rules
- **Names**: lowercase ASCII with hyphens, `^[a-z0-9]+(-[a-z0-9]+)*\.(webp|jpg|png|gif|ico)$`. Transliterate å/ä to a and ö to o; no spaces, underscores or uppercase; `.jpeg` becomes `.jpg`. On a clash in one folder add a numeric suffix.
- **Size and format**: long edge at most 1920 px, file at most 500 KB. Photos are JPG (a photo saved as PNG becomes JPG); PNG only for transparency or line art; GIFs and `.ico` stay as they are.
- **WebP**: every `.jpg`/`.png` has a `.webp` sibling. Markup keeps all `<img>` attributes on the inner `<img>`:
  ```html
  <picture>
    <source type="image/webp" srcset="../assets/images/museum/fence-1.webp">
    <img src="../assets/images/museum/fence-1.jpg" alt="..." width="..." height="...">
  </picture>
  ```
  CSS `url()` and lightbox `data-full` use the fallback only. `picture` is `display: contents` in `site.css`.
- **Adding images**: name them, run `./scripts/optimize-images.ps1 -Folder <dir>` (resizes in place, writes the `.webp`), `git add` the files, run `./scripts/apply-image-pictures.ps1` (wraps the `<img>` tags), then `./scripts/check-images.ps1`, which must pass. Do not add a hand-written `<picture>` unless the `.webp` exists.
- **Renaming or removing**: a published image that changes URL needs a redirect: add a row to `scripts/image-manifest.csv` (action `rename`, old and new path) and run `./scripts/build-redirects.ps1`. Unreferenced images move to `attic/unreferenced-images/<original path>`; no redirect.

## Refactors
- When moving content into `fragments/` or `pages/`, update all consumers (fetch targets, comments, loaders) and spot-check in browser.
