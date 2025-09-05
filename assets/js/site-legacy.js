// ES5-compatible version of site.js for older browsers
(function () {
  "use strict";

  // Check if we're in an older browser
  var isOldBrowser = !window.fetch || !window.Promise || !Array.from;

  // Loading state tracker
  var loadingState = {
    activeLoaders: 0,
    isLoading: false,
    maxRetries: 3,
    retryDelay: 1000,
  };

  // Function to show spinner
  function showSpinner() {
    var spinnerOverlay = document.getElementById("spinner-overlay");
    if (spinnerOverlay && !loadingState.isLoading) {
      loadingState.isLoading = true;
      spinnerOverlay.classList.remove("hide");
      spinnerOverlay.style.display = "flex";
    }
    loadingState.activeLoaders++;
  }

  // Function to hide spinner
  function hideSpinner() {
    loadingState.activeLoaders--;
    if (loadingState.activeLoaders <= 0) {
      var spinnerOverlay = document.getElementById("spinner-overlay");
      if (spinnerOverlay && loadingState.isLoading) {
        loadingState.isLoading = false;
        loadingState.activeLoaders = 0;
        spinnerOverlay.classList.add("hide");
        setTimeout(function () {
          spinnerOverlay.style.display = "none";
        }, 500);
      }
    }
  }

  // Utility function to retry failed requests (simplified for older browsers)
  function retryRequest(requestFn, maxRetries, delay, callback) {
    maxRetries = maxRetries || loadingState.maxRetries;
    delay = delay || loadingState.retryDelay;
    var attempt = 1;

    function tryRequest() {
      try {
        requestFn(function (error, result) {
          if (error && attempt < maxRetries) {
            console.warn(
              "Request attempt " + attempt + " failed:",
              error.message
            );
            attempt++;
            setTimeout(tryRequest, delay);
            delay *= 1.5; // Exponential backoff
          } else if (error) {
            callback(error);
          } else {
            callback(null, result);
          }
        });
      } catch (ex) {
        if (attempt < maxRetries) {
          attempt++;
          setTimeout(tryRequest, delay);
          delay *= 1.5;
        } else {
          callback(ex);
        }
      }
    }

    tryRequest();
  }

  // Function to load newsletter with fallback for older browsers
  function loadNewsLetter() {
    var archiveContainers = document.getElementById("archiveAccordion");
    if (!archiveContainers) {
      console.warn("Newsletter container (archiveAccordion) not found");
      return;
    }

    showSpinner();
    console.log("Starting newsletter loading...");

    function makeRequest(callback) {
      if (window.fetch) {
        // Modern browsers
        fetch("./assets/pages/newsletter.html")
          .then(function (response) {
            if (!response.ok) {
              throw new Error(
                "Failed to load newsletter: HTTP " +
                  response.status +
                  " - " +
                  response.statusText
              );
            }
            return response.text();
          })
          .then(function (content) {
            if (!content || content.trim().length === 0) {
              throw new Error("Newsletter content is empty");
            }
            callback(null, content);
          })
          .catch(function (error) {
            callback(error);
          });
      } else {
        // Fallback for older browsers
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "./assets/pages/newsletter.html");
        xhr.onload = function () {
          if (xhr.status >= 200 && xhr.status < 300) {
            var content = xhr.responseText;
            if (!content || content.trim().length === 0) {
              callback(new Error("Newsletter content is empty"));
            } else {
              callback(null, content);
            }
          } else {
            callback(
              new Error(
                "Failed to load newsletter: HTTP " +
                  xhr.status +
                  " - " +
                  xhr.statusText
              )
            );
          }
        };
        xhr.onerror = function () {
          callback(new Error("Network request failed"));
        };
        xhr.send();
      }
    }

    retryRequest(
      makeRequest,
      loadingState.maxRetries,
      loadingState.retryDelay,
      function (error, data) {
        hideSpinner();

        if (error) {
          console.error("Error loading newsletter after retries:", error);
          // Remove loading placeholder if it exists
          var loadingPlaceholder = archiveContainers.querySelector(
            ".newsletter-loading"
          );
          if (loadingPlaceholder) {
            loadingPlaceholder.remove();
          }

          archiveContainers.innerHTML =
            '<div class="col-12">' +
            '<div class="alert alert-danger text-center" role="alert">' +
            '<h4 class="alert-heading">' +
            '<i class="fas fa-exclamation-triangle me-2"></i>' +
            "Det gick inte att ladda nyhetsbrevet" +
            "</h4>" +
            '<p class="mb-2">Ett fel uppstod vid laddning av Barva-Bladet arkivet efter flera försök.</p>' +
            '<small class="text-muted">Fel: ' +
            error.message +
            "</small>" +
            "<hr>" +
            '<button class="btn btn-outline-danger btn-sm mt-2" onclick="window.location.reload()">' +
            '<i class="fas fa-redo me-1"></i>' +
            "Ladda om sidan" +
            "</button>" +
            "</div>" +
            "</div>";
          return;
        }

        // Remove loading placeholder and insert content
        var loadingPlaceholder = archiveContainers.querySelector(
          ".newsletter-loading"
        );
        if (loadingPlaceholder) {
          loadingPlaceholder.remove();
        }

        archiveContainers.innerHTML = data;

        // Trigger a custom event to notify other components
        triggerCustomEvent("newsletterLoaded", {
          container: archiveContainers,
        });
      }
    );
  }

  // Function to load events with fallback for older browsers
  function loadEvents() {
    var eventsContainer = document.getElementById("events-container");
    if (!eventsContainer) {
      console.warn("Events container not found");
      return;
    }

    showSpinner();
    console.log("Starting events loading...");

    function makeRequest(callback) {
      if (window.fetch) {
        // Modern browsers
        fetch("./assets/pages/events.html")
          .then(function (response) {
            if (!response.ok) {
              throw new Error(
                "Failed to load events: HTTP " +
                  response.status +
                  " - " +
                  response.statusText
              );
            }
            return response.text();
          })
          .then(function (content) {
            if (!content || content.trim().length === 0) {
              throw new Error("Events content is empty");
            }
            callback(null, content);
          })
          .catch(function (error) {
            callback(error);
          });
      } else {
        // Fallback for older browsers
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "./assets/pages/events.html");
        xhr.onload = function () {
          if (xhr.status >= 200 && xhr.status < 300) {
            var content = xhr.responseText;
            if (!content || content.trim().length === 0) {
              callback(new Error("Events content is empty"));
            } else {
              callback(null, content);
            }
          } else {
            callback(
              new Error(
                "Failed to load events: HTTP " +
                  xhr.status +
                  " - " +
                  xhr.statusText
              )
            );
          }
        };
        xhr.onerror = function () {
          callback(new Error("Network request failed"));
        };
        xhr.send();
      }
    }

    retryRequest(
      makeRequest,
      loadingState.maxRetries,
      loadingState.retryDelay,
      function (error, data) {
        hideSpinner();

        if (error) {
          console.error("Error loading events after retries:", error);
          // Remove loading placeholder if it exists
          var loadingPlaceholder =
            eventsContainer.querySelector(".events-loading");
          if (loadingPlaceholder) {
            loadingPlaceholder.remove();
          }

          eventsContainer.innerHTML =
            '<div class="col-12">' +
            '<div class="alert alert-danger text-center" role="alert">' +
            '<h4 class="alert-heading">' +
            '<i class="fas fa-exclamation-triangle me-2"></i>' +
            "Det gick inte att ladda evenemang" +
            "</h4>" +
            '<p class="mb-2">Ett fel uppstod vid laddning av evenemang efter flera försök.</p>' +
            '<small class="text-muted">Fel: ' +
            error.message +
            "</small>" +
            "<hr>" +
            '<button class="btn btn-outline-danger btn-sm mt-2" onclick="window.location.reload()">' +
            '<i class="fas fa-redo me-1"></i>' +
            "Ladda om sidan" +
            "</button>" +
            "</div>" +
            "</div>";
          return;
        }

        // Remove loading placeholder and insert content
        var loadingPlaceholder =
          eventsContainer.querySelector(".events-loading");
        if (loadingPlaceholder) {
          loadingPlaceholder.remove();
        }

        // Insert the events content
        eventsContainer.innerHTML = data;

        // Reinitialize lightbox functionality for all galleries on the page
        if (window.LightboxManager) {
          // First ensure lightbox is initialized
          window.LightboxManager.init();
          // Then attach listeners to all galleries on the entire page
          window.LightboxManager.attachListeners(document);
        }

        // Trigger a custom event to notify other components
        triggerCustomEvent("eventsLoaded", {
          container: eventsContainer,
        });
      }
    );
  }

  // Smooth scroll fallback for older browsers
  function smoothScrollTo(target) {
    var targetElement = document.querySelector(target);
    if (!targetElement) return;

    var headerHeight =
      document.querySelector(".site-header") &&
      document.querySelector(".site-header").offsetHeight
        ? document.querySelector(".site-header").offsetHeight
        : 0;
    var targetY = targetElement.offsetTop - headerHeight;

    if (window.smoothScrollTo) {
      // Use polyfill
      window.smoothScrollTo(targetY, 600);
    } else if ("scrollBehavior" in document.documentElement.style) {
      // Native smooth scroll
      window.scrollTo({
        top: targetY,
        behavior: "smooth",
      });
    } else {
      // Fallback to instant scroll
      window.scrollTo(0, targetY);
    }
  }

  // Cross-browser custom event dispatching
  function triggerCustomEvent(eventName, detail) {
    var event;
    detail = detail || {};

    if (typeof window.CustomEvent === "function") {
      // Modern browsers
      event = new CustomEvent(eventName, { detail: detail });
    } else {
      // IE/older browsers fallback
      event = document.createEvent("CustomEvent");
      event.initCustomEvent(eventName, false, false, detail);
    }

    window.dispatchEvent(event);
  }

  // Cross-browser event listener helper
  function addEventListenerCompat(element, event, handler) {
    if (element.addEventListener) {
      element.addEventListener(event, handler);
    } else if (element.attachEvent) {
      element.attachEvent("on" + event, handler);
    }
  }

  // Function to auto-close mobile navbar when link is clicked
  function setupMobileNavbarAutoClose() {
    var navbarToggler = document.querySelector(".navbar-toggler");
    var navbarCollapse = document.querySelector("#mainNavbar");
    var navLinks = document.querySelectorAll("#mainNavbar .nav-link");

    if (!navbarToggler || !navbarCollapse || !navLinks.length) {
      return;
    }

    // Add click event to all navigation links
    for (var i = 0; i < navLinks.length; i++) {
      (function (link) {
        addEventListenerCompat(link, "click", function (event) {
          // Only close if navbar is currently open (visible on mobile)
          if (
            navbarCollapse.classList &&
            navbarCollapse.classList.contains("show")
          ) {
            // Use Bootstrap's collapse method to close the navbar if available
            if (window.bootstrap && window.bootstrap.Collapse) {
              var bsCollapse = new bootstrap.Collapse(navbarCollapse, {
                toggle: false,
              });
              bsCollapse.hide();
            } else {
              // Fallback - remove the show class manually
              navbarCollapse.classList.remove("show");
            }
          }
        });
      })(navLinks[i]);
    }
  }

  // Function to highlight navbar items based on scroll position
  function setupNavbarActiveHighlighting() {
    var navLinks = document.querySelectorAll("#mainNavbar .nav-link");
    var sections = [];

    // Build sections array from nav links
    for (var i = 0; i < navLinks.length; i++) {
      var link = navLinks[i];
      var href = link.getAttribute("href");
      if (href && href.indexOf("#") === 0) {
        var targetId = href.substring(1);
        var targetElement = document.getElementById(targetId);
        if (targetElement) {
          sections.push({
            id: targetId,
            element: targetElement,
            navLink: link,
          });
        }
      }
    }

    if (sections.length === 0) return;

    function updateActiveNavLink() {
      var headerHeight =
        document.querySelector(".site-header") &&
        document.querySelector(".site-header").offsetHeight
          ? document.querySelector(".site-header").offsetHeight
          : 0;
      var scrollPosition = window.pageYOffset + headerHeight + 100; // Add some offset

      var activeSection = null;

      // Find the current section
      for (var i = sections.length - 1; i >= 0; i--) {
        var section = sections[i];
        if (scrollPosition >= section.element.offsetTop) {
          activeSection = section;
          break;
        }
      }

      // Update active states
      for (var j = 0; j < sections.length; j++) {
        var section = sections[j];
        if (section === activeSection) {
          section.navLink.classList.add("active");
        } else {
          section.navLink.classList.remove("active");
        }
      }
    }

    // Initial update
    updateActiveNavLink();

    // Update on scroll with throttling
    var scrollTimeout;
    addEventListenerCompat(window, "scroll", function () {
      if (scrollTimeout) {
        clearTimeout(scrollTimeout);
      }
      scrollTimeout = setTimeout(updateActiveNavLink, 10);
    });

    // Update active state when clicking nav links
    for (var k = 0; k < navLinks.length; k++) {
      (function (link) {
        addEventListenerCompat(link, "click", function () {
          // Remove active from all links
          for (var m = 0; m < navLinks.length; m++) {
            navLinks[m].classList.remove("active");
          }
          // Add active to clicked link
          link.classList.add("active");
        });
      })(navLinks[k]);
    }
  }

  // Enhanced navigation handling for older browsers
  function initializeNavigation() {
    var navLinks = document.querySelectorAll('.nav-link[href^="#"]');

    for (var i = 0; i < navLinks.length; i++) {
      (function (link) {
        addEventListenerCompat(link, "click", function (e) {
          e.preventDefault();
          var target = link.getAttribute("href");
          smoothScrollTo(target);

          // Remove focus from the link to remove border/unselect
          if (link.blur) {
            link.blur();
          }
        });
      })(navLinks[i]);
    }
  }

  // Load content function similar to site.js
  function loadContent() {
    console.log("Page loaded, starting content loading...");

    var loadingTasks = [];
    var completedTasks = 0;
    var totalTasks = 0;

    // Check what content needs to be loaded and create tasks
    if (document.getElementById("events-container")) {
      totalTasks++;
      loadingTasks.push({
        name: "events",
        execute: function () {
          loadEvents();
        },
      });
    }

    if (document.getElementById("archiveAccordion")) {
      totalTasks++;
      loadingTasks.push({
        name: "newsletter",
        execute: function () {
          loadNewsLetter();
        },
      });
    }

    // If no content to load, trigger completion immediately
    if (totalTasks === 0) {
      triggerCustomEvent("allContentLoaded");
      return;
    }

    // Execute all loading tasks
    for (var i = 0; i < loadingTasks.length; i++) {
      try {
        loadingTasks[i].execute();
      } catch (error) {
        console.error(
          "Loading task " + loadingTasks[i].name + " failed:",
          error
        );
      }
    }

    // Since we can't easily track completion of async operations in legacy mode,
    // we'll use a timeout to trigger the completion event
    // This is not ideal but works for legacy browser compatibility
    setTimeout(function () {
      console.log("✅ - All content loading completed (legacy mode)");
      triggerCustomEvent("allContentLoaded");
    }, 3000); // Give 3 seconds for content to load
  }

  // Initialize everything when DOM is ready
  function initialize() {
    console.log("Initializing site for browser compatibility...");

    if (isOldBrowser) {
      console.log("Older browser detected, using compatibility mode");
    }

    // Initialize navigation
    initializeNavigation();

    // Setup mobile navbar auto-close
    setupMobileNavbarAutoClose();

    // Setup navbar active highlighting based on scroll position
    setupNavbarActiveHighlighting();

    // Initialize lightbox if available
    if (
      window.LightboxManager &&
      typeof window.LightboxManager.init === "function"
    ) {
      window.LightboxManager.init();
    }

    // Set up custom event listeners (similar to site.js)
    addEventListenerCompat(window, "eventsLoaded", function (event) {
      console.log("✅ - Events loaded successfully.");
      // Reinitialize countdowns for dynamically loaded events
      if (window.EventCountdown && event.detail && event.detail.container) {
        if (typeof window.EventCountdown.init === "function") {
          window.EventCountdown.init(event.detail.container);
        } else if (
          window.EventCountdown.prototype &&
          window.EventCountdown.prototype.init
        ) {
          // For class-based implementations
          var countdown = new window.EventCountdown();
          countdown.init(event.detail.container);
        }
      }
    });

    addEventListenerCompat(window, "newsletterLoaded", function (event) {
      console.log("✅ - Newsletter loaded successfully.");
    });

    addEventListenerCompat(window, "allContentLoaded", function (event) {
      console.log("✅ - All content loaded successfully.");
    });

    // Load content when window loads (similar to site.js load event)
    if (window.addEventListener) {
      window.addEventListener("load", loadContent);
    } else {
      window.attachEvent("onload", loadContent);
    }
  }

  // Wait for DOM to be ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize);
  } else {
    initialize();
  }
})();
