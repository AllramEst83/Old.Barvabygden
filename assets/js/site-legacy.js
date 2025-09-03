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
          console.error("Failed to load newsletter after retries:", error);
          var loadingPlaceholder = archiveContainers.querySelector(
            ".newsletter-loading"
          );
          if (loadingPlaceholder) {
            loadingPlaceholder.innerHTML =
              '<div class="text-center py-5">' +
              '<div class="alert alert-warning mb-3">' +
              "<h6>Problem med att ladda arkivet</h6>" +
              '<p class="mb-0">Arkivet kunde inte laddas just nu. Försök igen senare.</p>' +
              "</div>" +
              "</div>";
          }
          return;
        }

        // Remove loading placeholder and insert content
        var loadingPlaceholder = archiveContainers.querySelector(
          ".newsletter-loading"
        );
        if (loadingPlaceholder) {
          loadingPlaceholder.remove();
        }

        archiveContainers.innerHTML += data;
        console.log("Newsletter loaded successfully");

        // Initialize lightbox for newly loaded content
        if (
          window.LightboxManager &&
          typeof window.LightboxManager.init === "function"
        ) {
          window.LightboxManager.init();
        }
      }
    );
  }

  // Smooth scroll fallback for older browsers
  function smoothScrollTo(target) {
    var targetElement = document.querySelector(target);
    if (!targetElement) return;

    var targetY = targetElement.offsetTop;

    if (window.smoothScrollTo) {
      // Use polyfill
      window.smoothScrollTo(targetY, 600);
    } else if ("scrollBehavior" in document.documentElement.style) {
      // Native smooth scroll
      targetElement.scrollIntoView({ behavior: "smooth" });
    } else {
      // Fallback to instant scroll
      window.scrollTo(0, targetY);
    }
  }

  // Enhanced navigation handling for older browsers
  function initializeNavigation() {
    var navLinks = document.querySelectorAll('.nav-link[href^="#"]');

    for (var i = 0; i < navLinks.length; i++) {
      (function (link) {
        link.addEventListener("click", function (e) {
          e.preventDefault();
          var target = link.getAttribute("href");
          smoothScrollTo(target);
        });
      })(navLinks[i]);
    }
  }

  // Initialize everything when DOM is ready
  function initialize() {
    console.log("Initializing site for browser compatibility...");

    if (isOldBrowser) {
      console.log("Older browser detected, using compatibility mode");
    }

    initializeNavigation();

    // Load newsletter if container exists
    if (document.getElementById("archiveAccordion")) {
      loadNewsLetter();
    }

    // Initialize lightbox if available
    if (
      window.LightboxManager &&
      typeof window.LightboxManager.init === "function"
    ) {
      window.LightboxManager.init();
    }
  }

  // Wait for DOM to be ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialize);
  } else {
    initialize();
  }
})();
