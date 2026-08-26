---
name: site-layout-sections
description: >-
  Page and section structure for this site. Use when adding a new block to
  index.html or a standalone page, or when choosing layout pattern.
---

# Layout sections (Barvabygden)

## Skeleton
- Wrap page body in `header` (nav), `main`, optional `footer`.
- Each major block: `<section id="slug" class="…">` for in-page anchors (nav uses `#hero`, `#events`, etc.).

## Patterns (pick by intent)
- **Single column**: short intros, one message, hero text stack.
- **Grid**: most sections; multi-column content with `row` / `col-*`.
- **Cards**: event lists and other repeating items; `row g-4`, responsive `col-lg-* col-xl-*`, `h-100` on cards.
- **F-pattern**: long or dense content → `accordion`, `list-group`, clear `h2`/`h3` hierarchy.
- **Z-pattern**: hero: visual first, then headline/supporting line, then primary action if any.
- **Asymmetrical**: sparingly for emphasis; never sacrifice reading order or contrast.

## Spacing
- Use Bootstrap vertical rhythm (`py-5`, `mb-5`) consistently within sections; align with neighboring sections on the same page.
