<?php

/**
 * Aucune recette de desastre ne fait charger de ressource depuis un hote tiers.
 *
 * Pourquoi ce fichier existe, et pourquoi il regarde AILLEURS que dans les recettes :
 *
 * Le 2026-08-18, Redoc a ete verse au depot plutot qu'appele depuis un CDN, au motif
 * que le visiteur n'a pas a etre annonce a un tiers. Les desastres, eux, appelaient
 * deux CDN a l'execution : la contradiction a ete tranchee le 2026-08-19 en versant
 * gsap, SplitText et anime.js.
 *
 * LA LECON DE CETTE BASCULE EST DANS LA PORTEE DU CONTROLE. Basculer les dix URL du
 * YAML ne suffisait pas : quatorze mentions subsistaient dans les ressources et les
 * README, le schema JSON proposait encore des URL de CDN EN EXEMPLE — un gabarit qui
 * enseigne a reintroduire ce qu'on retire — et deux hotes tiers vivants n'etaient dans
 * aucun inventaire, decouverts seulement en cherchant hors du YAML.
 *
 * Ce controle porte donc sur TOUT ce qui est servi : les recettes, les ressources des
 * desastres, et le schema qui sert de gabarit.
 *
 * @see openspec/specs/dependances-tierces/spec.md
 */

require_once dirname(__FILE__).'/../../bootstrap/unit.php';

$t = new lime_test(6);

$racine = realpath(dirname(__FILE__).'/../../..');

/**
 * Hotes tiers interdits : ceux qui servent des bibliotheques, et qui n'ont aucune
 * raison de voir passer un visiteur de ce site.
 */
$interdits = array('cdn.jsdelivr.net', 'cdnjs.cloudflare.com', 'unpkg.com', 'fonts.googleapis.com', 'fonts.gstatic.com', 'ajax.googleapis.com');

/**
 * Hotes autorises, et pourquoi. Tout ce qui n'est pas ici et n'est pas un domaine du
 * projet doit etre justifie avant d'etre ajoute.
 */
$autorises = array(
  'www.w3.org',                    // espaces de noms XML/SVG, jamais telecharges
  'musiqueapproximative.net',      // le site lui-meme
  'musiques-incongrues.net',       // services du collectif
  'partouze-cagoule.fr',           // domaine du collectif, confirme le 2026-08-19 : sert
                                   // le jingle du desastre mamie. Pas un tiers.
  'gsap.com',                      // mentions de licence, pas des chargements
  'animejs.com',                   // idem
  'docs.n8n.io',                   // documentation citee dans un README
  'votre-n8n.com',                 // exemple de gabarit dans un README
  'api.example.com',               // exemple de gabarit dans un README
);

/** Retourne les hotes cites par un ensemble de fichiers. */
$hotes = function ($motif) use ($racine) {
  $trouves = array();

  foreach (glob($racine.'/'.$motif, GLOB_BRACE) as $fichier) {
    if (!is_file($fichier)) {
      continue;
    }

    if (preg_match_all('#https?://([a-zA-Z0-9.-]+)#', file_get_contents($fichier), $m)) {
      foreach ($m[1] as $hote) {
        $trouves[$hote][] = basename($fichier);
      }
    }
  }

  return $trouves;
};

// ---------------------------------------------------------------------------
// Garde-fou : sans lui, un motif de recherche casse rendrait tout le fichier vert.
// ---------------------------------------------------------------------------

$recettes = glob($racine.'/apps/frontend/config/desastres/recettes/*.yml');
$t->cmp_ok(count($recettes), '>=', 5, sprintf('les recettes sont bien trouvees (%d fichiers) : sans cela ce controle serait vert a vide', count($recettes)));

// ---------------------------------------------------------------------------
// 1. Les recettes.
// ---------------------------------------------------------------------------

$dansRecettes = $hotes('apps/frontend/config/desastres/recettes/*.yml');
$fautifs = array_intersect(array_keys($dansRecettes), $interdits);

$t->is_deeply(
  array_values($fautifs),
  array(),
  'aucune recette n appelle un hote de CDN'.($fautifs ? ' — '.implode(', ', $fautifs) : '')
);

// ---------------------------------------------------------------------------
// 2. Les ressources servies : le YAML ne dit pas tout. Quatorze mentions y avaient
//    survecu a la bascule des dix URL de recettes.
// ---------------------------------------------------------------------------

$dansRessources = $hotes('web/desastres/*/{javascript,stylesheets}/*.{js,css}');
$fautifs = array_intersect(array_keys($dansRessources), $interdits);

$t->is_deeply(
  array_values($fautifs),
  array(),
  'aucune ressource de desastre ne charge depuis un CDN'.($fautifs ? ' — '.implode(', ', $fautifs) : '')
);

// ---------------------------------------------------------------------------
// 3. Le schema, qui sert de gabarit. Il proposait des URL de CDN en exemple :
//    un gabarit enseigne, et celui-la enseignait le contraire de la decision.
// ---------------------------------------------------------------------------

$dansSchema = $hotes('apps/frontend/config/desastres/schemas/*.json');
$fautifs = array_intersect(array_keys($dansSchema), $interdits);

$t->is_deeply(
  array_values($fautifs),
  array(),
  'le schema ne propose aucune URL de CDN en exemple'.($fautifs ? ' — '.implode(', ', $fautifs) : '')
);

// ---------------------------------------------------------------------------
// 4. Les bibliotheques versees sont bien la, avec leur licence.
// ---------------------------------------------------------------------------

$verses = array(
  'frontend/assets/javascripts/gsap/gsap-3.13.0.min.js',
  'frontend/assets/javascripts/gsap/SplitText-3.13.0.min.js',
  'frontend/assets/javascripts/animejs/anime-3.2.2.min.js',
);

$manquants = array();
foreach ($verses as $chemin) {
  if (!is_file($racine.'/web/'.$chemin)) {
    $manquants[] = $chemin;
  }
  // Auto-heberger, c'est redistribuer : la licence se verse avec le fichier.
  if (!is_file($racine.'/web/'.$chemin.'.LICENSE.txt')) {
    $manquants[] = $chemin.'.LICENSE.txt';
  }
}

$t->is_deeply($manquants, array(), 'les bibliotheques versees sont presentes, chacune avec sa licence');

// ---------------------------------------------------------------------------
// 5. Tout hote vivant non prevu doit etre signale plutot que passer inapercu.
//
//    C'est cette assertion qui a fait apparaitre lottie.host et
//    allocestmamie.partouze-cagoule.fr, absents de tout inventaire — le releve du packet
//    n'avait lu que le YAML.
//
//    allocestmamie.partouze-cagoule.fr a ete confirme comme domaine du collectif et
//    figure desormais parmi les hotes autorises. lottie.host reste une EXCEPTION ECRITE :
//    l'auto-heberger demanderait de verser l'animation, dont la licence n'est pas
//    etablissable — son adresse est un UUID anonyme — et la specification interdit de
//    verser sans verifier. L'assertion le garde visible plutot que de le laisser filer.
// ---------------------------------------------------------------------------

$imprevus = array();
foreach (array_keys($dansRessources) as $hote) {
  $connu = false;
  foreach ($autorises as $a) {
    if ($hote === $a || substr($hote, -strlen($a) - 1) === '.'.$a) {
      $connu = true;

      break;
    }
  }

  if (!$connu) {
    $imprevus[$hote] = implode(', ', array_unique($dansRessources[$hote]));
  }
}

$t->is_deeply(
  $imprevus,
  array('lottie.host' => 'fish.js'),
  'la seule exception ecrite est lottie.host, et il n en apparait pas de nouvelle'
);
