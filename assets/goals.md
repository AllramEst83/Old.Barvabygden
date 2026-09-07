# Barvabygden Modernization Plan

This document outlines the repeatable process for modernizing the legacy `.htm` pages of the Barvabygden website to match the modern `index.html` design and standards.

## Goals

- **UTF-8 Encoding**: Convert all pages to UTF-8 and use literal characters (å, ä, ö) instead of HTML entities.
- **Responsiveness**: Ensure all pages work well on mobile and desktop using Bootstrap 5.
- **Visual Consistency**: Align typography, colors, and layout with the new `index.html`.
- **Structural Integrity**: Remove table-based layouts and use modern CSS (Flexbox/Grid).
- **Maintainability**: Standardize the header, footer, and navigation.

---

## The Modernization Process (Repeatable for each page)

### 1. Preparation & Backup

- Identify the target `.htm` file (e.g., `utst.htm`).
- Ensure the file is tracked in Git to allow easy reverts.

### 2. Encoding Conversion

- Change the file encoding from `Windows-1252` (or similar) to `UTF-8`.
- Update the meta tag: `<meta charset="utf-8">`.
- Remove legacy encoding meta tags like `<meta http-equiv="Content-Type" content="text/html; charset=windows-1252" />`.
- Replace HTML entities (e.g., `&auml;`, `&ouml;`, `&aring;`) with their literal counterparts (ä, ö, å).

### 3. Apply Modern Shell (Template)

- Replace the legacy `<head>` with the standard head from `index.html` (including Bootstrap 5, Google Fonts, and `site.css`).
- Add the `site-header` (Navbar) and `site-footer` to match the main page.
- Wrap the main content area in a `<main class="container my-5">` block.

### 4. Content Migration

- **Remove Tables**: Identify content inside `<table>` tags and move it into Bootstrap `<div class="row">` and `<div class="col-*">` structures.
- **Clean Up Typography**: Remove legacy `<font>`, `<b>`, `<i>`, and `<center>` tags. Use semantic HTML (`<h2>`, `<strong>`, `<em>`) and Bootstrap classes for alignment (`text-center`).
- **Responsive Images**: Ensure all `<img>` tags have the `img-fluid` class and `alt` attributes.
- **Clean Inline Styles**: Move any necessary inline styles to `site.css` or use Bootstrap utility classes.

### 5. Enhancement

- **Lightbox Support**: If the page has image galleries, ensure they use the project's lightbox implementation.
- **Navigation**: Add a "Back to Home" link or breadcrumbs if appropriate.
- **SEO**: Add a relevant `<title>`, `<meta name="description">`, and Open Graph tags.

---

## Modernization Checklist (Per Page)

- [ ] Encoding set to UTF-8.
- [ ] Character entities replaced with literals (å, ä, ö).
- [ ] Responsive design (tested on mobile).
- [ ] Table-based layout removed.
- [ ] Standard header and footer included.
- [ ] Images are responsive (`img-fluid`).
- [ ] Navigation links functional.
- [ ] Accessibility check (alt text, heading structure).

---

## Todo List (Priority Pages)

### Core Pages

- [ ] `utst.htm` (Exhibitions)
- [ ] `skolan.htm` (School)
- [ ] `kyrka.htm` (Church)
- [ ] `prost-sk.htm` (Prostökna)
- [ ] `hagaborg.htm` (Hagaborg)
- [ ] `spackelf.htm` (Spackelfabriken)
- [ ] `bron.htm` (The Bridge)
- [ ] `gruv.htm` (The Mine)
- [ ] `bankl.htm` (Banklekmen)
- [ ] `folkd.htm` (Folkdansen)
- [ ] `bfa.htm` (Barva Framtid)
- [ ] `skolm.htm` (School Museum)
- [ ] `lucia.htm` (Lucia)
- [ ] `luren.htm` (Luren)
- [ ] `tromb.htm` (The Tornado)
- [ ] `gardsg.htm` (Gårdsgrupper)
- [ ] `macken.htm` (The Gas Station)
- [ ] `bib.htm` (Library)

### Secondary / Archives

- [ ] `bb-*.htm` (Barva-Bladet archives - multiple files)
- [ ] `barvaif_2011.htm` (Barva IF Archive)
- [ ] `danser-*.htm` (Dance archives)

---

## Step-by-Step Example: Modernizing `utst.htm`

### Phase 1: Encoding & Meta

**Old:**

```html
<meta http-equiv="Content-Type" content="text/html; charset=windows-1252" />
<title>FORNTIDA ARV I BARVA</title>
```

**New:**

```html
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Forntida Arv i Barva | Barvabygden</title>
```

### Phase 2: Shell Replacement

Replace everything from `<html>` to `<body>` and the end of the file with the boilerplate from `index.html`.

### Phase 3: Content Transformation

**Old (Table Layout):**

```html
<table>
  <tr>
    <td><img src="..." /></td>
    <td><font size="6">V&auml;lkommen till Barva!</font></td>
  </tr>
</table>
```

**New (Bootstrap Grid):**

```html
<header class="page-hero text-center my-4">
  <img
    src="./assets/images/hero/hero-banner.jpg"
    class="img-fluid rounded shadow-sm"
    alt="Barva Banner"
  />
  <h1 class="display-4 mt-3">Välkommen till Barva!</h1>
</header>
```

### Phase 4: Entity Cleanup

- `&auml;` → `ä`
- `&ouml;` → `ö`
- `&aring;` → `å`
- `&nbsp;` → (Remove or replace with CSS margin/padding)

---

## Technical Recommendations

1. **Global Header/Footer**: Consider using a JavaScript "include" or a small build script to avoid duplicating the header/footer across 50+ files.
2. **Asset Organization**: Gradually move images from the root directory to `assets/images/` and update references.
3. **CSS Consolidation**: Use `site.css` for all common styles to keep the `.htm` files clean.
