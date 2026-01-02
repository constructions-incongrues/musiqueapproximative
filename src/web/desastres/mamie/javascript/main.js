console.log("[desastres/mamie] Loaded");

// Verifier que les options sont presentes
if (!window.DesastreOptions || !window.DesastreOptions.mamie) {
  console.error(
    "[desastres/mamie] ERROR: Options not found in window.DesastreOptions.mamie",
  );
  console.log("[desastres/mamie] Available options:", window.DesastreOptions);
} else {
  console.log(
    "[desastres/mamie] Options loaded:",
    window.DesastreOptions.mamie,
  );

  // Play sound effect
  const audioUrl =
    "https://allocestmamie.partouze-cagoule.fr/assets/audio/jingle.mp3";
  console.log("[desastres/mamie] Creating audio from:", audioUrl);

  const audio = new Audio(audioUrl);
  audio.volume = window.DesastreOptions.mamie.volume;
  audio.playbackRate = 0.5 + Math.random(); // Vitesse de lecture aleatoire entre 0.5x et 1.5x

  console.log(
    "[desastres/mamie] Audio settings - volume:",
    audio.volume,
    "playbackRate:",
    audio.playbackRate.toFixed(2),
  );

  // Tenter de jouer le son automatiquement
  console.log("[desastres/mamie] Attempting autoplay...");
  const playPromise = audio.play();

  if (playPromise !== undefined) {
    playPromise
      .then(() => {
        console.log("[desastres/mamie] ▶ Audio playing automatically");
      })
      .catch((error) => {
        console.warn(
          "[desastres/mamie] WARNING: Autoplay blocked by browser:",
          error.message,
        );

        // Fallback: jouer au premier clic/interaction utilisateur
        const playOnInteraction = () => {
          console.log(
            "[desastres/mamie] 👆 User interaction detected, playing audio",
          );
          audio
            .play()
            .then(() => {
              console.log(
                "[desastres/mamie] ▶ Audio playing after user interaction",
              );
              // Retirer les listeners apres le premier play
              document.removeEventListener("click", playOnInteraction);
              document.removeEventListener("keydown", playOnInteraction);
              document.removeEventListener("touchstart", playOnInteraction);
            })
            .catch((e) => console.error("[desastres/mamie] ✖ Play ERROR:", e));
        };

        // Ecouter plusieurs types d'interactions
        document.addEventListener("click", playOnInteraction, { once: true });
        document.addEventListener("keydown", playOnInteraction, { once: true });
        document.addEventListener("touchstart", playOnInteraction, {
          once: true,
        });

        console.log(
          "[desastres/mamie] Listeners registered for user interaction fallback",
        );
      });
  }

  // Ajouter des event listeners pour le debogage
  audio.addEventListener("loadstart", () =>
    console.log("[desastres/mamie] Audio loading..."),
  );
  audio.addEventListener("loadeddata", () =>
    console.log("[desastres/mamie] Audio loaded"),
  );
  audio.addEventListener("ended", () =>
    console.log("[desastres/mamie] ■ Audio ENDED"),
  );
  audio.addEventListener("error", (e) =>
    console.error("[desastres/mamie] ✖ Audio ERROR:", e),
  );
}
