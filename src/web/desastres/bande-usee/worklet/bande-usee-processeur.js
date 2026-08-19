/**
 * La bande usée — processeur AudioWorklet.
 *
 * Fait flotter la hauteur du morceau comme une bande magnétique passée trop de fois.
 *
 * POURQUOI UNE LIGNE À RETARD ET PAS `playbackRate`
 *
 * `playbackRate` sur l'élément audio serait plus simple et donnerait un résultat faux : il
 * modifie la vitesse globale, ce qui s'entend comme un ralenti assumé. Ce qui fait la bande
 * fatiguée est une instabilité CONTINUE ET PETITE autour de la vitesse nominale.
 *
 * Une ligne à retard dont la longueur oscille produit exactement cela : lire le signal avec
 * un retard qui varie, c'est le lire à une vitesse qui varie, donc à une hauteur qui varie.
 *
 * POURQUOI UN WORKLET ET PAS `ScriptProcessorNode`
 *
 * Le traitement doit être continu et échantillon par échantillon. `ScriptProcessorNode`
 * s'exécute sur le fil principal : la moindre contention y produit des craquements, et un
 * craquement s'entend comme une PANNE DU SITE, c'est-à-dire l'inverse du but recherché. Ce
 * processeur tourne sur le fil de rendu audio, par quantums de 128 échantillons.
 *
 * DEUX PRÉCAUTIONS QUI NE SE VOIENT PAS
 *
 * 1. L'INTERPOLATION EST OBLIGATOIRE. Le retard modulé tombe entre deux échantillons ; lire
 *    l'échantillon le plus proche ferait sauter la lecture d'un cran à l'autre, et ces sauts
 *    s'entendent comme un grésillement. On interpole linéairement entre les deux voisins.
 *
 * 2. LE MODULATEUR SORT PAR LE PORT, PAS PAR UN ANALYSEUR. Les contrepoints visuel et
 *    tactile doivent suivre LA MÊME modulation, pas lui ressembler. Un `AnalyserNode`
 *    donnerait l'amplitude de la musique, qui n'a aucun rapport avec le flottement.
 *
 * @see openspec/changes/archive/*-desastre-la-bande-usee/
 */

const TAILLE_TAMPON = 4096; // ~93 ms a 44,1 kHz : trois fois le retard maximal, de quoi
// moduler sans jamais rattraper l'ecriture.

class BandeUseeProcesseur extends AudioWorkletProcessor {
  /**
   * `intensite` est un AudioParam et non une option de construction : les stories 35 et 36
   * la feront varier — selon l'age du morceau, puis selon le nombre d'ecoutes — et un
   * AudioParam s'automatise a la frequence audio sans discontinuite audible.
   */
  static get parameterDescriptors() {
    return [
      {
        name: "intensite",
        defaultValue: 1,
        minValue: 0,
        maxValue: 1,
        automationRate: "k-rate",
      },
    ];
  }

  constructor(options) {
    super();

    const reglages = (options && options.processorOptions) || {};

    // Wow : l'ondulation lente d'un plateau legerement voile.
    this.wowHz = reglages.wowHz || 0.6;
    // Flutter : le tremblement rapide d'un cabestan fatigue.
    this.flutterHz = reglages.flutterHz || 7.5;
    // Profondeur du retard, en millisecondes.
    //
    // CE REGLAGE SE MESURE, IL NE SE DEVINE PAS. La deviation de hauteur ne depend pas de
    // la profondeur seule mais de sa DERIVEE : pour une composante d'amplitude A a la
    // frequence f, l'ecart maximal vaut 2*PI*f*A. Le flutter, rapide, pese donc beaucoup
    // plus que le wow a profondeur egale.
    //
    // Un premier jeu de valeurs — 2,2 ms et un flutter a 0,35 — a ete rendu hors ligne et
    // mesure par passages par zero sur un la 440 : **154 cents d'amplitude**, plus d'un
    // demi-ton. Ce n'est pas une bande fatiguee, c'est une machine cassee.
    //
    // Une bande reelle tient entre dix et cinquante cents. Les valeurs ci-dessous visent
    // une trentaine — nettement perceptible comme une instabilite, jamais comme une panne.
    this.profondeurMs = reglages.profondeurMs || 0.9;
    // Retard median. Il faut de la marge des deux cotes pour que la modulation reste
    // symetrique sans jamais demander un retard negatif.
    this.retardBaseMs = reglages.retardBaseMs || 12;

    this.tampons = [];
    this.ecriture = 0;
    this.phaseWow = 0;
    this.phaseFlutter = 0;

    // Le port est bavard par nature : une valeur par quantum ferait ~344 messages par
    // seconde pour un affichage qui se rafraichit 60 fois. On n'en emet qu'un sur six.
    this.quantumsDepuisEnvoi = 0;
    this.quantumsParEnvoi = reglages.quantumsParEnvoi || 6;

    this.vivant = true;
    this.port.onmessage = (e) => {
      if (e.data && e.data.type === "arret") {
        this.vivant = false;
      }
    };
  }

