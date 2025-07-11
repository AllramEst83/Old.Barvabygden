window.addEventListener("DOMContentLoaded", () => {
  // Function to show spinner
  function showSpinner() {
    const spinnerOverlay = document.getElementById("spinner-overlay");
    if (spinnerOverlay) {
      spinnerOverlay.classList.remove("hide");
      spinnerOverlay.style.display = "flex";
    }
  }

  // Function to hide spinner
  function hideSpinner() {
    const spinnerOverlay = document.getElementById("spinner-overlay");
    if (spinnerOverlay) {
      spinnerOverlay.classList.add("hide");
      setTimeout(() => {
        spinnerOverlay.style.display = "none";
      }, 500);
    }
  }

  // Function to load events with improved error handling
  async function loadEvents() {
    const eventsContainer = document.getElementById("events-container");
    if (!eventsContainer) {
      console.warn("Events container not found");
      return;
    }

    showSpinner();

    try {
      const response = await fetch("./assets/pages/events.html");

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.text();

      // Insert the events content
      eventsContainer.innerHTML = data;

      // Reinitialize lightbox functionality for all galleries on the page
      if (window.LightboxManager) {
        // First ensure lightbox is initialized
        window.LightboxManager.init();
        // Then attach listeners to all galleries on the entire page
        window.LightboxManager.attachListeners(document);
      }

      console.log("Events loaded successfully");
    } catch (error) {
      console.error("Error fetching events:", error);
      eventsContainer.innerHTML = `
        <div class="col-12">
          <div class="alert alert-warning text-center" role="alert">
        <h4 class="alert-heading">Det gick inte att ladda evenemang</h4>
        <p class="mb-0">Vänligen uppdatera sidan för att försöka igen.</p>
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

  // Load events after the page has fully loaded
  window.addEventListener("load", loadEvents);

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

  // Load events when the page loads
  loadEvents();

  // Setup mobile navbar auto-close
  setupMobileNavbarAutoClose();
});
