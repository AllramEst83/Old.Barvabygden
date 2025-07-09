document.addEventListener("DOMContentLoaded", () => {
  const lightbox = document.getElementById("lightbox");
  const lightboxImg = document.getElementById("lightbox-img");
  const closeBtn = document.getElementById("close-btn");
  const nextBtn = document.getElementById("next-btn");
  const prevBtn = document.getElementById("prev-btn");

  let currentGallery = [];
  let currentIndex = 0;

  function showImage(index) {
    lightboxImg.src = currentGallery[index].dataset.full;
    lightbox.classList.remove("hidden");
    document.body.classList.add("lightbox-open");
  }

  function closeLightbox() {
    lightbox.classList.add("hidden");
    document.body.classList.remove("lightbox-open");
  }

  document.querySelectorAll(".gallery img").forEach((thumb) => {
    thumb.addEventListener("click", () => {
      // Find the parent gallery of the clicked image
      const gallery = thumb.closest(".gallery");
      // Get only the images in this gallery
      currentGallery = Array.from(gallery.querySelectorAll("img"));
      // Get index of clicked image within the gallery
      currentIndex = currentGallery.indexOf(thumb);
      showImage(currentIndex);
    });
  });

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
    if (lightbox.classList.contains("hidden")) return;
    if (e.key === "Escape") closeLightbox();
    if (e.key === "ArrowRight") nextBtn.click();
    if (e.key === "ArrowLeft") prevBtn.click();
  });
});
