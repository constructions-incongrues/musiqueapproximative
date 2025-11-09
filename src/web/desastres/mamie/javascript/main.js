console.log("[desastres/mamie] loaded");

// Play sound effect
const audio = new Audio("https://allocestmamie.partouze-cagoule.fr/assets/audio/jingle.mp3");
audio.volume = window.DesastreOptions.mamie.volume;
audio.playbackRate = 0.5 + Math.random();  // Vitesse de lecture aleatoire entre 0.5x et 1.5x

// Tenter de jouer le son automatiquement
const playPromise = audio.play();

if (playPromise !== undefined) {
  playPromise
    .then(() => {
      console.log("[desastres/mamie] Audio playing automatically");
    })
    .catch(error => {
      console.warn("[desastres/mamie] Autoplay blocked by browser:", error);

      // Fallback: jouer au premier clic/interaction utilisateur
      const playOnInteraction = () => {
        audio.play()
          .then(() => {
            console.log("[desastres/mamie] Audio playing after user interaction");
            // Retirer les listeners apres le premier play
            document.removeEventListener("click", playOnInteraction);
            document.removeEventListener("keydown", playOnInteraction);
            document.removeEventListener("touchstart", playOnInteraction);
          })
          .catch(e => console.error("[desastres/mamie] Play error:", e));
      };

      // Ecouter plusieurs types d'interactions
      document.addEventListener("click", playOnInteraction, { once: true });
      document.addEventListener("keydown", playOnInteraction, { once: true });
      document.addEventListener("touchstart", playOnInteraction, { once: true });

      console.log("[desastres/mamie] Waiting for user interaction to play audio...");
    });
}