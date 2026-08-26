---
name: reader-mode
description: >-
  Add a "Läsläge" (Reader Mode) overlay to a legacy Barva-Bladet issue page.
  The overlay provides font-size control, three background themes (Pappershvit,
  Vete, Satinsvart), and a responsive toolbar that collapses on small screens.
  Use when asked to add läsläge, reader mode, reading view, or reading
  experience to a page under assets/barva-news/. Requires the page to already
  be modernized (has .bb-topbar and <main class="container-lg bb-issue my-3">);
  run the modernize-legacy-barva-bladet skill first if not.
---

# Reader Mode Skill

Wires the shared Reader Mode files into a legacy issue page. The CSS and JS
already exist — do **not** recreate them.

## Shared files (already exist)

- `assets/css/reader-mode.css`
- `assets/js/reader-mode.js`

## Path prefix

For `assets/barva-news/<year>/` files the prefix is `../../../assets/`.
Calculate depth for other locations accordingly.

---

## Three edits per page

### 1 — `<head>`: add CSS link after `site.css`

```html
<!-- Reader Mode -->
<link rel="stylesheet" href="../../../assets/css/reader-mode.css" />
```

### 2 — `.bb-topbar`: add trigger button after the back-link `<a>`

```html
  <button
    type="button"
    class="reader-mode-trigger"
    data-reader-mode-trigger
    data-reader-content="main"
    aria-label="Öppna läsläge"
  >
    <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
      stroke="currentColor" stroke-width="2" stroke-linecap="round"
      stroke-linejoin="round" aria-hidden="true">
      <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
      <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
    </svg>
    Läsläge
  </button>
```

### 3 — end of `<body>`: add script after Bootstrap JS

```html
<!-- Reader Mode -->
<script src="../../../assets/js/reader-mode.js"></script>
```

---

## How the feature works (for context, not for editing)

- **Trigger**: any button with `data-reader-mode-trigger` auto-initialises via
  `ReaderMode.init()` at the bottom of `reader-mode.js`.
- **Content source**: `data-reader-content="main"` tells the JS which element
  to clone. Change the value if the page uses a different wrapper selector.
- **Toolbar**: on screens ≥ 600 px all controls are always visible. Below
  600 px the font slider and theme buttons collapse behind a settings toggle.
- **Themes**: `paper-white` (#f8f5f0), `wheat` (#f5deb3),
  `satin-black` (#1a1a1a). Active state uses a hard-coded box-shadow ring per
  swatch — do not switch this to `var(--rm-text)` as it causes flicker on
  theme change.
- **Font size**: `change` event only (fires on pointer release), range 13–24 px.
- **Persistence**: theme and font size are stored in `localStorage` keys
  `rm-theme` / `rm-font-size`.
- **Accessibility**: overlay has `role="dialog" aria-modal="true"`, focus moves
  to the close button on open and returns to the trigger on close, ESC closes.

---

## Sanity checks

- `.bb-topbar` contains exactly one `.back-link` and one `.reader-mode-trigger`.
- `data-reader-content` value matches the `<main>` selector (`"main"` by default).
- CSS `<link>` is inside `<head>`; `<script>` is at end of `<body>`.
- Path prefix resolves correctly relative to the file's location.
- Do **not** edit `reader-mode.css` or `reader-mode.js` as part of adding the
  feature to a new page — only touch the target issue HTML file.