  /**
   * Lecture interpolee dans la ligne a retard.
   *
   * `position` est fractionnaire : c'est tout l'interet, et c'est pourquoi on ne peut pas
   * se contenter d'arrondir.
   */
  lireInterpole(tampon, position) {
    const taille = tampon.length;
    let p = position;

    while (p < 0) {
      p += taille;
    }
    while (p >= taille) {
      p -= taille;
    }

    const entier = Math.floor(p);
    const fraction = p - entier;
    const suivant = entier + 1 >= taille ? 0 : entier + 1;

    return tampon[entier] * (1 - fraction) + tampon[suivant] * fraction;
  }

  process(entrees, sorties, parametres) {
    const entree = entrees[0];
    const sortie = sorties[0];

    // Pas d'entree : le morceau n'a pas encore demarre, ou il est fini. On reste en vie —
    // rendre `false` detruirait le noeud et il faudrait tout rebrancher a la lecture
    // suivante.
    if (!entree || entree.length === 0) {
      return this.vivant;
    }

    // Allouer un tampon par canal, une seule fois. `sampleRate` est fourni par le
    // AudioWorkletGlobalScope.
    if (this.tampons.length !== entree.length) {
      this.tampons = entree.map(() => new Float32Array(TAILLE_TAMPON));
      this.ecriture = 0;
    }

    const intensite = parametres.intensite[0];
    const nbEchantillons = entree[0].length;

    const baseEch = (this.retardBaseMs / 1000) * sampleRate;
    const profondeurEch = (this.profondeurMs / 1000) * sampleRate * intensite;

    const incrementWow = (2 * Math.PI * this.wowHz) / sampleRate;
    const incrementFlutter = (2 * Math.PI * this.flutterHz) / sampleRate;

    let modulateur = 0;

    for (let i = 0; i < nbEchantillons; i++) {
      // Le flutter pese 0,12 et non 0,35 : a 7,5 Hz contre 0,6, il deviait a lui seul plus
      // que le wow ne le faisait. Ponderé ainsi, les deux contribuent a peu pres autant, et
      // c'est le lent qui domine a l'oreille — comme sur une bande.
      modulateur = Math.sin(this.phaseWow) + 0.12 * Math.sin(this.phaseFlutter);

      const retard = baseEch + profondeurEch * modulateur;
      const lecture = this.ecriture - retard;

      for (let c = 0; c < entree.length; c++) {
        const tampon = this.tampons[c];
        tampon[this.ecriture] = entree[c][i];

        if (sortie[c]) {
          sortie[c][i] = this.lireInterpole(tampon, lecture);
        }
      }

      this.ecriture = (this.ecriture + 1) % TAILLE_TAMPON;
      this.phaseWow += incrementWow;
      this.phaseFlutter += incrementFlutter;
    }

    // Garder les phases bornees : sur une ecoute longue, elles atteindraient des valeurs ou
    // la precision d'un flottant se degrade et ou le flottement deviendrait irregulier.
    this.phaseWow %= 2 * Math.PI;
    this.phaseFlutter %= 2 * Math.PI;

    this.quantumsDepuisEnvoi++;
    if (this.quantumsDepuisEnvoi >= this.quantumsParEnvoi) {
      this.quantumsDepuisEnvoi = 0;
      // Normalise dans [-1, 1] : les contrepoints n'ont pas a connaitre nos amplitudes.
      this.port.postMessage({
        modulateur: modulateur / 1.12,
        intensite: intensite,
      });
    }

    return this.vivant;
  }
}

registerProcessor("bande-usee", BandeUseeProcesseur);
