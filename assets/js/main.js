function toggleSpeechOptions() {
  const options = document.getElementById("speechOptions");
  const isVisible = options.style.display === "block";
  options.style.display = isVisible ? "none" : "block";
}

function speakMidsommar() {
  const text = `
      Midsommarafton. Fri entré – Barva bygdegård.
      Ett traditionellt firande med lotterier, chokladhjul, kaffestuga, skytte med mera.
      Nytt för i år. Torsdag nittonde juni klockan femton till arton.
      Vi börjar firandet genom traditionen att klä majstången. Alla är hjärtligt välkomna!
      Midsommarafton – Fredag. Från klockan nio, vi klär klart majstången tillsammans.
      Kaffe, smörgås och glass till barnen erbjuds till alla som hjälper till.
      Klockan fjorton öppnar firandet.
      Klockan femton stångresning med dans runt majstången.
      Uppträdande av Kent Lindén.
      Midsommardagen: Friluftsgudstjänst klockan tretton vid Hembygds Museum.
    `;
  const utterance = new SpeechSynthesisUtterance(text);
  utterance.lang = "sv-SE";
  utterance.rate = 0.95;
  speechSynthesis.cancel();
  speechSynthesis.speak(utterance);
}

function stopSpeech() {
  speechSynthesis.cancel();
}
