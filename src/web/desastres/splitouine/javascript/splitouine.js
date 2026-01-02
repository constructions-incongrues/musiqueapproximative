/**
 * Désastre Splitouine
 *
 * Anime le texte de la page avec GSAP SplitText en synchronisation avec l'audio.
 * Le texte est découpé en caractères, mots ou lignes puis animé avec des effets
 * personnalisables (rotation, opacité, déplacement, etc.).
 *
 * Dépendances :
 * - GSAP 3.x (https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js)
 * - SplitText plugin (https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/SplitText.min.js)
 * - DesastreAudio helper (/desastres/shared/desastre-audio.js)
 *
 * Configuration via window.DesastreOptions.splitouine :
 * {
 *   split: {
 *     selector: string,           // Sélecteur CSS des éléments à animer
 *     configuration: object        // Options SplitText (type, mask, wordDelimiter, etc.)
 *   },
 *   tween: {
 *     type: string,                // Type d'éléments à animer : 'chars', 'words', 'lines'
 *     configuration: object        // Options GSAP (opacity, rotation, ease, stagger, etc.)
 *   }
 * }
 */

document.addEventListener("DOMContentLoaded", (event) => {
  // Attendre que l'élément audio soit prêt pour synchroniser l'animation
  window.DesastreAudio.onReady(function (audio) {
    console.log("[desastres/splitouine] Loaded");

    // Enregistrer le plugin SplitText de GSAP
    gsap.registerPlugin(SplitText);

    // Découper le texte selon la configuration
    // Crée des éléments <div> pour chaque caractère/mot/ligne
    let split = SplitText.create(
      window.DesastreOptions.splitouine.split.selector,
      window.DesastreOptions.splitouine.split.configuration,
    );
    console.log("[desastres/splitouine] Text split complete:", {
      chars: split.chars?.length || 0,
      words: split.words?.length || 0,
      lines: split.lines?.length || 0,
    });

    // Si aucune durée n'est spécifiée, synchroniser avec la durée de l'audio
    if (!window.DesastreOptions.splitouine.tween.configuration.duration) {
      window.DesastreOptions.splitouine.tween.configuration.duration =
        audio.duration;
      console.log(
        "[desastres/splitouine] Auto-matched duration to audio:",
        audio.duration + "s",
      );
    }

    // Créer l'animation GSAP depuis l'état initial vers l'état final
    // Le type (chars/words/lines) détermine quels éléments sont animés
    let tween = gsap.from(
      split[window.DesastreOptions.splitouine.tween.type],
      window.DesastreOptions.splitouine.tween.configuration,
    );
    console.log(
      "[desastres/splitouine] Animation started on:",
      window.DesastreOptions.splitouine.tween.type,
    );
  });
});
