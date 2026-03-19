---
name: assets-and-paths
description: >-
  Where new static files go and how to link them. Use when adding images,
  extra pages, CSS, or JS, or when fixing broken relative URLs.
---

# Assets and paths (Barvabygden)

## Placement
- Put **new** non-entry assets under `assets/` (e.g. `assets/images/…`, `assets/pages/…`, styles/scripts if you add them there).
- Avoid new loose files at repo root unless the task is explicitly “main page only” or legacy compatibility.

## Linking
- From **`index.html` (repo root)**: prefix with `./assets/...` (or `/assets/...` if the server is rooted at site root).
- From **`assets/pages/*.html`**: paths to images are often `./assets/images/...` **from site root** in injected fragments; if a fragment is loaded into `index.html`, resolve paths **as the main document expects** (usually root-relative `./assets/` from `index.html`). If a standalone file is opened from `assets/pages/`, adjust accordingly—prefer one canonical strategy per include.
- Prefer **consistent** URL style within a file; fix broken `src`/`href` after moves.

## Refactors
- When moving content into `assets/pages/`, update all consumers (fetch targets, comments, loaders) and spot-check in browser.
