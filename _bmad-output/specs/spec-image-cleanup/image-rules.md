# Image Rules (proposed defaults, confirm before use)

## Naming (CAP-1)

- Pattern: `^[a-z0-9]+(-[a-z0-9]+)*\.(webp|jpg|png|gif|ico)$`.
- Lowercase; spaces and underscores become hyphens; `å`/`ä` become `a`, `ö` becomes `o`; extension lowercased (`.jpeg` becomes `.jpg`, `.GIF` becomes `.gif`); strip noise such as `.jpg-for-web-NORMAL`.
- Names stay unique per folder. On collision append a numeric suffix (`-2`) or the topic. Never rename a file twice in one phase.
- Applies to `assets/images/**` and `barva-bladet/**/images/**`.

## Format and size (CAP-3, CAP-4)

| Type | Policy |
|---|---|
| JPG/JPEG photo | to WebP, quality about 80 |
| PNG photo (no transparency) | to WebP, quality about 80 |
| PNG with transparency, logos, line art | to lossless WebP, or keep PNG if WebP is larger |
| GIF (static) | unchanged |
| `.ico`, existing `.webp` under the caps | unchanged |

- Long edge at most 1920 px; images already smaller are never upscaled.
- Target: typical file under 300 KB, no file over 500 KB; if quality suffers, raise the cap for that file and record why in the manifest.
- Resize and recompress only; no crop, retouch or colour edits. EXIF stripped.

## Duplicates (CAP-2)

- Identical means equal SHA-256. Keep the file in the most specific topic folder that a page references, or in `shared/` when several topics use it; repoint every reference; remove the others with `git rm`.
- Archive per-issue copies are not merged (Constraints in SPEC.md).

## Image manifest (CAP-5)

`scripts/image-manifest.csv`, columns `old_path,new_path,action,old_bytes,new_bytes`; `action` is one of `rename`, `merge`, `resize`, `convert`, `keep`. Built and reviewed before any file is touched; later steps read it and nothing hard-codes paths. Rewrites reuse the `scripts/rewrite-links.ps1` approach.

## Verification

- `scripts/check-links.ps1` at or under baseline after every phase.
- A hash scan, a name-pattern scan and a size scan over live images, all with zero hits.
- Visual spot check: `index.html`, one page per topic, three archive issues, both fragments.
- After upload: `scripts/check-live.ps1`, and the home page plus one page per topic by eye.
