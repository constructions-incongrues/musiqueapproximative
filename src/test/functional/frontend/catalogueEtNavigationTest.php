<?php

/**
 * Ce que fait le catalogue : ordre, filtrage, recherche, navigation.
 *
 * Onze scenarios de `catalogue-morceaux` decrivent ces comportements et aucun
 * n'etait exerce. `postActionsTest` demande `/posts` et constate un 200 ; il ne
 * regarde ni l'ordre, ni l'effet de `c`, ni celui de `q`.
 *
 * Chaque assertion nomme le scenario qu'elle exerce.
 *
 * @see openspec/specs/catalogue-morceaux/spec.md
 */

include dirname(__FILE__).'/../../bootstrap/functional.php';
require_once dirname(__FILE__).'/../../bootstrap/database.php';

$browser = new sfTestFunctional(new sfBrowser(), new lime_test(20));
$t = $browser->test();

$table = Doctrine_Core::getTable('Post');

// Voir l'en-tete de representationJsonTest.php : le premier rendu Markdown d'un
// processus emet des avertissements qui polluent le corps de la reponse.
$browser->get('/posts');

$publiables = $table->createQuery('p')
  ->where('p.is_online = 1 AND p.publish_on <= NOW()')
  ->andWhere("p.slug IS NOT NULL AND p.slug != ''")
  ->orderBy('p.publish_on DESC')
  ->execute();

$t->ok(count($publiables) >= 3, 'les fixtures portent assez de morceaux pour observer un ordre');

// ------------------------------------------------------ Ordre du catalogue

$t->diag('Requirement: Ordre du catalogue');

$browser->get('/posts?format=max');
$lignes = array_values(array_filter(explode("\n", trim($browser->getResponse()->getContent())), function ($l) {
  return '' !== trim($l);
}));

$titresServis = array();
foreach ($lignes as $ligne) {
  preg_match_all('#"([^"]*)"#', $ligne, $m);
  $titresServis[] = $m[1][1];
}

$titresAttendus = array();
foreach ($publiables as $p) {
  $titresAttendus[] = str_replace('"', '', html_entity_decode($p->track_title));
}

$t->is($titresServis, $titresAttendus,
  'Scenario Ordre d une liste : du plus recent au plus ancien');

// ------------------------------------------------- Liste d un contributeur

$t->diag('Requirement: Liste et recherche plein texte');

$contributeur = $publiables[0]->getSfGuardUser()->username;
$aLui = $table->createQuery('p')
  ->leftJoin('p.sfGuardUser u')
  ->where('p.is_online = 1 AND p.publish_on <= NOW()')
  ->andWhere("p.slug IS NOT NULL AND p.slug != ''")
  ->andWhere('u.username = ?', $contributeur)
  ->count();

$browser->get('/posts?format=max&c='.$contributeur);
$lignesC = array_values(array_filter(explode("\n", trim($browser->getResponse()->getContent())), function ($l) {
  return '' !== trim($l);
}));

$t->is(count($lignesC), $aLui, 'Scenario Liste d un contributeur : seuls ses morceaux sont listes');
$t->ok($aLui < count($publiables), 'le filtre retire bien quelque chose : sans cela l assertion precedente ne prouve rien');

$browser->get('/posts?c='.$contributeur);
$t->like($browser->getResponse()->getContent(), '#'.preg_quote($contributeur, '#').'#i',
  'Scenario Liste d un contributeur : le titre de la page annonce sa playlist');

// ------------------------------------------------------ Recherche par termes

$cherche = $publiables[0]->track_author;
$attendus = $table->search($cherche);

$browser->get('/posts?format=max&q='.rawurlencode($cherche));
$lignesQ = array_values(array_filter(explode("\n", trim($browser->getResponse()->getContent())), function ($l) {
  return '' !== trim($l);
}));

$t->ok(count($lignesQ) > 0, 'Scenario Recherche par termes : la recherche remonte des morceaux');
$t->is(count($lignesQ), count($attendus),
  'Scenario Recherche par termes : les morceaux correspondant aux termes, et eux seuls');

$browser->get('/posts?q='.rawurlencode($cherche));
$page = $browser->getResponse()->getContent();
$t->like($page, '#'.preg_quote($cherche, '#').'#i',
  'Scenario Recherche par termes : le titre de la page reprend les termes cherches');
$t->like($page, '#'.count($attendus).'#',
  'Scenario Recherche par termes : le titre annonce le nombre de resultats');

// Un morceau non publiable ne doit pas remonter, meme s il correspond.
$invisible = $table->createQuery('p')->where('p.is_online = 0')->fetchOne();

if ($invisible) {
  $resultats = $table->search($invisible->track_author);
  $slugs = array();
  foreach ($resultats as $r) { $slugs[] = $r->slug; }

  $t->ok(!in_array($invisible->slug, $slugs, true),
    'Scenario Resultats de recherche non publiables : un morceau hors ligne est ecarte');
} else {
  $t->fail('les fixtures ne portent aucun morceau hors ligne : le scenario ne peut pas etre exerce');
}

