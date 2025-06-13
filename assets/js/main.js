// Toggle visibility of speech buttons
function toggleSpeechOptions() {
  const options = document.getElementById("speechOptions");
  const isVisible = options.style.display === "block";
  options.style.display = isVisible ? "none" : "block";
}

// Speak the Midsommar text aloud
function speakMidsommar() {
  if (!("speechSynthesis" in window)) {
    alert("Din webbläsare stödjer inte uppläsning av text.");
    return;
  }

  const text = `
    Midsommarafton 2025 – Barva Bygdegård.
    Ett traditionellt firande med lotterier, chokladhjul, kaffestuga och skytte med mera.

    Torsdag nittonde juni.
    Klockan femton till arton: Vi börjar firandet genom traditionen att klä majstången.
    Alla är hjärtligt välkomna.

    Midsommarafton – Fredag.
    Klockan nio: Vi klär klart majstången tillsammans.
    För de som hjälper till bjuds det på kaffe och smörgås.
    Barnen får glass.

    Klockan fjorton: Firandet öppnar.
    Klockan femton: Stångresning med dans runt majstången.

    Uppträdande av Kent Lindén.

    Midsommardagen – Lördag.
    Klockan tretton: Friluftsgudstjänst vid Hembygdsmuseumet.

    Fri entré.
  `;

  const utterance = new SpeechSynthesisUtterance(text);
  utterance.lang = "sv-SE";
  utterance.rate = 0.95;

  const volumeSlider = document.getElementById("volumeControl");
  if (volumeSlider) {
    utterance.volume = parseFloat(volumeSlider.value);
  }

  speechSynthesis.cancel();
  speechSynthesis.speak(utterance);
}

// Stop reading
function stopSpeech() {
  if ("speechSynthesis" in window) {
    speechSynthesis.cancel();
  }
}

window.addEventListener("DOMContentLoaded", () => {
  // Hide toggle button if speechSynthesis unsupported
  if (!("speechSynthesis" in window)) {
    const toggle = document.querySelector(".speech-toggle");
    if (toggle) {
      toggle.style.display = "none";
    }
  }
});

window.addEventListener("beforeunload", () => {
  // Stop speech if page is reloaded or tab/browser is closed
  if ("speechSynthesis" in window) {
    speechSynthesis.cancel();
  }
});
