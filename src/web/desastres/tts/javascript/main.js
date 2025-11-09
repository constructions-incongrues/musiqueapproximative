console.log("[desastres/tts] Loaded");

// Verifier que les options TTS sont presentes
if (!window.DesastreOptions || !window.DesastreOptions.tts) {
  console.error("[desastres/tts] ERROR: Options not found in window.DesastreOptions.tts");
  console.log("[desastres/tts] Available options:", window.DesastreOptions);
} else {
  console.log("[desastres/tts] Options loaded:", window.DesastreOptions.tts);

  // Recuperer le texte a lire
  const selector = window.DesastreOptions.tts.selector || 'div.descriptif p';
  console.log("[desastres/tts] Using selector:", selector);

  const element = document.querySelector(selector);

  if (!element) {
    console.error("[desastres/tts] ERROR: Element not found with selector:", selector);
  } else {
  const text = element.textContent.trim();
  console.log("[desastres/tts] Text found (length:", text.length + " chars):", text.substring(0, 100) + (text.length > 100 ? "..." : ""));

  // Fonction pour lancer la synthese vocale
  function speakText() {
    if (text.length === 0) {
      console.warn("[desastres/tts] WARNING: No text to speak");
      return;
    }

    console.log("[desastres/tts] Preparing speech synthesis...");
    const utterance = new SpeechSynthesisUtterance(text);

    // Configuration optionnelle
    // Lang: utiliser la valeur configuree ou choisir aleatoirement parmi les langues francaises
    if (window.DesastreOptions.tts.lang !== undefined) {
      utterance.lang = window.DesastreOptions.tts.lang;
      console.log("[desastres/tts] Using configured lang:", utterance.lang);
    } else {
      const frenchLangs = ['fr-FR', 'fr-CA', 'fr-BE', 'fr-CH'];
      utterance.lang = frenchLangs[Math.floor(Math.random() * frenchLangs.length)];
      console.log("[desastres/tts] Using random lang:", utterance.lang);
    }

    // Rate: utiliser la valeur configuree ou generer une valeur aleatoire entre 0.5 et 2
    if (window.DesastreOptions.tts.rate !== undefined) {
      utterance.rate = window.DesastreOptions.tts.rate;
      console.log("[desastres/tts] Using configured rate:", utterance.rate);
    } else {
      utterance.rate = 0.5 + Math.random() * 1.5;  // Entre 0.5 et 2
      console.log("[desastres/tts] Using random rate:", utterance.rate.toFixed(2));
    }

    // Pitch: utiliser la valeur configuree ou generer une valeur aleatoire entre 0 et 2
    if (window.DesastreOptions.tts.pitch !== undefined) {
      utterance.pitch = window.DesastreOptions.tts.pitch;
      console.log("[desastres/tts] Using configured pitch:", utterance.pitch);
    } else {
      utterance.pitch = Math.random() * 2;  // Entre 0 et 2
      console.log("[desastres/tts] Using random pitch:", utterance.pitch.toFixed(2));
    }

    // Volume: utiliser la valeur configuree ou generer une valeur aleatoire entre 0.3 et 1
    if (window.DesastreOptions.tts.volume !== undefined) {
      utterance.volume = window.DesastreOptions.tts.volume;
      console.log("[desastres/tts] Using configured volume:", utterance.volume);
    } else {
      utterance.volume = 0.3 + Math.random() * 0.7;  // Entre 0.3 et 1
      console.log("[desastres/tts] Using random volume:", utterance.volume.toFixed(2));
    }

    // Selection d'une voix aleatoire si une liste est fournie
    if (window.DesastreOptions.tts.voices && Array.isArray(window.DesastreOptions.tts.voices) && window.DesastreOptions.tts.voices.length > 0) {
      const voiceNames = window.DesastreOptions.tts.voices;
      const randomVoiceName = voiceNames[Math.floor(Math.random() * voiceNames.length)];
      console.log("[desastres/tts] Selecting random voice from", voiceNames.length, "options:", randomVoiceName);

      // Attendre que les voix soient chargees
      const setVoice = () => {
        const availableVoices = speechSynthesis.getVoices();
        console.log("[desastres/tts] Available voices count:", availableVoices.length);

        const selectedVoice = availableVoices.find(voice => voice.name === randomVoiceName);

        if (selectedVoice) {
          utterance.voice = selectedVoice;
          console.log("[desastres/tts] Voice set:", selectedVoice.name, "(lang:", selectedVoice.lang + ")");
        } else {
          console.warn("[desastres/tts] WARNING: Voice not found:", randomVoiceName);
          console.log("[desastres/tts] Available voices:", availableVoices.map(v => v.name).join(", "));
        }
      };

      // Les voix peuvent ne pas etre immediatement disponibles
      if (speechSynthesis.getVoices().length > 0) {
        console.log("[desastres/tts] Voices already loaded");
        setVoice();
      } else {
        console.log("[desastres/tts] Waiting for voices to load...");
        speechSynthesis.addEventListener('voiceschanged', setVoice, { once: true });
      }
    } else {
      console.log("[desastres/tts] No voice list configured, using default voice");
    }

    utterance.onstart = () => {
      console.log("[desastres/tts] ▶ Speech STARTED");
    };

    utterance.onend = () => {
      console.log("[desastres/tts] ■ Speech ENDED");
    };

    utterance.onerror = (event) => {
      console.error("[desastres/tts] ✖ Speech ERROR:", event.error, "-", event);
    };

    speechSynthesis.speak(utterance);
    console.log("[desastres/tts] Speech synthesis queued");
  }

  // Tenter de parler automatiquement
  console.log("[desastres/tts] Attempting autoplay...");
  try {
    speakText();
  } catch (error) {
    console.warn("[desastres/tts] WARNING: Autoplay failed:", error);
  }

  // Fallback: attendre une interaction utilisateur
  const speakOnInteraction = () => {
    console.log("[desastres/tts] 👆 User interaction detected, triggering speech");
    speakText();

    // Retirer les listeners
    document.removeEventListener("click", speakOnInteraction);
    document.removeEventListener("keydown", speakOnInteraction);
    document.removeEventListener("touchstart", speakOnInteraction);
  };

  // Ecouter les interactions utilisateur
  document.addEventListener("click", speakOnInteraction, { once: true });
  document.addEventListener("keydown", speakOnInteraction, { once: true });
  document.addEventListener("touchstart", speakOnInteraction, { once: true });

  console.log("[desastres/tts] Listeners registered for user interaction fallback");
  }
}