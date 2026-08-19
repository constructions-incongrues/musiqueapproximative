/**
 * La bande usée — branchement et contrepoints.
 *
 * Le site s'appelle Musique Approximative. Ce désastre prend ce nom au mot : la hauteur du
 * morceau se met à flotter, comme une bande passée trop de fois.
 *
 * TROIS COUCHES, UNE SEULE MODULATION
 *
 * L'oreille l'entend, l'œil la voit dériver sur le titre, la main la sent dans la réponse
 * de la page. Ce n'est pas de l'ornement : un son qui flotte SEUL se lit comme une panne —
 * connexion, casque, fichier — et un visiteur qui croit le site cassé s'en va. Le désastre
 * se retournerait alors contre le morceau qu'il devait accompagner.
 *
 * D'où la règle que la spécification rend obligatoire : les contrepoints PARTAGENT le
 * signal du processeur, ils ne l'imitent pas. Deux animations réglées sur les mêmes
 * fréquences seraient perçues comme deux événements simultanés ; ce qui fait la
 * démonstration, c'est que l'œil et la main confirment ce que l'oreille entend, au même
 * instant.
 *
 * CE QU'ON NE PEUT PAS FAIRE, ET QUI A ÉTÉ VÉRIFIÉ
 *
 * On ne ralentit pas le curseur : aucune API ne le permet. Le seul moyen serait
 * `requestPointerLock`, qui exige un geste, masque le curseur, affiche une bannière et
 * capture la souris dans la page — ça ne surprend pas, ça séquestre. Et masquer le curseur
 * pour en dessiner un qui traîne ferait atterrir les clics ailleurs que là où on les voit,
 * ce qui est un bug et non un désastre.
 *
 * Alors c'est l'inverse : le curseur n'est pas ralenti, C'EST LA PAGE QUI MET DU TEMPS À LE
 * REMARQUER. Une bande usée ne ralentit pas la main, elle répond mollement.
 *
 * @see openspec/changes/archive/*-desastre-la-bande-usee/
 */

