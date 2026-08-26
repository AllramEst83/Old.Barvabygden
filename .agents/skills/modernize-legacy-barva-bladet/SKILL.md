---
name: modernize-legacy-barva-bladet
description: >-
  Apply minimal modernization to a legacy Barva-Bladet issue HTML file: add a
  Bootstrap/site.css head baseline, set body.bb-legacy, insert a .bb-topbar
  backlink, and wrap the content in a readable container. Use when asked to
  modernize, update, or improve readability of a legacy Barva-Bladet issue page
  under assets/barva-news/, or before applying the reader-mode skill.
disable-model-invocation: true
---

# Modernize Legacy Barva‑Bladet Issue

Minimal, repeatable edits for legacy issues under `assets/barva-news/<year>/`.

## Hard constraints

- Do **not** rewrite, reflow, or re-author newsletter content.
- Do **not** delete legacy tables/`<font>`/Word markup.
- Do **not** change `src`/`href` paths (except adding the backlink wrapper).
- Do **not** move files or update the archive listing page.

---

## Four mechanical edits

### 1 — `<html>` and `<head>`

Ensure the document opens with:

```html
<!doctype html>
<html lang="sv">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../../../assets/css/site.css">
```

Keep any tiny in-file legacy `<style>` blocks — do not expand them.

### 2 — `<body>`

Set:

```html
<body class="bb-legacy">
```

Keep any legacy `bgcolor` attribute if present.

### 3 — Backlink (immediately inside `<body>`)

```html
<div class="container-lg bb-topbar">
  <a href="../../../index.html" class="back-link">
    ← Tillbaka till Barvabygden.se
  </a>
</div>
```

### 4 — Main content wrapper

Wrap all existing legacy content (everything after the backlink) in:

```html
<main class="container-lg bb-issue my-3">
  ...existing legacy content...
</main>
```

Do not restructure anything inside the wrapper.

---

## CSS requirement

Confirm `assets/css/site.css` contains the `body.bb-legacy` scoped block with:

- `.bb-issue` background + padding
- `body.bb-legacy p, body.bb-legacy .MsoNormal` — line-height, spacing, `text-align: left !important`
- `body.bb-legacy img { max-width: 100%; height: auto; }`
- `body.bb-legacy table` — mobile overflow handling
- `body.bb-legacy .bb-topbar` — spacing
- `body.bb-legacy .back-link` — pill/button styling + focus state

If the block is missing, add it at the end of `site.css`.

---

## Sanity checks

- Exactly one `<html>`, one `<head>`, one `<body>`.
- Backlink path is `../../../index.html`.
- No image `src` paths were changed.

---

## Next step: Reader Mode (optional)

If the task also requests a reading view / läsläge, apply the `reader-mode`
skill after these four edits. It requires the `.bb-topbar` and `<main>` wrapper
produced above to already be present.

---

## Output required

- File processed
- Whether the `site.css` legacy helper block was already present or added
- Short list of the exact mechanical edits performed (no commentary)
