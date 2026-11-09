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

## Refactors
- When moving content into `fragments/` or `pages/`, update all consumers (fetch targets, comments, loaders) and spot-check in browser.
