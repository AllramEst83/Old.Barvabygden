# Barvabygden Website - Project Documentation

## Project Overview

This is a static website for Barvabygden (Barva village), a community in Sweden between Eskilstuna and Strängnäs. The site serves as a central hub for local events, organizations, historical content, and community information.

**Website URL**: barvabygden.se  
**Language**: Swedish (sv)  
**Target Audience**: Local residents, visitors, and those interested in Swedish rural community life

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **CSS Framework**: Bootstrap 5.0.2
- **Fonts**: Google Fonts (Cookie, Crimson Text, EB Garamond)
- **Build Tools**: PowerShell scripts for deployment
- **Browser Support**: Modern browsers with graceful degradation for older browsers

## Project Structure

```
Old.Barvabygden/
├── index.html                          # Main landing page
├── assets/
│   ├── css/
│   │   ├── site.css                   # Main stylesheet with CSS custom properties
│   │   ├── lightbox.css               # Lightbox functionality styles
│   │   └── fallbacks.css              # Fallback styles for older browsers
│   ├── js/
│   │   ├── site.js                    # Main site functionality
│   │   ├── lightbox.js                # Image lightbox functionality
│   │   ├── event-countdown.js         # Event countdown timers
│   │   ├── polyfills.js               # Browser compatibility
│   │   └── site-legacy.js             # Legacy browser support
│   ├── images/
│   │   ├── branding/                  # Logos, coat of arms
│   │   ├── hero/                      # Hero section images
│   │   ├── events/                    # Event-related images
│   │   ├── museum/                    # Museum collection images
│   │   ├── scenery/                   # Local scenery photos
│   │   ├── personalities/             # Historical figures
│   │   ├── patterns/                  # Background patterns
│   │   └── [other categories]/
│   ├── pages/
│   │   ├── events.html                # Event cards HTML content
│   │   └── newsletter.html            # Newsletter content
│   ├── barva-news/                    # Historical newsletter archives
│   │   ├── 2010/, 2016/, 2017/, etc.  # Yearly folders
│   │   └── title/                     # Newsletter title images
│   ├── docs/                          # Documentation (this file)
│   ├── powershell/
│   │   └── update-version.ps1         # Deployment helper script
│   └── unused-images/                 # Archive of unused images
├── [root images]                      # Historical photos and documents
└── README.md (if exists)
```

## Key Components & Sections

### 1. Main Navigation Sections

The website is organized into these main sections:

- **Hem (Home)** - Hero section with welcome message
- **Evenemang (Events)** - Community events and activities
- **Om Barva (About Barva)** - Village history and information
- **Barva IF** - Local sports club information
- **Museum** - Historical artifacts and exhibitions
- **Barva-Bladet** - Community newsletter archive
- **Tjänster (Services)** - Local services and businesses
- **Bygd Info (Community Info)** - Practical community information
- **Kultur & Historia (Culture & History)** - Cultural content and publications
- **Kontakt (Contact)** - Contact information

### 2. CSS Architecture

The CSS uses a modern approach with CSS custom properties (variables):

```css
:root {
  --color-primary: #064420;
  --color-secondary: #2d3748;
  --color-accent: #0066cc;
  /* Color palette extracted from local imagery */
  --color-wheat-gold: #f4d03f;
  --color-forest-green: #27ae60;
  --color-sky-blue: #85c1e9;
  /* ... more colors */
}
```

**CSS Files Purpose**:

- `site.css` - Main styles with custom properties and responsive design
- `lightbox.css` - Image gallery and lightbox functionality
- `fallbacks.css` - Compatibility styles for older browsers

### 3. JavaScript Functionality

The JavaScript architecture includes:

- **Modular approach** with separate files for different features
- **Progressive enhancement** for older browser support
- **Loading state management** with spinners and retry logic
- **Event countdown functionality** for upcoming events
- **Lightbox functionality** for image galleries

## Content Management Guidelines

### Adding New Events

Events are managed in `assets/pages/events.html`. Each event follows this structure:

```html
<div class="col-lg-6 col-xl-4">
  <article class="event-card h-100 p-4">
    <header class="event-header mb-3">
      <h3 class="event-title h5 mb-2">[Event Name]</h3>
      <div class="event-category badge bg-[color] text-dark mb-2">
        [Category]
      </div>
      <div class="event-countdown mb-2">
        <span
          id="[unique-id]-countdown"
          data-event-date="YYYY-MM-DDTHH:MM"
          data-countdown-format="detailed"
          aria-live="polite"
          class="small js-event-countdown"
        >
          Beräknar tid kvar...
        </span>
      </div>
    </header>

    <div class="event-details">
      <dl class="event-info mb-3">
        <dt class="fw-bold">När:</dt>
        <dd class="mb-2">
          <time datetime="YYYY-MM-DDTHH:MM">[Human readable date]</time>
        </dd>
        <dt class="fw-bold">Plats:</dt>
        <dd class="mb-2">
          <address class="mb-0">
            <a
              href="[Google Maps link]"
              target="_blank"
              rel="noopener noreferrer"
            >
              [Location name]
            </a>
          </address>
        </dd>
      </dl>

      <div class="event-description mb-3">
        <p>[Event description]</p>
        <!-- Additional content -->
      </div>
    </div>
  </article>
</div>
```

