window.addEventListener("DOMContentLoaded", () => {
  // FOTBOLLSMATCH - 2025
  const rows = document.querySelectorAll(".pane.match-schedule tbody tr");

  rows.forEach((row) => {
    if (!row.dataset.hoverBound) {
      row.addEventListener("mouseenter", () => {
        row.style.backgroundColor = "#eaf2d9";
      });

      row.addEventListener("mouseleave", () => {
        row.style.backgroundColor = "";
      });

      row.dataset.hoverBound = "true"; // Mark as bound
    }
  });
  // FOTBOLLSMATCH - 2025
});
