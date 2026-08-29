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
   * CE RETOUR DOIT RESTER AVANT TOUTE LECTURE OU ÉCRITURE DE LA MÉMOIRE D'ÉCOUTE.
   *
   * Ce n'est pas une commodité d'écriture, c'est une exigence : un visiteur qui refuse le
   * désastre n'a pas à voir son écoute enregistrée pour autant, et retrouverait sinon, en le
   * réactivant, une usure accumulée pendant qu'il l'avait refusé. Déplacer ce bloc plus bas
   * romprait la spécification sans qu'aucun test ne le dise — la suite est en PHP et ne
   * traverse pas `localStorage`.
   *
   * Documentée dans docs/modules/ROOT/pages/desastres.adoc, dans le README de cette recette,
   * et — pour que le visiteur puisse la trouver — dans le pied de page du site.
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

  // ------------------------------------------------------------ la memoire de l'ecoute

  /**
   * L'usure que VOUS avez produite.
   *
   * Le desastre comptait l'age du morceau ; il compte desormais aussi ce que ce navigateur
   * lui a fait subir. C'est la premiere memoire du catalogue : dix-huit autres recettes
   * existent, aucune ne se souvient de quoi que ce soit.
   *
   * CE QU'ELLE N'EST PAS
   *
   * Rien ne quitte le navigateur : aucune requete, aucun identifiant, aucune correlation
   * entre appareils. Le site ne sait pas que vous avez use la bande, et ne peut pas le
   * savoir — la capacite `desastres` exige qu'une page mise en cache serve un corps
   * identique a chaque requete, ce que `desastreInvarianceTest` verifie. Une usure calculee
   * au service romprait ce test.
   *
   * L'ECOUTE VIEILLIT LE MORCEAU
   *
   * L'usure ne s'ajoute pas a l'intensite : elle s'ajoute a l'AGE, en jours virtuels, avant
   * la courbe. `intensite` est un AudioParam borne a [0, 1] ou l'age sature deja pour un
   * morceau de dix-huit ans — il n'y avait aucune marge au-dessus. Verser l'usure dans
   * l'age donne le plafond gratuitement, par le `min(1, ...)` deja present, sans toucher au
   * plancher ni a la courbe, tous deux mesures sur le catalogue reel.
   */
  var CLE_USURE = "desastres:" + NOM + ":usure";

  // En dessous d'un jour virtuel, une entree ne change plus rien au rendu : elle est purgee
  // a la prochaine ecriture. Le stockage se borne ainsi au repertoire reellement ecoute.
  var SEUIL_OUBLI_JOURS = 1;

  var ageVirtuelParEcouteJours =
    (typeof options.ageVirtuelParEcouteAns === "number"
      ? options.ageVirtuelParEcouteAns
      : 1) * 365.25;

  var demiVieOubliJours =
    typeof options.demiVieOubliJours === "number"
      ? options.demiVieOubliJours
      : 30;

  /**
   * L'adresse d'un morceau est `/post/:slug`. Pas de balise a poser dans le gabarit,
   * contrairement a la date de publication qu'avait exigee l'usure d'age.
   */
  function slugDuMorceau() {
    var trouve = /\/post\/([^/?#]+)/.exec(window.location.pathname);

    return trouve ? decodeURIComponent(trouve[1]) : null;
  }

  /**
   * Un desastre est un ornement : un stockage indisponible — navigation privee stricte,
   * quota atteint, stockage desactive — ne doit jamais empecher d'ecouter. Tout acces est
   * enveloppe, et l'echec retombe sur l'usure d'age seule.
   */
  function lireMemoire() {
    try {
      return JSON.parse(window.localStorage.getItem(CLE_USURE)) || {};
    } catch (e) {
      return {};
    }
  }

  function ecrireMemoire(memoire) {
    try {
      window.localStorage.setItem(CLE_USURE, JSON.stringify(memoire));
    } catch (e) {
      // Rien a faire : l'usure de cette session sera simplement oubliee.
    }
  }

  /**
   * L'oubli se calcule A LA LECTURE, depuis l'horodatage stocke. Un navigateur ferme six
   * mois n'a rien a rattraper, et lire n'ecrit jamais.
   *
   * La decroissance est continue plutot que par paliers : un oubli par paliers ferait
   * chuter l'alteration d'un coup entre deux visites, ce qui s'entendrait comme un
   * changement de reglage. L'oubli n'est pas un evenement.
   */
  function usureOubliee(entree) {
    if (
      !entree ||
      typeof entree.j !== "number" ||
      typeof entree.t !== "number"
    ) {
      return 0;
    }

    var jours = (Date.now() - entree.t) / 86400000;

    if (!(jours > 0)) {
      jours = 0;
    }

    var reste = entree.j * Math.pow(2, -jours / demiVieOubliJours);

    return reste > 0 ? reste : 0;
  }

  /** Usure courante du morceau de cette page, en jours virtuels. */
  function usureDuMorceau() {
    var slug = slugDuMorceau();

    return slug ? usureOubliee(lireMemoire()[slug]) : 0;
  }

  /** Nombre d'ecoutes enregistrees, pour le seul journal de console. */
  function ecoutesDuMorceau() {
    var slug = slugDuMorceau();

    if (!slug) {
      return 0;
    }

    var entree = lireMemoire()[slug];

    return entree && typeof entree.n === "number" ? entree.n : 0;
  }

  /**
   * Une ecoute est comptee au DEMARRAGE DE LA LECTURE, et une seule fois par chargement de
   * page. Compter a l'affichage userait la bande de quelqu'un qui n'a rien entendu ; compter
   * chaque reprise apres pause compterait une meme ecoute plusieurs fois.
   */
  var ecouteComptee = false;

  function compterUneEcoute() {
    if (ecouteComptee) {
      return;
    }

    var slug = slugDuMorceau();

    if (!slug) {
      return;
    }

    ecouteComptee = true;

    var memoire = lireMemoire();
    var maintenant = Date.now();

    // Purge des entrees devenues negligeables : elles ne changent plus rien au rendu.
    Object.keys(memoire).forEach(function (autre) {
      if (autre !== slug && usureOubliee(memoire[autre]) < SEUIL_OUBLI_JOURS) {
        delete memoire[autre];
      }
    });

    var entree = memoire[slug];

    memoire[slug] = {
      j: usureOubliee(entree) + ageVirtuelParEcouteJours,
      t: maintenant,
      n: (entree && typeof entree.n === "number" ? entree.n : 0) + 1,
    };

    ecrireMemoire(memoire);
  }

  // -------------------------------------------------- l'age du morceau, et le votre

  /**
   * Intensite de l'usure : l'age du morceau, augmente de ce que ce navigateur lui a fait.
   *
   * Les deux se composent AVANT la courbe, dans la meme unite — des jours. L'age reel dit
   * ce que le temps a fait au morceau ; l'usure dit ce que vous en avez fait. Le
   * `min(1, ...)` qui suit borne les deux d'un coup : c'est le plafond exige par la
   * specification, et il ne peut pas etre contourne par erreur puisqu'il etait deja la.
   *
   * Consequence a connaitre : un morceau de dix-huit ans ou plus est deja a `part = 1`.
   * L'ecouter cent fois n'y change rien — environ 2 % du catalogue. C'est le plafond
   * applique tot plutot que tard, et il se defend : une bande usee jusqu'a la corde ne
   * s'use plus. Le README de la recette le dit, pour que ce ne soit pas pris pour un defaut.
   *
   * POURQUOI UNE COURBE ET PAS UNE PROPORTION
   *
   * Le catalogue va de juin 2008 a aujourd'hui : 6 643 jours, 8 216 morceaux. Une courbe
   * lineaire sur cette etendue placerait **43 % du catalogue dans la bande la plus
   * alteree** — mesure sur les morceaux reels — ce qui ferait de l'usure l'etat normal
   * plutot que la marque d'un age. L'exposant 2 la reserve aux morceaux vraiment anciens :
   *
   *   2026 → 22,8 cents    2016 → 35,9     2011 → 52,2     2008 → 65,1
   *
   * POURQUOI UN PLANCHER
   *
   * Sans lui, 29 % des morceaux recevraient une alteration inaudible alors que l'en-tete
   * `X-Desastre` annonce le desastre. Un desastre declare et imperceptible se lit comme un
   * desastre casse.
   *
   * Encore faut-il que le plancher soit lui-meme audible. A 0,15 il ne l'etait pas — 4,4
   * cents pour un morceau du jour — et le defaut qu'il pretendait corriger existait quand
   * meme. La courbe etant quadratique, c'est ce plancher, et non la profondeur, qui decide
   * de ce qu'entend la moitie recente du catalogue.
   *
   * POURQUOI UNE CONSTANTE ET PAS L'ETENDUE DU CATALOGUE
   *
   * L'etendue grandit chaque jour. L'usure d'un morceau donne changerait alors sans que
   * personne ne l'ait decidee. Dix-huit ans est un repere qui se lit, se discute et se
   * regle ; `MAX(publish_on)` n'en est pas un.
   */
  function intensiteDeLUsure() {
    var repli = typeof options.intensite === "number" ? options.intensite : 1;
    var plancher =
      typeof options.plancherUsure === "number" ? options.plancherUsure : 0.35;
    var referenceAns =
      typeof options.referenceAns === "number" ? options.referenceAns : 18;

    var balise = document.querySelector(
      'meta[name="musiqueapproximative:publie-le"]',
    );

    if (!balise || !balise.getAttribute("content")) {
      // Un desastre est un ornement : une date absente ne doit pas le faire echouer.
      return repli;
    }

    var publie = Date.parse(balise.getAttribute("content"));

    if (isNaN(publie)) {
      return repli;
    }

    var jours = (Date.now() - publie) / 86400000;

    if (jours < 0) {
      jours = 0;
    }

    var usure = usureDuMorceau();
    var part = Math.min(1, (jours + usure) / (referenceAns * 365.25));
    var intensite = plancher + (1 - plancher) * part * part;

    // Le cumul ne se mesure pas a l'oreille d'une visite a l'autre : sans ce journal, rien
    // ne permet de verifier qu'il a lieu.
    console.log(
      "[desastres/" +
        NOM +
        "] morceau de " +
        Math.round(jours / 365.25) +
        " an(s), " +
        ecoutesDuMorceau() +
        " ecoute(s) ici = " +
        Math.round(usure) +
        " jour(s) d'usure, intensite " +
        intensite.toFixed(2),
    );

    return intensite;
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

        noeud.parameters.get("intensite").value = intensiteDeLUsure();

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
              intensite: noeud.parameters.get("intensite").value,
              contexte: contexte.state,
              messagesRecus: messagesRecus,
              titreTrouve: !!titre,
              mouvementRefuse: mouvementRefuse,
              usureJours: Math.round(usureDuMorceau()),
              ecoutes: ecoutesDuMorceau(),
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

  /**
   * Se brancher des que l'element audio existe.
   *
   * ON N'UTILISE PAS `DesastreAudio`, ET C'EST UNE CORRECTION.
   *
   * La premiere version passait par son `onReady`, qui n'appelle qu'apres le chargement des
   * METADONNEES du morceau. Or `createMediaElementSource()` n'a besoin que de L'ELEMENT :
   * attendre le media etait une exigence inutile, et elle rendait le desastre dependant du
   * bon vouloir du reseau — fichier lent, absent ou en erreur, il ne s'appliquait jamais.
   *
   * Elle a aussi cache une dependance non declaree : `desastre-audio.js` n'etait pas dans
   * les `scripts:` de la recette. En developpement, `mangelettres` se declenchait sur la
   * meme page et le chargeait, si bien que ce desastre en profitait sans l'avoir demande.
   * Force seul en production, il rendait « DesastreAudio absent » et ne faisait rien.
   *
   * Chercher l'element soi-meme supprime les deux problemes d'un coup. jPlayer le cree a
   * l'execution, d'ou l'attente courte.
   */
  function surElementAudio(action) {
    var essais = 0;

    var chercher = function () {
      var element =
        document.querySelector("#jp_audio_0") ||
        document.querySelector("audio");

      if (element) {
        action(element);

        return;
      }

      if (essais++ < 40) {
        window.setTimeout(chercher, 250);
      } else {
        console.warn(
          "[desastres/" + NOM + "] aucun element audio trouve, rien a user",
        );
      }
    };

    chercher();
  }

  surElementAudio(function (element) {
    brancher(element);
    element.addEventListener("play", reveiller);

    // L'intensite a ete posee par `brancher()` AVANT ce premier `play` : l'ecoute en cours
    // porte l'usure des precedentes, pas la sienne. C'est la lecture voulue — vous avez use
    // la bande, vous l'entendez la fois d'apres.
    element.addEventListener("play", compterUneEcoute);
  });

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
