window.addEventListener("DOMContentLoaded", () => {
  // Show the spinner overlay on page load
  window.addEventListener("load", () => {
    const spinnerOverlay = document.getElementById("spinner-overlay");
    setTimeout(() => {
      spinnerOverlay.classList.add("hide");
      setTimeout(() => {
        spinnerOverlay.style.display = "none";
      }, 500);
    }, 1000);
  });

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();

      const targetId = this.getAttribute("href").substring(1);
      const targetElement = document.getElementById(targetId);

      if (targetElement) {
        const headerHeight =
          document.querySelector(".site-header").offsetHeight;
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
});
