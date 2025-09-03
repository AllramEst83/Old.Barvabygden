window.addEventListener("DOMContentLoaded", () => {
  // Loading state tracker
  const loadingState = {
    activeLoaders: 0,
    isLoading: false,
    maxRetries: 3,
    retryDelay: 1000,
  };

  // Function to show spinner
  function showSpinner() {
    const spinnerOverlay = document.getElementById("spinner-overlay");
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
      const spinnerOverlay = document.getElementById("spinner-overlay");
      if (spinnerOverlay && loadingState.isLoading) {
        loadingState.isLoading = false;
        loadingState.activeLoaders = 0; // Reset to prevent negative values
        spinnerOverlay.classList.add("hide");
        setTimeout(() => {
          spinnerOverlay.style.display = "none";
        }, 500);
      }
    }
  }

  // Utility function to retry failed requests
  async function retryRequest(
    requestFn,
    maxRetries = loadingState.maxRetries,
    delay = loadingState.retryDelay
  ) {
    let lastError;

    for (let attempt = 1; attempt <= maxRetries; attempt++) {
      try {
        return await requestFn();
      } catch (error) {
        lastError = error;
        console.warn(`Request attempt ${attempt} failed:`, error.message);

        if (attempt < maxRetries) {
          console.log(`Retrying in ${delay}ms...`);
          await new Promise((resolve) => setTimeout(resolve, delay));
          delay *= 1.5; // Exponential backoff
        }
      }
    }

    throw lastError;
  }

  // Function to load newsletter with improved error handling and spinner coordination
  async function loadNewsLetter() {
    const archiveContainers = document.getElementById("archiveAccordion");
    if (!archiveContainers) {
      console.warn("Newsletter container (archiveAccordion) not found");
      return;
    }

    showSpinner();

    try {
      console.log("Starting newsletter loading...");

      const data = await retryRequest(async () => {
        const response = await fetch("./assets/pages/newsletter.html");

        if (!response.ok) {
          throw new Error(
            `Failed to load newsletter: HTTP ${response.status} - ${response.statusText}`
          );
        }

        const content = await response.text();

        // Validate that we received actual content
        if (!content || content.trim().length === 0) {
          throw new Error("Newsletter content is empty");
        }

        return content;
      });

      // Remove loading placeholder and insert content
      const loadingPlaceholder = archiveContainers.querySelector(
        ".newsletter-loading"
      );
      if (loadingPlaceholder) {
        loadingPlaceholder.remove();
      }

      archiveContainers.innerHTML = data;

      // Trigger a custom event to notify other components
      window.dispatchEvent(
        new CustomEvent("newsletterLoaded", {
          detail: { container: archiveContainers },
        })
      );
    } catch (error) {
      console.error("Error loading newsletter after retries:", error);

      // Remove loading placeholder if it exists
      const loadingPlaceholder = archiveContainers.querySelector(
        ".newsletter-loading"
      );
      if (loadingPlaceholder) {
        loadingPlaceholder.remove();
      }

      archiveContainers.innerHTML = `
        <div class="col-12">
          <div class="alert alert-danger text-center" role="alert">
            <h4 class="alert-heading">
              <i class="fas fa-exclamation-triangle me-2"></i>
              Det gick inte att ladda nyhetsbrevet
            </h4>
            <p class="mb-2">Ett fel uppstod vid laddning av Barva-Bladet arkivet efter flera försök.</p>
            <small class="text-muted">Fel: ${error.message}</small>
            <hr>
            <button class="btn btn-outline-danger btn-sm mt-2" onclick="window.location.reload()">
              <i class="fas fa-redo me-1"></i>
              Ladda om sidan
            </button>
          </div>
        </div>
      `;
    } finally {
      hideSpinner();
    }
  }

  // Function to load events with improved error handling and spinner coordination
  async function loadEvents() {
    const eventsContainer = document.getElementById("events-container");
    if (!eventsContainer) {
      console.warn("Events container not found");
      return;
    }

    showSpinner();

    try {
      console.log("Starting events loading...");

      const data = await retryRequest(async () => {
        const response = await fetch("./assets/pages/events.html");

        if (!response.ok) {
          throw new Error(
            `Failed to load events: HTTP ${response.status} - ${response.statusText}`
          );
        }

        const content = await response.text();

        // Validate that we received actual content
        if (!content || content.trim().length === 0) {
          throw new Error("Events content is empty");
        }

        return content;
      });

      // Remove loading placeholder and insert content
      const loadingPlaceholder =
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
      window.dispatchEvent(
        new CustomEvent("eventsLoaded", {
          detail: { container: eventsContainer },
        })
      );
    } catch (error) {
      console.error("Error loading events after retries:", error);

      // Remove loading placeholder if it exists
      const loadingPlaceholder =
        eventsContainer.querySelector(".events-loading");
      if (loadingPlaceholder) {
        loadingPlaceholder.remove();
      }

      eventsContainer.innerHTML = `
        <div class="col-12">
          <div class="alert alert-danger text-center" role="alert">
            <h4 class="alert-heading">
              <i class="fas fa-exclamation-triangle me-2"></i>
              Det gick inte att ladda evenemang
            </h4>
            <p class="mb-2">Ett fel uppstod vid laddning av evenemang efter flera försök.</p>
            <small class="text-muted">Fel: ${error.message}</small>
            <hr>
            <button class="btn btn-outline-danger btn-sm mt-2" onclick="window.location.reload()">
              <i class="fas fa-redo me-1"></i>
              Ladda om sidan
            </button>
          </div>
        </div>
      `;
    } finally {
      hideSpinner();
    }
  }

  // Function to auto-close mobile navbar when link is clicked
  function setupMobileNavbarAutoClose() {
    const navbarToggler = document.querySelector(".navbar-toggler");
    const navbarCollapse = document.querySelector("#mainNavbar");
    const navLinks = document.querySelectorAll("#mainNavbar .nav-link");

    if (!navbarToggler || !navbarCollapse || !navLinks.length) {
      return;
    }

    // Add click event to all navigation links
    navLinks.forEach((link) => {
      link.addEventListener("click", (event) => {
        // Only close if navbar is currently open (visible on mobile)
        if (navbarCollapse.classList.contains("show")) {
          // Use Bootstrap's collapse method to close the navbar
          const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
            toggle: false,
          });
          bsCollapse.hide();
        }
      });
    });
  }

  // Load events and newsletter after the page has fully loaded
  window.addEventListener("load", async function () {
    console.log("Page loaded, starting content loading...");

    // Load both events and newsletter in parallel
    const loadPromises = [
      loadEvents().catch((error) =>
        console.error("Events loading failed:", error)
      ),
      loadNewsLetter().catch((error) =>
        console.error("Newsletter loading failed:", error)
      ),
    ];

    try {
      await Promise.allSettled(loadPromises);
      console.log("✅ - All content loading completed");

      // Trigger a custom event when all content is loaded
      window.dispatchEvent(new CustomEvent("allContentLoaded"));
    } catch (error) {
      console.error("Error during content loading:", error);
    }
  });

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();

      const targetId = this.getAttribute("href").substring(1);
      const targetElement = document.getElementById(targetId);

      if (targetElement) {
        const headerHeight =
          document.querySelector(".site-header")?.offsetHeight || 0;
        const targetPosition = targetElement.offsetTop - headerHeight;

        window.scrollTo({
          top: targetPosition,
          behavior: "smooth",
        });

        // Remove focus from the link to remove border/unselect
        this.blur();
      }
    });
  });

  //Subscibe to custom events
  window.addEventListener("eventsLoaded", (event) => {
    console.log("✅ - Events loaded successfully.");
  });

  window.addEventListener("newsletterLoaded", (event) => {
    console.log("✅ - Newsletter loaded successfully.");
  });
  //Subscibe to custom events

  // Setup mobile navbar auto-close
  setupMobileNavbarAutoClose();
});