(function () {
  "use strict";

  var NOM = "bande-usee";
  var options = (window.DesastreOptions && window.DesastreOptions[NOM]) || {};

  /**
   * Sortie de secours.
   *
   * `prefers-reduced-motion` couvre les deux contrepoints, mais IL N'EXISTE AUCUN RÉGLAGE
   * STANDARD POUR REFUSER UNE ALTÉRATION SONORE. Sans cette échappatoire, ce serait le seul
   * désastre du catalogue auquel on ne peut pas échapper.
   *
   * Documentée dans docs/modules/ROOT/pages/desastres.adoc.
   */
  if (/[?&]sans-desastre(=|&|$)/.test(window.location.search)) {
    console.log(
      "[desastres/" +
        NOM +
        "] sortie demandee par l'adresse, rien ne sera applique",
    );
    return;
  }

  var mouvementRefuse =
    window.matchMedia &&
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  if (mouvementRefuse) {
    console.log(
      "[desastres/" +
        NOM +
        "] prefers-reduced-motion : les contrepoints visuel et tactile " +
        "sont retires, l'alteration sonore reste",
    );
  }

  // --------------------------------------------------------------------- le son

  var contexte = null;
  var noeud = null;
  var branche = false;
  var messagesRecus = 0;

  function brancher(audio) {
    if (branche) {
      return;
    }

    var Contexte = window.AudioContext || window.webkitAudioContext;

    if (!Contexte || typeof window.AudioWorkletNode !== "function") {
      console.warn(
        "[desastres/" +
          NOM +
          "] AudioWorklet indisponible, le morceau reste intact",
      );

      return;
    }

    branche = true;
    contexte = new Contexte();

    // `audioWorklet` est un ACCESSEUR sur BaseAudioContext : le lire sur le prototype leve
    // « Illegal invocation », il exige une instance. C'est exactement l'erreur qui avait
    // d'abord fait croire, en sondant le navigateur, que AudioWorklet etait absent — puis
    // qui a fait echouer ce branchement en silence, l'exception etant avalee par le
    // try/catch de DesastreAudio.onReady. On teste donc AudioWorkletNode, qui est un
    // constructeur global, et on lit `audioWorklet` sur le contexte construit.
    if (!contexte.audioWorklet) {
      console.warn(
        "[desastres/" +
          NOM +
          "] pas d'audioWorklet sur le contexte, le morceau reste intact",
      );

      return;
    }

    contexte.audioWorklet
      .addModule("/desastres/" + NOM + "/worklet/" + NOM + "-processeur.js")
      .then(function () {
        var source = contexte.createMediaElementSource(audio);

        noeud = new AudioWorkletNode(contexte, NOM, {
          processorOptions: {
            wowHz: options.wowHz,
            flutterHz: options.flutterHz,
            profondeurMs: options.profondeurMs,
            retardBaseMs: options.retardBaseMs,
          },
        });

        if (typeof options.intensite === "number") {
          noeud.parameters.get("intensite").value = options.intensite;
        }

        noeud.port.onmessage = function (e) {
          if (e.data && typeof e.data.modulateur === "number") {
            messagesRecus++;
            appliquerContrepoints(e.data.modulateur);
          }
        };

        source.connect(noeud);
        noeud.connect(contexte.destination);

        console.log("[desastres/" + NOM + "] la bande est usee");

        // Un desastre qui ne se voit pas est un desastre qu'on ne peut pas diagnostiquer a
        // l'oeil. On expose de quoi le faire depuis la console.
        window.DesastreBandeUsee = {
          contexte: contexte,
          noeud: noeud,
          etat: function () {
            return {
              contexte: contexte.state,
              messagesRecus: messagesRecus,
              titreTrouve: !!titre,
              mouvementRefuse: mouvementRefuse,
            };
          },
        };
      })
      .catch(function (e) {
        // Un desastre est un ornement : s'il echoue, le morceau doit rester audible. Le
        // `createMediaElementSource` n'ayant pas eu lieu, la sortie normale de l'element
        // audio n'a pas ete detournee et le son continue de lui-meme.
        console.warn(
          "[desastres/" +
            NOM +
            "] echec du chargement, le morceau reste intact :",
          e,
        );
      });
  }

  /**
   * `AudioContext` naît suspendu tant que le visiteur n'a rien cliqué. Ce n'est pas un
   * obstacle mais un point de départ : le clic de lecture est exactement le moment où le
   * désastre doit commencer.
   */
  function reveiller() {
    if (contexte && contexte.state === "suspended") {
      contexte.resume();
    }
  }

  if (
    window.DesastreAudio &&
    typeof window.DesastreAudio.onReady === "function"
  ) {
    window.DesastreAudio.onReady(function (audio) {
      var element = audio && audio.element ? audio.element : null;

      if (!element) {
        console.warn(
          "[desastres/" + NOM + "] aucun element audio, rien a user",
        );

        return;
      }

      brancher(element);
      element.addEventListener("play", reveiller);
    });
  } else {
    console.warn(
      "[desastres/" +
        NOM +
        "] DesastreAudio absent, le desastre ne s'applique pas",
    );
  }

  ["click", "keydown", "touchstart"].forEach(function (evenement) {
    document.addEventListener(evenement, reveiller, {
      once: false,
      passive: true,
    });
  });

  // ------------------------------------------------------- les deux contrepoints

  var selecteurTitre = options.selecteurTitre || "article h2";
  var titre = document.querySelector(selecteurTitre);
  var derniereValeur = 0;
  var enAttente = false;

  /**
   * Le titre du morceau, et non `h1` ni `.title`.
   *
   * Relevé le 2026-08-19 : `article h1` porte l'ARTISTE, `article h2` le TITRE. Un `h1` nu
   * attraperait la barre latérale — la page en compte trois — et `.title` est le nom du
   * site, ce que vise `mangelettres`.
   */
  function appliquerContrepoints(modulateur) {
    if (mouvementRefuse) {
      return;
    }

    derniereValeur = modulateur;

    if (enAttente) {
      return;
    }

    enAttente = true;

    // Le port emet plus souvent que l'ecran ne se rafraichit : on ne peint qu'une fois par
    // trame, sur la derniere valeur recue.
    //
    // Consequence voulue : dans un onglet masque, `requestAnimationFrame` est suspendu.
    // Les deux contrepoints se figent pendant que le son continue de flotter — ce qui est
    // juste, personne ne regarde. Le rappel en attente s'execute a la premiere trame du
    // retour, et `enAttente` se libere alors de lui-meme.
    window.requestAnimationFrame(function () {
      enAttente = false;

      var m = derniereValeur;

      // Le script peut avoir ete evalue avant que `article h2` soit analyse : on retente
      // tant qu'on ne l'a pas, plutot que de rester muet pour toujours.
      if (!titre) {
        titre = document.querySelector(selecteurTitre);
      }

      if (titre) {
        // Minuscule, et c'est voulu : on cherche la confirmation de ce qu'on entend, pas un
        // effet visuel qui prendrait le dessus.
        titre.style.transform =
          "translateY(" +
          (m * 1.6).toFixed(3) +
          "px) rotate(" +
          (m * 0.14).toFixed(3) +
          "deg)";
      }

      // La page repond au pointeur avec le meme retard flottant. Le curseur n'est pas
      // touche : les clics restent exacts, seule la REPONSE tarde.
      var retardMs = 90 + Math.abs(m) * 110;
      document.documentElement.style.setProperty(
        "--bande-usee-retard",
        retardMs.toFixed(0) + "ms",
      );
    });
  }
})();
