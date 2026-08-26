---
name: bootstrap-barvabygden
description: >-
  Bootstrap-first UI for Barvabygden. Use when composing or refactoring layout,
  components, spacing, or responsiveness with vanilla HTML.
---

# Bootstrap-first (Barvabygden)

## Defaults
- Prefer Bootstrap layout: `container` (centered blocks), `container-fluid` only for full-bleed (e.g. navbar band).
- Structure sections: `row` → `col-12` for titles → `col-*` for content. Use `g-3` / `g-4` for gutters.
- Prefer utilities (`py-5`, `mb-3`, `text-center`, `d-flex`, `align-items-center`) over new CSS.

## Components
- Navigation: `navbar`, `collapse`, existing `site-header` pattern from `index.html`.
- Repeatable items: card-style blocks in a grid; use `h-100` for equal-height columns when cards stack.
- Dense lists: `accordion` + inner `row`/`col` like About / newsletter patterns.

## Custom CSS
- Add only when Bootstrap cannot express the design cleanly. Scope to a page/section class; avoid `!important` unless unblocking legacy.
