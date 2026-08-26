---
name: swedish-a11y-content
description: >-
  Swedish copy, structured content, and baseline accessibility. Use when
  writing headings, event copy, contact info, or interactive widgets.
---

# Swedish content & accessibility (Barvabygden)

## Copy
- Tone: warm, community-focused, plain Swedish. Keep titles scannable; avoid long unbroken paragraphs.
- **Dates**: use `<time datetime="…">` with ISO datetime when precise (events).
- **Addresses / maps**: link to maps with `target="_blank"` and `rel="noopener noreferrer"`.
- **Phone**: `tel:` links; preserve Swedish formatting users expect in visible text.

## Accessibility
- Meaningful **alt** on images; empty alt only if decorative and redundant.
- Logical heading order: one main `h1` per page; don’t skip levels for styling (use Bootstrap heading classes on the correct level).
- **Accordion / toggles**: use Bootstrap patterns; preserve `aria-expanded`, `aria-controls` as in existing markup.
- Dynamic text (e.g. countdown): keep **live region** attributes (`aria-live`) where already used; never rely on emoji alone for critical info.

## Don’t
- Don’t replace clear text with only symbols/emoji for essential meaning.
