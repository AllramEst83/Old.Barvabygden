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
- `assets/pages/`: HTML fragments fetched via JS (e.g., `events.html`).
- `assets/barva-news/`: Digital archive of the Barva-Bladet newsletter.
- `assets/images/`: Organized image categories (branding, hero, events, museum, etc.).
- `assets/powershell/`: Deployment scripts (e.g., `update-version.ps1`).
- `assets/docs/`: Project documentation, including `PROJECT_DOCUMENTATION.md`.
- `[root images]`: Extensive collection of historical photos and documents (e.g., `1870.jpg`, `1926.jpg`).
- `.htaccess`: Apache configuration for HTTPS redirection and cache control.

## Building and Running
- **Local Development**: Open `index.html` directly in a browser or serve using a local web server (e.g., `python -m http.server`, `npx serve`).
- **Deployment**: Run the PowerShell script `./assets/powershell/update-version.ps1` from the root to update version parameters in CSS/JS links for cache busting.

## Development Conventions
- **Events**: New events should be added to `assets/pages/events.html` following the established card structure.
- **Images**: 
    - New organized assets should go into `assets/images/`.
    - Historical photos and documents are traditionally stored in the root directory with descriptive filenames (e.g., `YYYY-description.jpg`).
- **CSS**: Uses CSS custom properties for theming (defined in `:root` in `site.css`).
- **JavaScript**: Content like events is loaded dynamically using `fetch()` in `site.js`.
- **Cache Control**: Managed via `.htaccess` and the PowerShell versioning script.

## Maintenance Notes
- **Barva-Bladet**: Add new newsletters to yearly folders in `assets/barva-news/` and update navigation links.
- **Events**: Seasonal updates are required in `assets/pages/events.html`.
- **Historical Content**: Maintain consistent naming conventions for new historical uploads.

---
*Refer to `assets/docs/PROJECT_DOCUMENTATION.md` for more detailed development guides.*
