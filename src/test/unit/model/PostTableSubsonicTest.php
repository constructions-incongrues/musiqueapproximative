<?php

/**
 * Couche de requetes PostTable pour l'API Subsonic.
 *
 * Verifie que la regle de visibilite unique (PostTable::WHERE_ONLINE) est
 * bien appliquee par chaque nouvelle methode, que les agregats (mois,
 * artistes, contributeurs) correspondent aux fixtures, que la selection de
 * champs FIELDS_SUBSONIC evite le lazy-load champ par champ de Doctrine 1,
 * et que les entrees limit/offset/like ne peuvent ni planter la requete ni
 * se comporter en joker sur cette surface publique non authentifiee.
 */

require_once dirname(__FILE__).'/../../bootstrap/database.php';

$t = new lime_test(47);

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

// juin 2024 porte les quatre facons d'etre invisible sauf une (le futur, qui
// est teste separement) : hors ligne (4), slug vide (6), slug NULL (7). Le
// morceau de carol (8, sans profil) doit lui remonter comme les autres.
$t->is_deeply(
  subsonic_ids($table->getPostsByMonth('2024-06')),
  array(1, 2, 8),
  'getPostsByMonth(2024-06) exclut le post hors ligne, le slug vide et le slug NULL ; garde les trois visibles dans l\'ordre de publication'
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
$t->is((int) $months[0]['song_count'], 3, 'juin 2024 compte trois morceaux visibles (alice x2, bob, carol)');
$t->is((int) $months[0]['duration'], 485, 'la duree de juin 2024 est la somme de 245, 180 et 60');
$t->is($months[1]['month'], '2024-05', 'le second mois est mai 2024');
$t->is($months[1]['duration'], null, 'SUM() sur l\'unique morceau de mai, sans duree, renvoie NULL et non 0');

$t->diag('getMonths() : entrees limite/offset hors bornes (Fix 4 et 5 de la revue)');

$t->is(count($table->getMonths(0)), 2, 'getMonths(0) omet le LIMIT et renvoie tout, sans planter');
$t->is(count($table->getMonths(-1)), 2, 'getMonths(-1) degrade vers "pas de limite" plutot que de produire un LIMIT invalide (500)');

$offsetOnly = $table->getMonths(null, 1);
$t->is(count($offsetOnly), 1, 'getMonths(null, 1) applique l\'offset meme sans limite explicite');
$t->is($offsetOnly[0]['month'], '2024-05', 'cet offset saute bien le premier mois');

$t->diag('getMonth()');

$juin = $table->getMonth('2024-06');
$t->is((int) $juin['song_count'], 3, 'getMonth(2024-06) compte trois morceaux');
$t->is((int) $juin['duration'], 485, 'getMonth(2024-06) somme les trois durees');
$t->is((int) $juin['cover_post_id'], 1, 'cover_post_id est le plus petit id du mois (post 1), pas le premier chronologiquement s\'ils divergent');
$t->is($table->getMonth('2099-01'), null, 'getMonth(2099-01) est null : le seul post de ce mois est dans le futur, donc invisible');

$t->diag('getDistinctArtists()');

$artists = array();
foreach ($table->getDistinctArtists() as $artist) {
  $artists[$artist['track_author']] = (int) $artist['album_count'];
}
$t->is(isset($artists['Sigur Rós']) ? $artists['Sigur Rós'] : null, 2, 'Sigur Rós publie sur deux mois distincts : album_count = 2');
$t->is(isset($artists['AC/DC']) ? $artists['AC/DC'] : null, 1, 'AC/DC publie sur un seul mois : album_count = 1');
$t->ok(!array_key_exists('Fantôme', $artists), 'Fantôme, dont aucun post n\'est visible, n\'apparait pas du tout');

$t->diag('getDistinctArtists() : entree "0" et metacaracteres LIKE (Fix 1 et 3 de la revue)');

$t->is(
  count($table->getDistinctArtists('0')),
  0,
  'getDistinctArtists(\'0\') applique bien le filtre (aucun artiste ne s\'appelle "0") : un simple if($like) l\'aurait ignore, "0" etant falsy en PHP'
);
$t->is(
  count($table->getDistinctArtists('AC%DC')),
  0,
  'le "%" saisi par le client est echappe : "AC%DC" ne matche pas "AC/DC" par coincidence de joker'
);
$t->is(
  count($table->getDistinctArtists('AC_DC')),
  0,
  'le "_" saisi par le client est echappe : "AC_DC" ne matche pas "AC/DC" en traitant "_" comme un joker un caractere'
);

$t->diag('getMonthsByArtist()');

$sigurMonths = $table->getMonthsByArtist('Sigur Rós');
$t->is(count($sigurMonths), 2, 'Sigur Rós a deux mois de publication visibles');
$t->is_deeply(
  array_map(function ($m) { return $m['month']; }, $sigurMonths),
  array('2024-06', '2024-05'),
  'les mois de Sigur Rós sont tries du plus recent au plus ancien'
);

$t->diag('getPostsByArtist()');

$t->is_deeply(
  subsonic_ids($table->getPostsByArtist('Sigur Rós')),
  array(3, 1),
  'getPostsByArtist(Sigur Rós) renvoie les deux morceaux dans l\'ordre de publication (mai puis juin)'
);
$t->is_deeply(
  subsonic_ids($table->getPostsByArtist('Sigur Rós', 1)),
  array(3),
  'getPostsByArtist(Sigur Rós, 1) : premiere page'
);
$t->is_deeply(
  subsonic_ids($table->getPostsByArtist('Sigur Rós', 1, 1)),
  array(1),
  'getPostsByArtist(Sigur Rós, 1, 1) : seconde page, via l\'offset'
);
$t->is_deeply(
  subsonic_ids($table->getPostsByArtist('Sigur Rós', -5)),
  array(3, 1),
  'une limite negative ne plante pas la requete : elle degrade vers "pas de limite" (le clamp de buildOnlinePostsQuery)'
);

$t->diag('getContributors()');

$contributors = array();
foreach ($table->getContributors() as $c) {
  $contributors[$c['username']] = $c;
}
$t->is((int) $contributors['alice']['song_count'], 2, 'alice a deux morceaux visibles (les trois autres lui appartenant sont exclus)');
$t->is((int) $contributors['alice']['duration'], 245, 'la duree d\'alice ignore le morceau sans duree (SUM saute les NULL)');
$t->is($contributors['alice']['display_name'], 'Alice', 'alice a un profil : son display_name vient de user_profile');
$t->is((int) $contributors['bob']['song_count'], 1, 'bob a un morceau visible');
$t->is((int) $contributors['bob']['duration'], 180, 'la duree de bob correspond a son unique morceau');
$t->is((int) $contributors['carol']['song_count'], 1, 'carol, qui n\'a pas de ligne user_profile, apparait tout de meme');
$t->is((int) $contributors['carol']['duration'], 60, 'la duree de carol correspond a son unique morceau');
$t->is(
  $contributors['carol']['display_name'],
  'carol',
  'COALESCE(up.display_name, u.username) : sans profil, on retombe sur le username plutot que sur NULL (playlist sans nom)'
);

$t->diag('searchSongs()');

$t->is_deeply(subsonic_ids($table->searchSongs('Rock')), array(1), 'searchSongs(Rock) trouve le post 1 par son titre');
$t->is_deeply(
  subsonic_ids($table->searchSongs('')),
  array(8, 2, 1, 3),
  'une recherche vide renvoie l\'ensemble pagine des posts visibles, du plus recent au plus ancien (ordre herite du builder, pas casse par un orderBy local)'
);
$t->is_deeply(
  subsonic_ids($table->searchSongs('Fantôme')),
  array(),
  'une recherche qui ne matche que des posts invisibles (auteur Fantôme) ne renvoie rien'
);
$t->is_deeply(
  subsonic_ids($table->searchSongs('Sigur')),
  array(1, 3),
  'searchSongs(Sigur) trouve par artiste et pas seulement par titre — c\'est la raison documentee de ne pas utiliser PostTable::search()'
);

$t->diag('Pagination de getMonths()');

$page1 = $table->getMonths(1);
$page2 = $table->getMonths(1, 1);
$t->is(count($page1), 1, 'getMonths(1) renvoie une seule ligne');
$t->is($page1[0]['month'], '2024-06', 'la premiere page est le mois le plus recent');
$t->is(count($page2), 1, 'getMonths(1, 1) renvoie une seule ligne');
$t->is($page2[0]['month'], '2024-05', 'la seconde page, avec un offset de 1, est l\'autre mois');

$t->diag('FIELDS_SUBSONIC : pas de lazy-load champ par champ, et contre-epreuve');

$connection = Doctrine_Manager::getInstance()->getCurrentConnection();
$posts = $table->getPostsByMonth('2024-06');

$queriesBeforePublish = $connection->count();
foreach ($posts as $post) {
  // Doctrine 1 emet une requete par acces a une colonne absente du SELECT
  // d'origine. publish_on doit figurer dans FIELDS_SUBSONIC : le lire sur
  // chaque post ne doit declencher aucune requete supplementaire.
  $post->publish_on;
}
$t->is(
  $connection->count(),
  $queriesBeforePublish,
  'lire publish_on sur chaque post d\'un mois ne declenche aucune requete supplementaire'
);

$queriesBeforeBody = $connection->count();
foreach ($posts as $post) {
  // Contre-epreuve : body n'est PAS dans FIELDS_SUBSONIC. Si l'assertion
  // precedente passait aussi quand FIELDS_SUBSONIC valait '*', elle ne
  // prouverait rien. Ici, le compteur doit bouger.
  $post->body;
}
$t->ok(
  $connection->count() > $queriesBeforeBody,
  'contre-epreuve : lire body (absent de FIELDS_SUBSONIC) declenche bien un lazy-load, ce qui montre que l\'assertion precedente n\'etait pas vide de sens'
);
