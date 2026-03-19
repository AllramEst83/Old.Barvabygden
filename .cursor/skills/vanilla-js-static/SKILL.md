---
name: vanilla-js-static
description: >-
  Vanilla JavaScript for static pages. Use when adding or changing scripts
  that touch the DOM, load fragments, or handle events—without new libraries.
---

# Vanilla JS (Barvabygden)

## Style
- Small, named functions; avoid growing globals—attach to one namespace object if needed.
- **Guard** every `querySelector` / `getElementById` result before use.
- Prefer **event delegation** (`element.addEventListener` on a stable parent) for lists and dynamic content.

## DOM & includes
- If content is fetched/injected, run init code **after** insertion (or use `MutationObserver` only if already justified).
- Don’t assume `document.readyState`; if script runs at end of body, init immediately; otherwise use `DOMContentLoaded` once.

## Dependencies
- No npm packages or frameworks unless the user explicitly asks.
