// Lightbox functionality that can be initialized multiple times
window.LightboxManager = (function () {
  let lightbox, lightboxImg, closeBtn, nextBtn, prevBtn;
  let currentGallery = [];
  let currentIndex = 0;
  let isInitialized = false;

  function showImage(index) {
    if (!lightbox || !lightboxImg) return;
    lightboxImg.src = currentGallery[index].dataset.full;
    lightbox.classList.remove("hidden");
    document.body.classList.add("lightbox-open");
  }

  function closeLightbox() {
    if (!lightbox) return;
    lightbox.classList.add("hidden");
    document.body.classList.remove("lightbox-open");
  }

  function initializeLightboxElements() {
    if (isInitialized) return;

    lightbox = document.getElementById("lightbox");
    lightboxImg = document.getElementById("lightbox-img");
    closeBtn = document.getElementById("close-btn");
    nextBtn = document.getElementById("next-btn");
    prevBtn = document.getElementById("prev-btn");

    if (!lightbox || !lightboxImg || !closeBtn || !nextBtn || !prevBtn) {
      console.warn("Lightbox elements not found");
      return;
    }

    // Add event listeners for lightbox controls
    closeBtn.addEventListener("click", closeLightbox);

    nextBtn.addEventListener("click", () => {
      currentIndex = (currentIndex + 1) % currentGallery.length;
      showImage(currentIndex);
    });

    prevBtn.addEventListener("click", () => {
      currentIndex =
        (currentIndex - 1 + currentGallery.length) % currentGallery.length;
      showImage(currentIndex);
    });

    lightbox.addEventListener("click", (e) => {
      if (e.target === lightbox) closeLightbox();
    });

    document.addEventListener("keydown", (e) => {
      if (!lightbox || lightbox.classList.contains("hidden")) return;
      if (e.key === "Escape") closeLightbox();
      if (e.key === "ArrowRight") nextBtn.click();
      if (e.key === "ArrowLeft") prevBtn.click();
    });

    isInitialized = true;
  }

  function attachGalleryListeners(container = document) {
    // Initialize lightbox elements if not already done
    initializeLightboxElements();

    // Attach click listeners to gallery images within the specified container
    container.querySelectorAll(".gallery img").forEach((thumb) => {
      // Remove existing listeners to prevent duplicates
      thumb.removeEventListener("click", handleImageClick);
      thumb.addEventListener("click", handleImageClick);
    });
  }

  function handleImageClick(event) {
    const thumb = event.target;
    // Find the parent gallery of the clicked image
    const gallery = thumb.closest(".gallery");
    if (!gallery) return;

    // Get only the images in this gallery
    currentGallery = Array.from(gallery.querySelectorAll("img"));
    // Get index of clicked image within the gallery
    currentIndex = currentGallery.indexOf(thumb);
    showImage(currentIndex);
  }

  // Public API
  return {
    init: initializeLightboxElements,
    attachListeners: attachGalleryListeners,
    reinitialize: function (container) {
      attachGalleryListeners(container);
    },
  };
})();

document.addEventListener("DOMContentLoaded", () => {
  // Initialize lightbox for existing content
  window.LightboxManager.init();
  window.LightboxManager.attachListeners();
});
