console.log("[desastres/tts] loaded");

// Recuperer le texte a lire
const selector = window.DesastreOptions.tts.selector;
const element = document.querySelector(selector);

if (!element) {
  console.error("[desastres/tts] Element not found:", selector);
} else {
  const text = element.textContent.trim();
  console.log("[desastres/tts] Text to speak:", text);

  // Fonction pour lancer la synthese vocale
  function speakText() {
    if (text.length === 0) {
      console.warn("[desastres/tts] No text to speak");
      return;
    }

    const utterance = new SpeechSynthesisUtterance(text);

    // Configuration optionnelle
    // Lang: utiliser la valeur configuree ou choisir aleatoirement parmi les langues francaises
    if (window.DesastreOptions.tts.lang !== undefined) {
      utterance.lang = window.DesastreOptions.tts.lang;
    } else {
      const frenchLangs = ['fr-FR', 'fr-CA', 'fr-BE', 'fr-CH'];
      utterance.lang = frenchLangs[Math.floor(Math.random() * frenchLangs.length)];
      console.log("[desastres/tts] Random lang:", utterance.lang);
    }

    // Rate: utiliser la valeur configuree ou generer une valeur aleatoire entre 0.5 et 2
    if (window.DesastreOptions.tts.rate !== undefined) {
      utterance.rate = window.DesastreOptions.tts.rate;
    } else {
      utterance.rate = 0.5 + Math.random() * 1.5;  // Entre 0.5 et 2
      console.log("[desastres/tts] Random rate:", utterance.rate);
    }

    // Pitch: utiliser la valeur configuree ou generer une valeur aleatoire entre 0 et 2
    if (window.DesastreOptions.tts.pitch !== undefined) {
      utterance.pitch = window.DesastreOptions.tts.pitch;
    } else {
      utterance.pitch = Math.random() * 2;  // Entre 0 et 2
      console.log("[desastres/tts] Random pitch:", utterance.pitch);
    }

    // Volume: utiliser la valeur configuree ou generer une valeur aleatoire entre 0.3 et 1
    if (window.DesastreOptions.tts.volume !== undefined) {
      utterance.volume = window.DesastreOptions.tts.volume;
    } else {
      utterance.volume = 0.3 + Math.random() * 0.7;  // Entre 0.3 et 1
      console.log("[desastres/tts] Random volume:", utterance.volume);
    }

    // Selection d'une voix aleatoire si une liste est fournie
    if (window.DesastreOptions.tts.voices && Array.isArray(window.DesastreOptions.tts.voices) && window.DesastreOptions.tts.voices.length > 0) {
      const voiceNames = window.DesastreOptions.tts.voices;
      const randomVoiceName = voiceNames[Math.floor(Math.random() * voiceNames.length)];

      // Attendre que les voix soient chargees
      const setVoice = () => {
        const availableVoices = speechSynthesis.getVoices();
        const selectedVoice = availableVoices.find(voice => voice.name === randomVoiceName);

        if (selectedVoice) {
          utterance.voice = selectedVoice;
          console.log("[desastres/tts] Voice selected:", selectedVoice.name);
        } else {
          console.warn("[desastres/tts] Voice not found:", randomVoiceName, "Available voices:", availableVoices.map(v => v.name));
        }
      };

      // Les voix peuvent ne pas etre immediatement disponibles
      if (speechSynthesis.getVoices().length > 0) {
        setVoice();
      } else {
        speechSynthesis.addEventListener('voiceschanged', setVoice, { once: true });
      }
    }

    utterance.onstart = () => {
      console.log("[desastres/tts] Speech started");
    };

    utterance.onend = () => {
      console.log("[desastres/tts] Speech ended");
    };

    utterance.onerror = (event) => {
      console.error("[desastres/tts] Speech error:", event);
    };

    speechSynthesis.speak(utterance);
    console.log("[desastres/tts] Speech synthesis triggered");
  }

  // Tenter de parler automatiquement
  try {
    speakText();
    console.log("[desastres/tts] Attempting autoplay");
  } catch (error) {
    console.warn("[desastres/tts] Autoplay failed:", error);
  }

  // Fallback: attendre une interaction utilisateur
  const speakOnInteraction = () => {
    console.log("[desastres/tts] User interaction detected, starting speech");
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

  console.log("[desastres/tts] Waiting for user interaction if autoplay blocked...");
}