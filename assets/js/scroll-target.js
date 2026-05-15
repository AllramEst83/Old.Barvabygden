(function () {
  var target = sessionStorage.getItem("scrollTarget");
  if (!target) return;
  sessionStorage.removeItem("scrollTarget");

  function scrollToTarget() {
    var el = document.getElementById(target);
    if (!el) return;
    var navbarHeight = 90;
    var top = el.getBoundingClientRect().top + window.pageYOffset - navbarHeight;
    window.scrollTo({ top: top, behavior: "smooth" });
  }

  // Scroll after events are injected; fall back to window load
  window.addEventListener("eventsLoaded", function () {
    setTimeout(scrollToTarget, 100);
  });
  window.addEventListener("load", function () {
    setTimeout(scrollToTarget, 800);
  });
})();
