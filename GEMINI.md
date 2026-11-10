# Barvabygden Community Website

A static website for Barvabygden (Barva village), Sweden. This site serves as a digital hub for local events, organizations, historical content, and community information.

## Project Overview
- **Type**: Static Website
- **Language**: Swedish (sv)
- **Technologies**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.0.2
- **Key Features**: Event management with countdowns, image galleries with lightboxes, newsletter archives (Barva-Bladet), and historical documentation.

## Directory Structure
- `index.html`: Main landing page and entry point.
- `assets/css/`: Stylesheets, including `site.css` (main), `lightbox.css`, and `fallbacks.css`.
- `assets/js/`: JavaScript logic, including `site.js` (main), `event-countdown.js`, and `lightbox.js`.
- `fragments/`: HTML fragments fetched via JS (`events.html`, `newsletter.html`).
- `pages/`: Standalone legacy/content pages (flat).
- `barva-bladet/`: Digital archive of the Barva-Bladet newsletter.
- `assets/images/`: Organized image categories (branding, hero, events, museum, etc.).
- `scripts/`: Deployment scripts (e.g., `update-version.ps1`).
- `docs/`: Project documentation, including `project-documentation.md` and `deploy-runbook.md` (not deployed).
- `migration-sources/` and `attic/`: pending newsletter sources and quarantined files (not deployed).
- `.htaccess`: Apache configuration for HTTPS redirection and cache control.

## Building and Running
- **Local Development**: Open `index.html` directly in a browser or serve using a local web server (e.g., `python -m http.server`, `npx serve`).
- **Deployment**: Run the PowerShell script `./scripts/update-version.ps1` from the root to update version parameters in CSS/JS links for cache busting.

## Development Conventions
- **Events**: New events should be added to `fragments/events.html` following the established card structure.
- **Images**: 
    - New organized assets should go into `assets/images/`.
    - Historical photos go in a topic folder under `assets/images/` (shared ones in `assets/images/shared/`), not in the repo root.
    - Names: lowercase ASCII with hyphens (`^[a-z0-9]+(-[a-z0-9]+)*\.(webp|jpg|png|gif|ico)$`; å/ä to a, ö to o). Long edge at most 1920 px, file at most 500 KB; photos as JPG, PNG only for transparency.
    - Every `.jpg`/`.png` has a `.webp` sibling and is used as `<picture><source type="image/webp" srcset="x.webp"><img src="x.jpg" ...></picture>`. For new images run `scripts/optimize-images.ps1 -Folder <dir>`, `git add`, `scripts/apply-image-pictures.ps1`, then `scripts/check-images.ps1`.
    - Renaming or moving a published image needs a redirect: add a row to `scripts/image-manifest.csv` and run `scripts/build-redirects.ps1`.
- **CSS**: Uses CSS custom properties for theming (defined in `:root` in `site.css`).
- **JavaScript**: Content like events is loaded dynamically using `fetch()` in `site.js`.
- **Cache Control**: Managed via `.htaccess` and the PowerShell versioning script.

## Maintenance Notes
- **Barva-Bladet**: Add new newsletters to yearly folders in `barva-bladet/` and update navigation links.
- **Events**: Seasonal updates are required in `fragments/events.html`.
- **Historical Content**: Maintain consistent naming conventions for new historical uploads.

---
*Refer to `docs/project-documentation.md` for more detailed development guides.*
