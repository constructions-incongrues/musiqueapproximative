console.log("[desastres/tts] Loaded");

// Verifier que les options TTS sont presentes
if (!window.DesastreOptions || !window.DesastreOptions.tts) {
  console.error("[desastres/tts] ERROR: Options not found in window.DesastreOptions.tts");
  console.log("[desastres/tts] Available options:", window.DesastreOptions);
} else {
  console.log("[desastres/tts] Options loaded:", window.DesastreOptions.tts);

  // Fonction pour construire le contexte de declenchement
  function buildContext() {
    const now = new Date();
    const context = {
      // Contexte temporel
      date: {
        day: now.getDate(),
        month: now.getMonth() + 1,
        year: now.getFullYear(),
        hour: now.getHours(),
        minute: now.getMinutes(),
        weekday: now.getDay() === 0 ? 7 : now.getDay(), // 1=lundi, 7=dimanche
        timestamp: now.getTime()
      },
      // Parametres de requete (similaires a ceux utilises cote backend)
      query: {},
      // Options de la recette TTS
      options: window.DesastreOptions.tts || {},
      // Toutes les options des desastres actifs
      allDesastreOptions: window.DesastreOptions || {},
      // Informations de la page
      page: {
        url: window.location.href,
        pathname: window.location.pathname,
        title: document.title
      }
    };

    // Extraire les informations du morceau depuis le DOM
    // Chercher le titre du morceau (h2)
    const titleElement = document.querySelector('h2');
    if (titleElement) {
      context.query.title = titleElement.textContent.trim();
    }

    // Chercher l'artiste (h1)
    const artistElement = document.querySelector('h1');
    if (artistElement) {
      context.query.artist = artistElement.textContent.trim();
    }

    // Chercher le contributor (lien avec rel="author")
    const contributorElement = document.querySelector('a[rel="author"]');
    if (contributorElement) {
      context.query.contributor = contributorElement.textContent.trim();
    }

    // Ajouter les metadonnees audio si disponibles
    if (window.DesastreAudio && window.DesastreAudio.isReady) {
      context.audio = {
        duration: window.DesastreAudio.duration,
        currentTime: window.DesastreAudio.element ? window.DesastreAudio.element.currentTime : null
      };
    }

    return context;
  }

  // Fonction pour recuperer le texte a lire
  async function getText() {
    // Priorite 1 : Texte depuis une URL
    if (window.DesastreOptions.tts.url) {
      console.log("[desastres/tts] Fetching text from URL:", window.DesastreOptions.tts.url);
      try {
        // Construire le contexte complet
        const context = buildContext();
        console.log("[desastres/tts] Sending context to URL:", context);

        const response = await fetch(window.DesastreOptions.tts.url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(context)
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();

        if (Array.isArray(data) && data.length > 0) {
          const randomText = data[Math.floor(Math.random() * data.length)];
          console.log("[desastres/tts] Text selected from URL (", data.length, "options):", randomText.substring(0, 100) + (randomText.length > 100 ? "..." : ""));
          return randomText;
        } else {
          console.error("[desastres/tts] ERROR: URL did not return a valid array of texts");
        }
      } catch (error) {
        console.error("[desastres/tts] ERROR: Failed to fetch text from URL:", error);
      }
    }

    // Priorite 2 : Texte depuis une liste configuree
    if (window.DesastreOptions.tts.texts && Array.isArray(window.DesastreOptions.tts.texts) && window.DesastreOptions.tts.texts.length > 0) {
      const texts = window.DesastreOptions.tts.texts;
      const randomText = texts[Math.floor(Math.random() * texts.length)];
      console.log("[desastres/tts] Text selected from configured list (", texts.length, "options):", randomText.substring(0, 100) + (randomText.length > 100 ? "..." : ""));
      return randomText;
    }

    // Priorite 3 : Texte depuis un selecteur CSS
    const selector = window.DesastreOptions.tts.selector || 'div.descriptif p';
    console.log("[desastres/tts] Using CSS selector:", selector);

    const element = document.querySelector(selector);

    if (!element) {
      console.error("[desastres/tts] ERROR: Element not found with selector:", selector);
      return null;
    }

    const text = element.textContent.trim();
    console.log("[desastres/tts] Text found from selector (length:", text.length + " chars):", text.substring(0, 100) + (text.length > 100 ? "..." : ""));
    return text;
  }

  // Fonction pour lancer la synthese vocale
  async function speakText() {
    const text = await getText();

    if (!text || text.length === 0) {
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

    // Selection d'une voix aleatoire
    // Si options.voices est defini, on choisit parmi cette liste
    // Sinon, on choisit parmi toutes les voix disponibles
    const setVoice = () => {
      const availableVoices = speechSynthesis.getVoices();
      console.log("[desastres/tts] Available voices count:", availableVoices.length);

      let selectedVoice = null;

      if (window.DesastreOptions.tts.voices && Array.isArray(window.DesastreOptions.tts.voices) && window.DesastreOptions.tts.voices.length > 0) {
        // Cas 1: options.voices est defini -> choisir parmi cette liste
        const voiceNames = window.DesastreOptions.tts.voices;
        const randomVoiceName = voiceNames[Math.floor(Math.random() * voiceNames.length)];
        console.log("[desastres/tts] Selecting random voice from configured list (", voiceNames.length, "options):", randomVoiceName);

        selectedVoice = availableVoices.find(voice => voice.name === randomVoiceName);

        if (selectedVoice) {
          console.log("[desastres/tts] Voice set:", selectedVoice.name, "(lang:", selectedVoice.lang + ")");
        } else {
          console.warn("[desastres/tts] WARNING: Configured voice not found:", randomVoiceName);
          console.log("[desastres/tts] Available voices:", availableVoices.map(v => v.name).join(", "));
        }
      } else {
        // Cas 2: options.voices n'est pas defini -> choisir parmi toutes les voix disponibles
        console.log("[desastres/tts] No voice list configured, selecting random voice from all available voices");

        if (availableVoices.length > 0) {
          selectedVoice = availableVoices[Math.floor(Math.random() * availableVoices.length)];
          console.log("[desastres/tts] Random voice selected:", selectedVoice.name, "(lang:", selectedVoice.lang + ")");
        } else {
          console.warn("[desastres/tts] WARNING: No voices available, using default voice");
        }
      }

      if (selectedVoice) {
        utterance.voice = selectedVoice;
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