// ------------------------------------------------- Navigation sequentielle

$t->diag('Requirement: Navigation sequentielle et Reponse de navigation');

$milieu = null;
foreach ($publiables as $candidat) {
  $s = $table->getNextPost($candidat, array());
  $p = $table->getPreviousPost($candidat, array());
  if ($s && $s->slug && $p && $p->slug) { $milieu = $candidat; break; }
}

$t->ok($milieu, 'les fixtures portent un morceau ayant un voisin de chaque cote');

$suivant = $table->getNextPost($milieu, array());
$precedent = $table->getPreviousPost($milieu, array());

$browser->get('/posts/next?current='.$milieu->id);
$rn = json_decode($browser->getResponse()->getContent(), true);

$t->is(array_keys($rn), array('url', 'title'),
  'Scenario Structure de la reponse : la reponse porte url et title, et rien de plus');
$t->like($rn['url'], '#'.preg_quote($suivant->slug, '#').'#',
  'Scenario Morceau suivant : c est bien le morceau publie juste apres');
$t->like($rn['title'], '#'.preg_quote($suivant->track_title, '#').'#',
  'Scenario Structure de la reponse : title porte l intitule du morceau');

$browser->get('/posts/prev?current='.$milieu->id);
$rp = json_decode($browser->getResponse()->getContent(), true);

$t->like($rp['url'], '#'.preg_quote($precedent->slug, '#').'#',
  'Scenario Morceau precedent : c est bien le morceau publie juste avant');

// Navigation restreinte a un contributeur.
$restreint = $table->getNextPost($milieu, array('c' => $contributeur));

$browser->get('/posts/next?current='.$milieu->id.'&c='.$contributeur);
$statut = $browser->getResponse()->getStatusCode();

if ($restreint && $restreint->slug) {
  $rr = json_decode($browser->getResponse()->getContent(), true);
  $t->like($rr['url'], '#'.preg_quote($restreint->slug, '#').'#',
    'Scenario Navigation restreinte a un contributeur : le voisin est cherche dans sa seule playlist');
} else {
  $t->is($statut, 404,
    'Scenario Navigation restreinte a un contributeur : sans voisin dans sa playlist, la reponse le dit');
}

// ---------------------------------------------------------- Tirage aleatoire

$t->diag('Requirement: Tirage aleatoire');

$browser->get('/posts/random');
$ra = json_decode($browser->getResponse()->getContent(), true);

$t->is(array_keys((array) $ra), array('url', 'title'),
  'Scenario Morceau aleatoire : la reponse a la meme forme que la navigation');

$slugsPubliables = array();
foreach ($publiables as $p) { $slugsPubliables[] = $p->slug; }

$tire = basename(parse_url($ra['url'], PHP_URL_PATH));

// Le tirage est aleatoire ET mis en cache : constater une fois ne demontre rien.
// On interroge donc le modele, qui est ce que la route appelle, sur assez de
// tirages pour que le hasard ne masque plus rien.
//
// Ce scenario a longtemps ete marque NON VERIFIE : `getRandomPost()` reecrivait
// la condition de publiabilite a la main, sans la clause de slug de
// `WHERE_ONLINE`, et pouvait donc servir `/post/` — une page morte. Un seul
// morceau de la production etait dans ce cas, et c'est le meme defaut vu deux
// fois : son titre cyrillique detruit par l'encodage latin1 n'a laisse aucun
// slug a construire.
$sansSlug = array();

foreach ($table->createQuery('p')
  ->where("p.is_online = 1 AND p.publish_on <= NOW() AND (p.slug IS NULL OR p.slug = '')")
  ->execute() as $muet) {
  $sansSlug[] = $muet->id;
}

$tires = array();

for ($i = 0; $i < 60; $i++) {
  $tire = $table->getRandomPost(array());
  $tires[] = $tire ? $tire->id : null;
}

$t->is(
  array_values(array_intersect($tires, $sansSlug)),
  array(),
  'Scenario Morceau aleatoire : sur 60 tirages, aucun morceau sans identifiant d URL'
);

$t->ok(count(array_unique($tires)) > 1, '60 tirages donnent plusieurs morceaux : le hasard joue bien');

$browser->get('/posts/random?c='.$contributeur);
$rac = json_decode($browser->getResponse()->getContent(), true);
$tireC = basename(parse_url($rac['url'], PHP_URL_PATH));

$morceauTire = $table->createQuery('p')->where('p.slug = ?', $tireC)->fetchOne();
$t->is(
  $morceauTire ? $morceauTire->getSfGuardUser()->username : null,
  $contributeur,
  'Scenario Tirage restreint a un contributeur : le morceau tire est de ce contributeur'
);