**Event Categories & Colors**:

- `bg-warning text-dark` - Markets & Crafts
- `bg-info text-dark` - Cultural events
- `bg-success text-white` - Sports events
- `bg-primary text-white` - Community meetings

### Adding Images

**Image Organization**:

- Place images in appropriate subdirectories under `assets/images/`
- Use descriptive filenames in Swedish or English
- Optimize images for web (compress while maintaining quality)
- Consider responsive image needs

**Root Directory Images**:

- Historical photos and documents are stored in the root directory
- Use descriptive filenames with years when possible
- Examples: `1870.jpg`, `1926.jpg`, `2015-dansen.jpg`

**Image Categories**:

- `branding/` - Logos, coat of arms, official graphics
- `hero/` - Main banner images
- `events/` - Event-specific photos
- `museum/` - Historical artifacts and museum pieces
- `scenery/` - Local landscape and building photos
- `personalities/` - Historical figures and notable people
- `patterns/` - Background patterns and textures

### Newsletter Management

Historical newsletters are organized in `assets/barva-news/`:

- Create yearly folders (e.g., `2025/`)
- Store PDF files with descriptive names
- Update navigation links in the main HTML

### CSS Color Scheme

The site uses a nature-inspired color palette extracted from local imagery:

```css
/* Primary colors */
--color-primary: #064420; /* Dark forest green */
--color-secondary: #2d3748; /* Dark gray */
--color-accent: #0066cc; /* Bright blue */

/* Extended palette */
--color-wheat-gold: #f4d03f; /* Agricultural gold */
--color-forest-green: #27ae60; /* Forest green */
--color-sky-blue: #85c1e9; /* Sky blue */
--color-barn-red: #c77067; /* Traditional barn red */
--color-meadow-green: #a9dfbf; /* Light meadow green */
--color-lavender: #d2b4de; /* Soft lavender */
```

## Development Workflow

### 1. Local Development

1. Make changes to HTML, CSS, or JavaScript files
2. Test in multiple browsers (Chrome, Firefox, Safari, Edge)
3. Verify mobile responsiveness
4. Check accessibility features

### 2. Deployment

Use the PowerShell script for cache busting:

```powershell
# From project root
./assets/powershell/update-version.ps1
```

This script:

- Updates version parameters on CSS and JS files
- Ensures browsers load fresh assets after updates
- Provides deployment timestamp

### 3. Version Control Best Practices

- Commit logical changes together
- Use descriptive commit messages in Swedish or English
- Test thoroughly before pushing to main branch

## Browser Compatibility

### Modern Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

### Legacy Browser Support

- Graceful degradation for older browsers
- Fallback CSS for unsupported features
- Polyfills for JavaScript functionality
- `noscript` content for JavaScript-disabled browsers

### Accessibility Features

- Semantic HTML structure
- ARIA labels for dynamic content
- High contrast color combinations
- Keyboard navigation support
- Screen reader compatibility

## Performance Considerations

### Image Optimization

- Use appropriate file formats (JPEG for photos, PNG for graphics)
- Compress images without significant quality loss
- Consider WebP format for modern browsers
- Implement lazy loading for large image galleries

### CSS & JavaScript

- Minify CSS and JavaScript for production
- Use CSS custom properties for consistent theming
- Progressive enhancement for JavaScript features
- Cache busting with version parameters

### Loading Performance

- Critical CSS inlined or loaded first
- Non-critical JavaScript loaded asynchronously
- Loading states and spinners for dynamic content
- Retry logic for failed network requests

## Common Tasks

### Adding a New Section

1. Add navigation link in `index.html` navigation
2. Create section HTML structure with proper IDs
3. Add corresponding CSS styles in `site.css`
4. Implement any JavaScript functionality needed
5. Test responsive behavior

### Updating Event Information

1. Edit `assets/pages/events.html`
2. Update event dates, descriptions, and details
3. Add new event images to `assets/images/events/`
4. Test countdown functionality
5. Deploy with version update

### Adding Historical Content

1. Add images to appropriate directories
2. Update museum or history sections in `index.html`
3. Consider creating new category directories if needed
4. Maintain consistent naming conventions

## Troubleshooting

### Common Issues

1. **Images not loading**: Check file paths and ensure images exist
2. **CSS not updating**: Run deployment script to bust cache
3. **JavaScript errors**: Check browser console for specific errors
4. **Mobile layout issues**: Test responsive breakpoints

### Browser-Specific Issues

- **Internet Explorer**: Use polyfills and fallback CSS
- **Safari**: Test flexbox and grid implementations
- **Mobile browsers**: Verify touch interactions and viewport settings

## Contact & Maintenance

For technical issues or content updates, refer to the contact information provided on the website. Regular maintenance should include:

- Updating event information seasonally
- Adding new historical content as available
- Monitoring website performance and accessibility
- Keeping dependencies updated for security

---

_This documentation is maintained as part of the Barvabygden website project. Last updated: September 2025_
