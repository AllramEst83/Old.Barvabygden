/**
 * reader-mode.js
 * ─────────────────────────────────────────────────────────────────────────────
 * Modular, zero-dependency Reader Mode for static pages.
 *
 * Quick setup on any page:
 *   1. Link assets/css/reader-mode.css in <head>.
 *   2. Add this script before </body>.
 *   3. Give your trigger button the attribute:  data-reader-mode-trigger
 *   4. Optionally add data-reader-content="<selector>" on the same button
 *      (defaults to "main").
 *
 * Advanced: call ReaderMode.init({ triggerSelector, contentSelector }) manually.
 *
 * Preferences (theme, font size) are stored in localStorage so they persist
 * across sessions.
 */

const ReaderMode = (() => {
  'use strict';

  /* ── Constants ─────────────────────────────────────────────────────────── */
  const LS_THEME     = 'rm-theme';
  const LS_FONT_SIZE = 'rm-font-size';
  const MIN_SIZE     = 13;
  const MAX_SIZE     = 24;
  const DEFAULT_SIZE = 17;

  const THEMES = {
    'paper-white':  'Pappershvit',
    'wheat':        'Vete',
    'satin-black':  'Satinsvart',
  };

  /* ── State ─────────────────────────────────────────────────────────────── */
  let overlay       = null;
  let contentArea   = null;
  let lastFocused   = null;
  let activeTheme   = 'paper-white';
  let activeFontSz  = DEFAULT_SIZE;

  /* ── Preference helpers ────────────────────────────────────────────────── */
  function loadPrefs() {
    try {
      const t = localStorage.getItem(LS_THEME);
      const s = parseInt(localStorage.getItem(LS_FONT_SIZE), 10);
      if (t && THEMES[t])                         activeTheme  = t;
      if (!isNaN(s) && s >= MIN_SIZE && s <= MAX_SIZE) activeFontSz = s;
    } catch (_) { /* localStorage may be unavailable */ }
  }

  function savePrefs() {
    try {
      localStorage.setItem(LS_THEME,     activeTheme);
      localStorage.setItem(LS_FONT_SIZE, activeFontSz);
    } catch (_) {}
  }

  /* ── DOM helpers ───────────────────────────────────────────────────────── */

  /**
   * Clones `sourceEl` and strips inline font-size declarations so the
   * reader's slider controls text size uniformly.
   */
  function cloneAndClean(sourceEl) {
    const clone = sourceEl.cloneNode(true);

    /* Strip inline font-size and fixed widths from every element */
    clone.querySelectorAll('[style]').forEach(el => {
      el.style.fontSize = '';
      el.style.width    = '';
      el.style.minWidth = '';
    });

    /* Remove legacy presentational attributes that fight the reader layout */
    clone.querySelectorAll('[align]').forEach(el => el.removeAttribute('align'));
    clone.querySelectorAll('[width]').forEach(el => {
      /* Keep width on <img> so the browser can still size them; drop from everything else */
      if (el.tagName !== 'IMG') el.removeAttribute('width');
    });
    clone.querySelectorAll('[bgcolor]').forEach(el => el.removeAttribute('bgcolor'));

    return clone;
  }

  /** Build and return the complete overlay element. */
  function buildOverlay(contentSelector) {
    const source = document.querySelector(contentSelector);
    if (!source) {
      console.warn('[ReaderMode] Source element not found:', contentSelector);
      return null;
    }

    const cleaned = cloneAndClean(source);

    /* ── Overlay wrapper ── */
    const wrap = document.createElement('div');
    wrap.className = 'reader-mode-overlay';
    wrap.setAttribute('role',       'dialog');
    wrap.setAttribute('aria-modal', 'true');
    wrap.setAttribute('aria-label', 'Läsläge');
    wrap.setAttribute('data-theme', activeTheme);

    /* ── Toolbar ── */
    const toolbar = document.createElement('div');
    toolbar.className = 'reader-mode-toolbar';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', 'Läsläge inställningar');

    /* Label */
    const lbl = document.createElement('span');
    lbl.className   = 'reader-mode-label';
    lbl.textContent = 'Läsläge';
    lbl.setAttribute('aria-hidden', 'true');

    /* Font-size control */
    const fontCtrl = document.createElement('div');
    fontCtrl.className = 'reader-mode-font-control';

    const sliderId = 'rm-font-slider-' + Math.random().toString(36).slice(2, 7);

    const aSmall = document.createElement('span');
    aSmall.className       = 'rm-a-small';
    aSmall.textContent     = 'A';
    aSmall.setAttribute('aria-hidden', 'true');

    const slider = document.createElement('input');
    slider.type       = 'range';
    slider.id         = sliderId;
    slider.className  = 'reader-mode-font-slider';
    slider.min        = MIN_SIZE;
    slider.max        = MAX_SIZE;
    slider.value      = activeFontSz;
    slider.setAttribute('aria-label', 'Teckenstorlek');

    const aLarge = document.createElement('span');
    aLarge.className       = 'rm-a-large';
    aLarge.textContent     = 'A';
    aLarge.setAttribute('aria-hidden', 'true');

    fontCtrl.append(aSmall, slider, aLarge);

    /* Theme picker */
    const themeGroup = document.createElement('div');
    themeGroup.className = 'reader-mode-theme-group';
    themeGroup.setAttribute('role',        'group');
    themeGroup.setAttribute('aria-label',  'Bakgrundsfärg');

    Object.entries(THEMES).forEach(([key, label]) => {
      const btn = document.createElement('button');
      btn.type      = 'button';
      btn.className = 'reader-mode-theme-btn' + (key === activeTheme ? ' is-active' : '');
      btn.dataset.theme = key;
      btn.title     = label;
      btn.setAttribute('aria-label',   label);
      btn.setAttribute('aria-pressed', String(key === activeTheme));
      themeGroup.appendChild(btn);
    });

    /* Controls panel — wraps font + theme, collapses on small screens */
    const controlsPanel = document.createElement('div');
    controlsPanel.className = 'reader-mode-controls';
    controlsPanel.append(fontCtrl, themeGroup);

    /* Settings toggle (hamburger for controls) — only visible on small screens via CSS */
    const settingsToggle = document.createElement('button');
    settingsToggle.type      = 'button';
    settingsToggle.className = 'reader-mode-settings-toggle';
    settingsToggle.setAttribute('aria-label',    'Visa inställningar');
    settingsToggle.setAttribute('aria-expanded', 'false');
    settingsToggle.setAttribute('aria-controls', 'rm-controls-panel');
    settingsToggle.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none"
      stroke="currentColor" stroke-width="2" stroke-linecap="round"
      stroke-linejoin="round" aria-hidden="true">
      <line x1="4"  y1="6"  x2="20" y2="6"/>
      <line x1="4"  y1="12" x2="14" y2="12"/>
      <line x1="4"  y1="18" x2="18" y2="18"/>
    </svg>`;
    controlsPanel.id = 'rm-controls-panel';

    /* Close button */
    const closeBtn = document.createElement('button');
    closeBtn.type      = 'button';
    closeBtn.className = 'reader-mode-close';
    closeBtn.setAttribute('aria-label', 'Stäng läsläge');
    closeBtn.innerHTML = `<svg width="18" height="18" viewBox="0 0 18 18" fill="none"
      stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
      <line x1="2" y1="2" x2="16" y2="16"/>
      <line x1="16" y1="2" x2="2" y2="16"/>
    </svg>`;

    toolbar.append(lbl, settingsToggle, closeBtn, controlsPanel);

    /* ── Content area ── */
    contentArea = document.createElement('div');
    contentArea.className = 'reader-mode-content';
    contentArea.style.fontSize = activeFontSz + 'px';

    /* Move cleaned children in (not the source element itself) */
    while (cleaned.firstChild) {
      contentArea.appendChild(cleaned.firstChild);
    }

    wrap.append(toolbar, contentArea);

    /* ── Event wiring ── */

    /* Settings toggle: show/hide controls on small screens */
    settingsToggle.addEventListener('click', () => {
      const isOpen = toolbar.classList.toggle('controls-open');
      settingsToggle.setAttribute('aria-expanded', String(isOpen));
      settingsToggle.setAttribute('aria-label', isOpen ? 'Dölj inställningar' : 'Visa inställningar');
    });

    /* Font size: apply only on "change" (pointer/touch release) */
    slider.addEventListener('change', () => {
      activeFontSz = parseInt(slider.value, 10);
      contentArea.style.fontSize = activeFontSz + 'px';
      savePrefs();
    });

    /* Theme buttons */
    themeGroup.addEventListener('click', e => {
      const btn = e.target.closest('.reader-mode-theme-btn');
      if (!btn) return;
      setTheme(btn.dataset.theme);
      themeGroup.querySelectorAll('.reader-mode-theme-btn').forEach(b => {
        const active = b.dataset.theme === activeTheme;
        b.classList.toggle('is-active', active);
        b.setAttribute('aria-pressed', String(active));
      });
      savePrefs();
    });

    closeBtn.addEventListener('click', close);

    return wrap;
  }

  /* ── Core API ──────────────────────────────────────────────────────────── */

  function setTheme(name) {
    if (!THEMES[name] || !overlay) return;
    activeTheme = name;
    overlay.setAttribute('data-theme', name);
  }

  /**
   * Open the reader mode overlay.
   * @param {object} [opts]
   * @param {string} [opts.contentSelector="main"]  CSS selector for content source.
   */
  function open(opts) {
    if (overlay) return;

    loadPrefs();

    const contentSelector = (opts && opts.contentSelector) || 'main';
    overlay = buildOverlay(contentSelector);
    if (!overlay) return;

    /* Remember focus so we can restore it on close */
    lastFocused = document.activeElement;

    document.body.appendChild(overlay);
    document.body.classList.add('reader-mode-open');

    /* Trigger CSS fade-in on next paint */
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        if (overlay) overlay.classList.add('is-visible');
      });
    });

    document.addEventListener('keydown', onKeyDown);

    /* Move focus to the close button */
    const closeBtn = overlay.querySelector('.reader-mode-close');
    if (closeBtn) closeBtn.focus();
  }

  /** Close and remove the reader mode overlay. */
  function close() {
    if (!overlay) return;

    overlay.classList.remove('is-visible');
    document.body.classList.remove('reader-mode-open');
    document.removeEventListener('keydown', onKeyDown);

    overlay.addEventListener('transitionend', () => {
      if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
      overlay    = null;
      contentArea = null;
      if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
    }, { once: true });
  }

  function onKeyDown(e) {
    if (e.key === 'Escape') {
      e.preventDefault();
      close();
    }
  }

  /**
   * Attach a trigger button listener.
   * Call once after DOMContentLoaded.
   *
   * @param {object} [opts]
   * @param {string} [opts.triggerSelector="[data-reader-mode-trigger]"]
   * @param {string} [opts.contentSelector="main"]
   */
  function init(opts) {
    const options         = opts || {};
    const triggerSel      = options.triggerSelector || '[data-reader-mode-trigger]';
    const contentSel      = options.contentSelector || 'main';
    const triggerEl       = document.querySelector(triggerSel);

    if (!triggerEl) return;

    /* Allow per-element override via data attribute */
    const resolvedContent = triggerEl.dataset.readerContent || contentSel;

    triggerEl.addEventListener('click', () => open({ contentSelector: resolvedContent }));
  }

  /* ── Public surface ────────────────────────────────────────────────────── */
  return { init, open, close };
})();

/* Auto-initialize */
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => ReaderMode.init());
} else {
  ReaderMode.init();
}
