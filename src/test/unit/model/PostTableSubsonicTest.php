<?php

/**
 * Couche de requetes PostTable pour l'API Subsonic.
 *
 * Verifie que la regle de visibilite unique (PostTable::WHERE_ONLINE) est
 * bien appliquee par chaque nouvelle methode, que les agregats (mois,
 * artistes, contributeurs) correspondent aux fixtures, et que la
 * selection de champs FIELDS_SUBSONIC evite le lazy-load champ par champ
 * de Doctrine 1.
 */

require_once dirname(__FILE__).'/../../bootstrap/database.php';

$t = new lime_test(26);

$table = Doctrine_Core::getTable('Post');

/**
 * Extrait les ids d'une collection de Post, dans leur ordre d'iteration.
 */
function subsonic_ids($collection)
{
  $ids = array();
  foreach ($collection as $post) {
    $ids[] = (int) $post->id;
  }

  return $ids;
}

$t->diag('Regle de visibilite, via les nouvelles requetes Subsonic');

$t->is_deeply(
  subsonic_ids($table->getPostsByMonth('2024-06')),
  array(1, 2),
  'getPostsByMonth(2024-06) exclut le post hors ligne et le post sans slug, garde les deux visibles dans l\'ordre de publication'
);
$t->is_deeply(
  subsonic_ids($table->getPostsByMonth('2024-05')),
  array(3),
  'getPostsByMonth(2024-05) renvoie le post visible sans duree'
);
$t->is_deeply(
  subsonic_ids($table->getPostsByMonth('2099-01')),
  array(),
  'getPostsByMonth(2099-01) exclut le post programme dans le futur'
);

$t->diag('getMonths()');

$months = $table->getMonths();
$t->is(count($months), 2, 'getMonths() renvoie deux mois (les invisibles ne forment pas de mois a eux seuls)');
$t->is($months[0]['month'], '2024-06', 'par defaut, le mois le plus recent vient en premier');
$t->is((int) $months[0]['song_count'], 2, 'juin 2024 compte deux morceaux visibles');
$t->is((int) $months[0]['duration'], 425, 'la duree de juin 2024 est la somme de 245 et 180');
$t->is($months[1]['month'], '2024-05', 'le second mois est mai 2024');
$t->is($months[1]['duration'], null, 'SUM() sur l\'unique morceau de mai, sans duree, renvoie NULL et non 0');

$t->diag('getDistinctArtists()');

$artists = array();
foreach ($table->getDistinctArtists() as $artist) {
  $artists[$artist['track_author']] = (int) $artist['album_count'];
}
$t->is(isset($artists['Sigur Rós']) ? $artists['Sigur Rós'] : null, 2, 'Sigur Rós publie sur deux mois distincts : album_count = 2');
$t->is(isset($artists['AC/DC']) ? $artists['AC/DC'] : null, 1, 'AC/DC publie sur un seul mois : album_count = 1');
$t->ok(!array_key_exists('Fantôme', $artists), 'Fantôme, dont aucun post n\'est visible, n\'apparait pas du tout');

$t->diag('getMonthsByArtist()');

$sigurMonths = $table->getMonthsByArtist('Sigur Rós');
$t->is(count($sigurMonths), 2, 'Sigur Rós a deux mois de publication visibles');
$t->is_deeply(
  array_map(function ($m) { return $m['month']; }, $sigurMonths),
  array('2024-06', '2024-05'),
  'les mois de Sigur Rós sont tries du plus recent au plus ancien'
);

$t->diag('getContributors()');

$contributors = array();
foreach ($table->getContributors() as $c) {
  $contributors[$c['username']] = $c;
}
$t->is((int) $contributors['alice']['song_count'], 2, 'alice a deux morceaux visibles (les trois autres lui appartenant sont exclus)');
$t->is((int) $contributors['alice']['duration'], 245, 'la duree d\'alice ignore le morceau sans duree (SUM saute les NULL)');
$t->is((int) $contributors['bob']['song_count'], 1, 'bob a un morceau visible');
$t->is((int) $contributors['bob']['duration'], 180, 'la duree de bob correspond a son unique morceau');

$t->diag('searchSongs()');

$t->is_deeply(subsonic_ids($table->searchSongs('Rock')), array(1), 'searchSongs(Rock) trouve le post 1 par son titre');
// Tri par publish_on croissant : le post 3 (mai) precede les posts 1 et 2
// (juin), meme si son id est plus grand — l'id d'insertion des fixtures
// ne suit pas l'ordre chronologique.
$t->is_deeply(subsonic_ids($table->searchSongs('')), array(3, 1, 2), 'une recherche vide renvoie l\'ensemble pagine des posts visibles, par ordre de publication');
$t->is_deeply(
  subsonic_ids($table->searchSongs('Fantôme')),
  array(),
  'une recherche qui ne matche que des posts invisibles (auteur Fantôme) ne renvoie rien'
);

$t->diag('Pagination de getMonths()');

$page1 = $table->getMonths(1);
$page2 = $table->getMonths(1, 1);
$t->is(count($page1), 1, 'getMonths(1) renvoie une seule ligne');
$t->is($page1[0]['month'], '2024-06', 'la premiere page est le mois le plus recent');
$t->is(count($page2), 1, 'getMonths(1, 1) renvoie une seule ligne');
$t->is($page2[0]['month'], '2024-05', 'la seconde page, avec un offset de 1, est l\'autre mois');

$t->diag('FIELDS_SUBSONIC : pas de lazy-load champ par champ');

$connection = Doctrine_Manager::getInstance()->getCurrentConnection();
$posts = $table->getPostsByMonth('2024-06');
$queriesBefore = $connection->count();
foreach ($posts as $post) {
  // Doctrine 1 emet une requete par acces a une colonne absente du SELECT
  // d'origine. publish_on doit figurer dans FIELDS_SUBSONIC : le lire sur
  // chaque post ne doit declencher aucune requete supplementaire.
  $post->publish_on;
}
$t->is(
  $connection->count(),
  $queriesBefore,
  'lire publish_on sur chaque post d\'un mois ne declenche aucune requete supplementaire'
);
