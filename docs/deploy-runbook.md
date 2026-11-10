# Deploy runbook: cutover to the new folder layout

Deploys are manual: WinSCP over SSH to one.com. You compare local against remote and check or
uncheck what to upload. The folder restructure is a one-off cutover, and this page is the
repeatable procedure for it. Ordinary later deploys use steps 1–3 only.

## Before you start

1. On `feature/folder-restructure` (or after merging it), regenerate the generated files so they
   match the manifest:
   ```powershell
   ./scripts/build-redirects.ps1           # .htaccess 301 blocks (pages, PDFs and images)
   ./scripts/build-server-delete-list.ps1  # scripts/server-delete-list.txt
   ./scripts/check-links.ps1               # must report: No new broken references
   ./scripts/check-images.ps1              # image rules: names, size, WebP, picture markup
   ./scripts/update-version.ps1            # cache-busting version in index.html
   ```
2. Serve locally (`python -m http.server`) and open `index.html`, one page from `pages/`, both
   fragments (the events and newsletter sections) and one newsletter issue. Styles and images must
   load.

## Upload set

Upload: `index.html`, `.htaccess`, `pages/`, `fragments/`, `barva-bladet/`, `assets/`.

Never upload: `docs/`, `scripts/`, `attic/`, `migration-sources/`, `_bmad/`, `_bmad-output/`,
`.agents/`, `.claude/`, `.vscode/`, `.ilosoft/`, `web/`, `*.md`, `*.ps1`, `.git*`.
(`.htaccess` also answers 404 for these if they are uploaded by mistake.)

## Steps

1. **Synchronize (local → remote)** in WinSCP with:
   - **Exclusion file mask:**
     `| docs/; scripts/; attic/; migration-sources/; _bmad/; _bmad-output/; .agents/; .claude/; .vscode/; .ilosoft/; web/; *.md; *.ps1; .git*`
     Repo-only folders, including the ~410 files in `attic/`, then never appear in the comparison list.
   - **"Delete files" off.** Otherwise the old root media that exist only on the server would be
     offered for deletion.
2. **Read the comparison preview.** Expect new files under `pages/`, `fragments/`, `barva-bladet/`
   and the topic folders in `assets/images/` (plus `assets/files/` if present), and changed
   `index.html`, `assets/js/site*.js` and `.htaccess`. Check all of them. Check `.htaccess` last, or
   upload it on its own after the rest, so redirects never point at files that are not there yet.
3. **Verify:**
   ```powershell
   ./scripts/check-live.ps1
   ```
   It checks that each old page and newsletter-PDF URL returns 301 to its new path and that the new
   path returns 200, that the home page returns 200, and that `/docs/`, `/attic/`, `/README.md` and
   `/.agents/` return 404. Then spot-check the home page, the events and newsletter sections, and one
   issue by eye. `https://barvabygden.se/kyrka.htm` must land on `/pages/kyrka.htm`.
4. **Delete the old server copies** listed in `scripts/server-delete-list.txt`: the folders
   `assets/pages/`, `assets/barva-news/`, `assets/powershell/` and `assets/docs/`, and the old root
   page and PDF files. **Leave every root media file in place** (`.jpg`, `.gif`, `.png`): external
   image links still use them.
5. **Re-run step 3.** Redirects (mod_alias `Redirect`) fire whether or not the old copy still exists,
   so the result should be identical.

## Images

The image cleanup replaced many image files: new names, resized JPG/PNG fallbacks, a `.webp` next to
each, and some images removed. Deploy it with the same steps; these points are specific to images.

- **Upload size.** `assets/images/` and `barva-bladet/` now hold about 27 MB, down from about 120 MB.
  Upload `.htaccess` last, as in step 2, so the image redirects never point at files that are missing.
- **Old copies stay on the server.** Because step 1 keeps "Delete files" off, renamed, merged and
  format-changed images (and the removed PNGs and attic images) leave their old copies on the server.
  That is harmless: the 301 redirects answer before the file is looked up. To tidy up, run a second
  synchronize limited to `assets/images/` and `barva-bladet/` with "Delete files" on and read the
  preview first: only old image names should be offered for deletion. The root media files are not in
  those folders, so they stay.
- **Case-only renames.** 16 images changed only in case (for example `Gran1.GIF` to `gran1.gif`).
  WinSCP on Windows may treat the two names as the same file, so the lowercase file might not be
  uploaded. `./scripts/check-live.ps1` catches it (the redirect target answers 404). If it does,
  delete the old-case file on the server by hand and upload the new one.
- **Verify.** `./scripts/check-live.ps1` also checks every image redirect (old URL 301, new URL 200)
  and that `.webp` files are served as `image/webp`. Then look at the home page, one page per topic
  and one archive issue in a browser.
- **Cache.** Fallback JPGs that were resized in place keep their URL and are cached for a week;
  `scripts/update-version.ps1` (step 1) refreshes the CSS and JS links, but returning visitors may
  see an old image until their cache expires.
- **New images later.** Follow the image rules in `CLAUDE.md`: name, `optimize-images.ps1 -Folder`,
  `apply-image-pictures.ps1`, `check-images.ps1`, then deploy as usual.

## If something goes wrong

- Redirects misbehave or the site breaks: re-upload the previous `.htaccess` (keep a copy before
  step 2) and re-check. The old copies stay on the server until step 4, so nothing is lost before then.
- A page renders without styles or images: run `./scripts/check-links.ps1` locally and compare the
  path in the page with `scripts/path-manifest.csv`.
- To undo the layout change in git, revert the phase commits on `feature/folder-restructure`; each
  phase was committed separately.

## Reference

- `scripts/path-manifest.csv`: every moved file, old path → new path, with its class.
- `scripts/image-manifest.csv`: every live image, old path, new path and action (rename, merge, convert, quarantine, keep); the source of the image redirects.
- `attic/README.md`: what the quarantine holds.
- Pending newsletter issues 37–46 wait in `migration-sources/barva-bladet/` and redirect to `/`
  until the migration skill moves them into `barva-bladet/` and repoints their redirect.
