/**
 * Event Countdown Manager
 * Handles dynamic countdown displays for multiple events
 *
 * Usage:
 * - Add class 'js-event-countdown' to any element
 * - Add 'data-event-date' attribute with ISO date string
 * - Optionally add 'data-countdown-format' for custom formatting
 *
 * @version 2.0
 */
(function (window, document) {
  "use strict";

  // Configuration constants
  const CONFIG = {
    SELECTOR: ".js-event-countdown",
    UPDATE_INTERVAL: 60000, // Update every minute
    DATE_ATTRIBUTE: "data-event-date",
    FORMAT_ATTRIBUTE: "data-countdown-format",
    DEBUG: false,
  };

  // Time constants
  const TIME_UNITS = {
    MINUTE: 60 * 1000,
    HOUR: 60 * 60 * 1000,
    DAY: 24 * 60 * 60 * 1000,
  };

  /**
   * Event Countdown Class
   */
  class EventCountdown {
    constructor() {
      this.elements = new Map();
      this.updateInterval = null;
      this.isRunning = false;

      // Bind methods to maintain context
      this.update = this.update.bind(this);
      this.handleVisibilityChange = this.handleVisibilityChange.bind(this);

      this.log("EventCountdown initialized");
    }

    /**
     * Initialize the countdown system
     * @param {Element} root - Root element to search within (default: document)
     */
    init(root = document) {
      this.log("Initializing countdowns...");

      // Find all countdown elements
      const countdownElements = root.querySelectorAll(CONFIG.SELECTOR);

      // Mark past table rows (e.g. match schedules without countdown spans)
      this.markPastTableRows(root);

      if (countdownElements.length === 0) {
        this.log("No countdown elements found");
        return;
      }

      // Register each element
      countdownElements.forEach((element) => this.registerElement(element));

      // Start the update cycle
      this.start();

      // Handle page visibility changes
      this.setupVisibilityHandler();

      this.log(`Initialized ${countdownElements.length} countdown(s)`);
    }

    /**
     * Register a countdown element
     * @param {Element} element - The countdown element
     */
    registerElement(element) {
      const dateStr = element.getAttribute(CONFIG.DATE_ATTRIBUTE);
      const format = element.getAttribute(CONFIG.FORMAT_ATTRIBUTE) || "days";

      if (!dateStr) {
        this.warn("Element missing data-event-date attribute", element);
        return;
      }

      const targetDate = new Date(dateStr);
      if (isNaN(targetDate.getTime())) {
        this.warn("Invalid date format:", dateStr, element);
        return;
      }

      // Store element data
      const elementData = {
        element,
        targetDate,
        format,
        lastUpdate: null,
      };

      this.elements.set(element, elementData);

      // Initial update
      this.updateElement(elementData);

      this.log("Registered countdown for:", dateStr, "Format:", format);
    }

    /**
     * Update a single countdown element
     * @param {Object} elementData - Element data object
     */
    updateElement(elementData) {
      const { element, targetDate, format } = elementData;
      const now = new Date();
      const timeDiff = targetDate.getTime() - now.getTime();

      const displayText = this.formatCountdown(timeDiff, targetDate, format);

      // Only update if text has changed
      if (element.textContent !== displayText) {
        element.textContent = displayText;
        elementData.lastUpdate = now;

        // Add CSS class based on status
        this.updateElementStatus(element, timeDiff);
      }
    }

    /**
     * Format countdown text based on time difference
     * @param {number} timeDiff - Time difference in milliseconds
     * @param {Date} targetDate - Target date
     * @param {string} format - Format type ('days', 'detailed', 'hours')
     * @returns {string} Formatted countdown text
     */
    formatCountdown(timeDiff, targetDate, format) {
      // Event has passed
      if (timeDiff < -TIME_UNITS.DAY) {
        return "Evenemanget har passerat";
      }

      // Event is today
      if (this.isToday(targetDate)) {
        const hoursUntil = Math.ceil(timeDiff / TIME_UNITS.HOUR);
        if (hoursUntil > 0) {
          return `Idag, ${hoursUntil} timmar kvar`;
        }
        return "Pågår idag";
      }

      // Future event
      if (timeDiff > 0) {
        switch (format) {
          case "detailed":
            return this.formatDetailed(timeDiff);
          case "hours":
            return this.formatHours(timeDiff);
          case "days":
          default:
            return this.formatDays(timeDiff);
        }
      }

      return "Evenemanget har passerat";
    }

    /**
     * Format countdown in days
     */
    formatDays(timeDiff) {
      const days = Math.ceil(timeDiff / TIME_UNITS.DAY);
      return `${days} ${days === 1 ? "dag" : "dagar"} kvar`;
    }

    /**
     * Format countdown in hours
     */
    formatHours(timeDiff) {
      const hours = Math.ceil(timeDiff / TIME_UNITS.HOUR);
      return `${hours} ${hours === 1 ? "timme" : "timmar"} kvar`;
    }

    /**
     * Format detailed countdown (days, hours, minutes)
     */
    formatDetailed(timeDiff) {
      const days = Math.floor(timeDiff / TIME_UNITS.DAY);
      const hours = Math.floor((timeDiff % TIME_UNITS.DAY) / TIME_UNITS.HOUR);
      const minutes = Math.floor(
        (timeDiff % TIME_UNITS.HOUR) / TIME_UNITS.MINUTE
      );

      const parts = [];
      if (days > 0) parts.push(`${days} ${days === 1 ? "dag" : "dagar"}`);
      if (hours > 0) parts.push(`${hours} ${hours === 1 ? "timme" : "timmar"}`);
      if (minutes > 0 && days === 0)
        parts.push(`${minutes} ${minutes === 1 ? "minut" : "minuter"}`);

      return parts.length > 0
        ? `${parts.join(", ")} kvar`
        : "Mindre än en minut kvar";
    }

    /**
     * Check if date is today
     */
    isToday(date) {
      const today = new Date();
      return (
        date.getDate() === today.getDate() &&
        date.getMonth() === today.getMonth() &&
        date.getFullYear() === today.getFullYear()
      );
    }

    /**
     * Update element CSS classes based on countdown status
     */
    updateElementStatus(element, timeDiff) {
      // Remove existing status classes
      element.classList.remove(
        "countdown-today",
        "countdown-soon",
        "countdown-passed"
      );

      if (timeDiff < 0) {
        element.classList.add("countdown-passed");
        // Mark the nearest event card as past for strikethrough styling
        const card = element.closest(".event-card");
        if (card) card.classList.add("event-card-past");
      } else if (timeDiff < TIME_UNITS.DAY) {
        element.classList.add("countdown-today");
      } else if (timeDiff < TIME_UNITS.DAY * 7) {
        element.classList.add("countdown-soon");
      }
    }

    /**
     * Mark table rows as past when their <time datetime> date has passed.
     * Applies 'event-row-past' to <tr> elements inside event tables.
     * @param {Element} root - Root element to search within (default: document)
     */
    markPastTableRows(root = document) {
      const now = Date.now();
      root.querySelectorAll("tr").forEach((row) => {
        const timeEl = row.querySelector("time[datetime]");
        if (!timeEl) return;
        const dateStr = timeEl.getAttribute("datetime");
        if (!dateStr) return;
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return;
        if (date.getTime() < now) {
          row.classList.add("event-row-past");
        }
      });
    }

    /**
     * Update all countdown elements
     */
    update() {
      if (this.elements.size === 0) {
        this.log("No elements to update");
        return;
      }

      this.elements.forEach((elementData) => {
        // Check if element is still in DOM
        if (!document.contains(elementData.element)) {
          this.elements.delete(elementData.element);
          return;
        }

        this.updateElement(elementData);
      });

      // Stop if no elements remain
      if (this.elements.size === 0) {
        this.stop();
      }
    }

    /**
     * Start the countdown update cycle
     */
    start() {
      if (this.isRunning) return;

      this.isRunning = true;
      this.updateInterval = setInterval(this.update, CONFIG.UPDATE_INTERVAL);
      this.log("Countdown updates started");
    }

    /**
     * Stop the countdown update cycle
     */
    stop() {
      if (!this.isRunning) return;

      this.isRunning = false;
      if (this.updateInterval) {
        clearInterval(this.updateInterval);
        this.updateInterval = null;
      }
      this.log("Countdown updates stopped");
    }

    /**
     * Handle page visibility changes to optimize performance
     */
    setupVisibilityHandler() {
      if (typeof document.hidden !== "undefined") {
        document.addEventListener(
          "visibilitychange",
          this.handleVisibilityChange
        );
      }
    }

    /**
     * Handle visibility change events
     */
    handleVisibilityChange() {
      if (document.hidden) {
        this.log("Page hidden, stopping updates");
        this.stop();
      } else {
        this.log("Page visible, resuming updates");
        this.update(); // Update immediately
        this.start();
      }
    }

    /**
     * Cleanup and destroy the countdown instance
     */
    destroy() {
      this.stop();
      this.elements.clear();
      document.removeEventListener(
        "visibilitychange",
        this.handleVisibilityChange
      );
      this.log("EventCountdown destroyed");
    }

    /**
     * Logging methods
     */
    log(...args) {
      if (CONFIG.DEBUG) {
        console.log("[EventCountdown]", ...args);
      }
    }

    warn(...args) {
      console.warn("[EventCountdown]", ...args);
    }

    error(...args) {
      console.error("[EventCountdown]", ...args);
    }
  }

  // Create global instance
  const countdownManager = new EventCountdown();

  // Auto-initialize when DOM is ready
  function autoInit() {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () =>
        countdownManager.init()
      );
    } else {
      countdownManager.init();
    }
  }

  // Expose public API
  window.EventCountdown = {
    // Initialize countdowns (for manual initialization or after dynamic content)
    init: (root) => countdownManager.init(root),

    // Update all countdowns immediately
    update: () => countdownManager.update(),

    // Start/stop automatic updates
    start: () => countdownManager.start(),
    stop: () => countdownManager.stop(),

    // Cleanup
    destroy: () => countdownManager.destroy(),

    // Legacy compatibility
    initEventCountdowns: (root) => countdownManager.init(root),
  };

  // Auto-initialize
  autoInit();

  // Export for module systems
  if (typeof module !== "undefined" && module.exports) {
    module.exports = window.EventCountdown;
  }
})(window, document);
