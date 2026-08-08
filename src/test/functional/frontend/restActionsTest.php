<?php

include(dirname(__FILE__).'/../../bootstrap/functional.php');
require_once dirname(__FILE__).'/../../bootstrap/database.php';

$browser = new sfTestFunctional(new sfBrowser());

$t = $browser->test();

// --- ping, en XML et en JSON ---

$browser->get('/rest/ping.view');
$t->is($browser->getResponse()->getStatusCode(), 200, 'ping.view : repond 200');

$xml = new SimpleXMLElement($browser->getResponse()->getContent());
$t->is((string) $xml['status'], 'ok', 'ping.view : status ok en XML');

$browser->get('/rest/ping.view?f=json');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['status'], 'ok', 'ping.view?f=json : status ok en JSON');

// --- la forme sans .view (clients OpenSubsonic) ---

$browser->get('/rest/ping');
$t->is($browser->getResponse()->getStatusCode(), 200, '/rest/ping (sans .view) repond 200');
$xml = new SimpleXMLElement($browser->getResponse()->getContent());
$t->is((string) $xml['status'], 'ok', '/rest/ping (sans .view) : status ok en XML');

// --- Content-Type : du JSON simple, jamais du JSON API ---

$browser->get('/rest/ping.view?f=json');
$contentType = $browser->getResponse()->getHttpHeader('Content-Type');
$t->like($contentType, '#application/json#', 'Content-Type : application/json');
$t->unlike($contentType, '#vnd\.api\+json#', 'Content-Type : JsonApiFilter ne touche pas au module rest');

// --- methode inconnue : erreur 70, HTTP 200 (Subsonic repond toujours 200) ---

$browser->get('/rest/getNothing.view?f=json');
$t->is($browser->getResponse()->getStatusCode(), 200, 'methode inconnue : HTTP 200 quand meme');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['status'], 'failed', 'methode inconnue : status failed');
$t->is($json['subsonic-response']['error']['code'], 70, 'methode inconnue : code 70');

// --- parametre requis manquant : erreur 10 ---
//
// Aucun gestionnaire de la tache 7 n'appelle encore requireParameter() (les
// methodes qui en auront besoin, comme getAlbum, arrivent dans une tache
// ulterieure) : on verifie donc le helper directement, sur l'instance
// d'action reellement construite par le dispatcher, plutot que d'inventer
// un gestionnaire qui n'a pas sa place ici.
$browser->get('/rest/ping.view');
$actionInstance = $browser->getContext()->getActionStack()->getLastEntry()->getActionInstance();
$reflection = new ReflectionMethod($actionInstance, 'requireParameter');
$reflection->setAccessible(true);

try {
  $reflection->invoke($actionInstance, $browser->getContext()->getRequest(), 'inexistant');
  $t->fail('requireParameter() aurait du lever une SubsonicException');
} catch (SubsonicException $e) {
  $t->is($e->getCode(), 10, 'requireParameter() sur un parametre absent : code 10');
}

// --- star : refus, code 50 (serveur en lecture seule) ---

$browser->get('/rest/star.view?f=json&id=1');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 50, 'star : code 50 (lecture seule)');

// --- un talon serialise son conteneur vide en objet ({}), pas en tableau ([]) ---
//
// json_decode(..., true) confond {} et [] (les deux redeviennent un tableau
// PHP vide) : on decode donc en objets (sans le flag $assoc) pour distinguer
// les deux formes, exactement ce que verifierait un client Subsonic strict.

$browser->get('/rest/getGenres.view?f=json');
$decoded = json_decode($browser->getResponse()->getContent());
$t->ok(is_object($decoded->{'subsonic-response'}->genres), 'getGenres : conteneur vide serialise en objet JSON, pas en tableau');

// --- getAlbumList2 --------------------------------------------------------
//
// Fixtures (src/data/fixtures/subsonic.sql) : 2024-06 a trois morceaux
// visibles (245 + 180 + 60 = 485s), 2024-05 en a un seul, sans duree.

$browser->get('/rest/getAlbumList2.view?f=json&type=newest');
$json = json_decode($browser->getResponse()->getContent(), true);
$albums = $json['subsonic-response']['albumList2']['album'];
$t->is($albums[0]['id'], 'al-2024-06', 'getAlbumList2 : le mois le plus recent en tete');
$t->is($albums[0]['songCount'], 3, 'getAlbumList2 : songCount present');
$t->is($albums[0]['artist'], 'Various Artists', 'getAlbumList2 : artiste de compilation');
$t->ok(!isset($albums[0]['artistId']), 'getAlbumList2 : pas d artistId pendant');
$t->is($albums[0]['duration'], 485, 'getAlbumList2 : duree cumulee du mois');
$t->ok(!isset($albums[1]['duration']), 'getAlbumList2 : duree absente quand tous les morceaux du mois en sont depourvus');

$browser->get('/rest/getAlbumList2.view?f=json&type=newest&size=1');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is(count($json['subsonic-response']['albumList2']['album']), 1, 'getAlbumList2 : size respecte');

$browser->get('/rest/getAlbumList2.view?f=json&type=newest&size=1&offset=1');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['albumList2']['album'][0]['id'], 'al-2024-05', 'getAlbumList2 : offset respecte');

$browser->get('/rest/getAlbumList2.view?f=json&type=newest&size=99999');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->ok(count($json['subsonic-response']['albumList2']['album']) <= 500, 'getAlbumList2 : size plafonne a 500');

$browser->get('/rest/getAlbumList2.view?f=json&type=alphabeticalByName&size=500');
$json = json_decode($browser->getResponse()->getContent(), true);
$albums = $json['subsonic-response']['albumList2']['album'];
$t->is($albums[0]['id'], 'al-2024-04', 'getAlbumList2 : alphabeticalByName trie par ordre croissant');

$browser->get('/rest/getAlbumList2.view?f=json&type=byYear&size=500');
$json = json_decode($browser->getResponse()->getContent(), true);
$albums = $json['subsonic-response']['albumList2']['album'];
$t->is($albums[0]['id'], 'al-2024-04', 'getAlbumList2 : byYear trie par ordre croissant');

$browser->get('/rest/getAlbumList2.view?f=json&type=random&size=500');
$json = json_decode($browser->getResponse()->getContent(), true);
$ids = array();
foreach ($json['subsonic-response']['albumList2']['album'] as $album) {
  $ids[] = $album['id'];
}
sort($ids);
$t->is($ids, array('al-2024-04', 'al-2024-05', 'al-2024-06'), 'getAlbumList2 : random retourne les memes mois, sans en perdre');

// --- getAlbum --------------------------------------------------------------

$browser->get('/rest/getAlbum.view?f=json&id=al-2024-06');
$json = json_decode($browser->getResponse()->getContent(), true);
$album = $json['subsonic-response']['album'];
$t->is(count($album['song']), 3, 'getAlbum : les trois morceaux du mois');
$t->is($album['song'][0]['id'], '1', 'getAlbum : premier morceau publie en tete');
$t->is($album['song'][0]['track'], 1, 'getAlbum : numero de piste calcule');
$t->is($album['song'][1]['track'], 2, 'getAlbum : deuxieme piste');
$t->is($album['song'][2]['track'], 3, 'getAlbum : troisieme piste');
$t->is($album['song'][0]['title'], 'Rock & Roll', 'getAlbum : titre avec « & » intact en JSON');

$browser->get('/rest/getAlbum.view?f=json&id=al-2024-05');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->ok(!isset($json['subsonic-response']['album']['duration']), 'getAlbum : duree absente pour un album sans morceau chiffre');

$browser->get('/rest/getAlbum.view?f=json&id=al-1999-01');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 70, 'getAlbum : mois vide -> 70');

$browser->get('/rest/getAlbum.view?f=json&id=nimportequoi');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 70, 'getAlbum : id malforme -> 70');

// --- getSong -----------------------------------------------------------------

$browser->get('/rest/getSong.view?f=json&id=1');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['song']['id'], '1', 'getSong : renvoie le morceau');

$browser->get('/rest/getSong.view?f=json&id=999999');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 70, 'getSong : id inconnu -> 70');

// id 4 : hors ligne (is_online = 0) -- doit rester invisible malgre un id
// numerique valide. C'est l'assertion la plus importante de la tache : elle
// verifie que findPost() passe bien par buildOnlinePostsQuery() et non par
// une requete ad hoc qui ignorerait la regle de visibilite.
$browser->get('/rest/getSong.view?f=json&id=4');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 70, 'getSong : morceau invisible -> 70, jamais le morceau');
$t->ok(!isset($json['subsonic-response']['song']), 'getSong : aucune cle song dans la reponse d erreur');

// --- getArtists ----------------------------------------------------------
//
// Fixtures : AC/DC, Carol Solo et Sigur Rós sont visibles ; Fantôme ne l'est
// jamais (ses quatre posts sont tous invisibles, cf. subsonic.sql). C'est
// l'assertion la plus importante de la tache : elle verifie que la regle de
// visibilite atteint la dimension artiste, pas seulement les morceaux.

$browser->get('/rest/getArtists.view?f=json');
$json = json_decode($browser->getResponse()->getContent(), true);
$index = $json['subsonic-response']['artists']['index'];

$names = [];
foreach ($index as $group) {
  foreach ($group['artist'] as $artist) {
    $names[] = $artist['name'];
  }
}
sort($names);
$t->is($names, ['AC/DC', 'Carol Solo', 'Sigur Rós'], 'getArtists : les trois artistes visibles, jamais Fantôme');

// post 9 (auteur vide, cf. subsonic.sql) ne doit produire ni une entree au
// nom vide, ni l'id "ar-" que SubsonicId::forArtist('') fabriquerait et que
// parseArtist() refuserait ensuite (erreur 70 a l'ouverture) : c'est le
// regression test cote HTTP du fix de getDistinctArtists().
$hasEmptyOrDeadArtist = false;
foreach ($index as $group) {
  foreach ($group['artist'] as $artist) {
    if ('' === $artist['name'] || 'ar-' === $artist['id']) {
      $hasEmptyOrDeadArtist = true;
    }
  }
}
$t->ok(!$hasEmptyOrDeadArtist, 'getArtists : aucun artiste au nom vide, aucun id "ar-" (l\'auteur vide du post 9 ne doit jamais y figurer)');

$byName = [];
foreach ($index as $group) {
  foreach ($group['artist'] as $artist) {
    $byName[$artist['name']] = $artist;
  }
}
$t->is($byName['Sigur Rós']['albumCount'], 2, 'getArtists : albumCount de Sigur Rós = 2 (deux mois)');

$byLetter = [];
foreach ($index as $group) {
  $byLetter[$group['name']] = array_map(function ($artist) { return $artist['name']; }, $group['artist']);
}
$t->ok(in_array('AC/DC', $byLetter['A']), 'getArtists : AC/DC dans l index A');
$t->ok(in_array('Sigur Rós', $byLetter['S']), 'getArtists : Sigur Rós dans l index S');

$letters = array_map(function ($group) { return $group['name']; }, $index);
$sortedLetters = $letters;
sort($sortedLetters);
$t->is($letters, $sortedLetters, 'getArtists : index trie par lettre');

// --- getArtist -------------------------------------------------------------

$sigurId = SubsonicId::forArtist('Sigur Rós');
$browser->get('/rest/getArtist.view?f=json&id='.urlencode($sigurId));
$json = json_decode($browser->getResponse()->getContent(), true);
$artist = $json['subsonic-response']['artist'];
$t->is(count($artist['album']), 2, 'getArtist : Sigur Rós a deux albums (deux mois)');

$fantomeId = SubsonicId::forArtist('Fantôme');
$browser->get('/rest/getArtist.view?f=json&id='.urlencode($fantomeId));
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 70, 'getArtist : artiste sans post visible (Fantôme) -> 70');

$browser->get('/rest/getArtist.view?f=json&id=nimportequoi');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 70, 'getArtist : id malforme -> 70');

// --- search3 -----------------------------------------------------------
//
// Fixtures (src/data/fixtures/subsonic.sql), morceaux visibles seulement :
//   1 Rock & Roll  / Sigur Rós  / 2024-06
//   2 A < B        / AC/DC      / 2024-06
//   8 Solo         / Carol Solo / 2024-06
//   3 Ancien       / Sigur Rós  / 2024-05
//   9 Anonyme      / (vide)     / 2024-04
// Les posts 4 a 7 (Fantôme) sont tous invisibles. Une requete vide rend les
// cinq, du plus recent au plus ancien : 8, 2, 1, 3, 9 (cf. searchSongs()).

$browser->get('/rest/search3.view?f=json&query=Rock');
$json = json_decode($browser->getResponse()->getContent(), true);
$songs = $json['subsonic-response']['searchResult3']['song'];
$t->is(count($songs), 1, 'search3 : trouve le morceau par titre');
$t->is($songs[0]['id'], '1', 'search3 : c est bien le morceau 1 (Rock & Roll)');

// Sigur Rós est l'artiste de deux morceaux visibles (posts 1 et 3) : c'est le
// cas qui justifie de ne pas reutiliser PostTable::search() (recherche par
// titre de post uniquement, aucun resultat "artist" possible).
$browser->get('/rest/search3.view?f=json&query=Sigur');
$json = json_decode($browser->getResponse()->getContent(), true);
$result = $json['subsonic-response']['searchResult3'];
$t->is(count($result['artist']), 1, 'search3 : trouve l artiste Sigur Rós');
$t->is($result['artist'][0]['name'], 'Sigur Rós', 'search3 : le bon artiste');
$t->is(count($result['song']), 2, 'search3 : trouve aussi ses deux morceaux visibles');

$browser->get('/rest/search3.view?f=json&query=');
$json = json_decode($browser->getResponse()->getContent(), true);
$songs = $json['subsonic-response']['searchResult3']['song'];
$t->is(count($songs), 5, 'search3 : requete vide = tout parcourir (pagine)');
$ids = array_map(function ($s) { return $s['id']; }, $songs);
$t->is($ids, array('8', '2', '1', '3', '9'), 'search3 : requete vide, ordre du plus recent au plus ancien');

// Fantôme n'a que des posts invisibles : la recherche ne doit jamais les
// faire remonter, ni cote morceau ni cote artiste.
$browser->get('/rest/search3.view?f=json&query=Fantome');
$json = json_decode($browser->getResponse()->getContent(), true);
$result = $json['subsonic-response']['searchResult3'];
$t->is(count($result['song']), 0, 'search3 : morceaux de Fantôme (invisible) jamais remontes');
$t->is(count($result['artist']), 0, 'search3 : Fantôme absent aussi cote artiste');

// --- search3 : zero resultat serialise en objet, jamais en tableau -------
//
// json_decode(..., true) confond {} et [] : on decode donc en objets (sans
// $assoc) pour distinguer les deux, comme le test getGenres plus haut.

$browser->get('/rest/search3.view?f=json&query=zzzzzzz');
$raw = $browser->getResponse()->getContent();
$t->ok(false === strpos($raw, '"searchResult3":[]'), 'search3 : zero resultat, jamais searchResult3 serialise en tableau vide');

$decoded = json_decode($raw);
$result = $decoded->{'subsonic-response'}->searchResult3;
$t->ok(is_object($result), 'search3 : conteneur searchResult3 toujours un objet JSON, meme sans resultat');
$t->ok(isset($result->album) && is_array($result->album) && 0 === count($result->album), 'search3 : album present mais toujours vide (pas de correspondance textuelle pertinente)');

// --- search3 : songCount / songOffset -------------------------------------

$browser->get('/rest/search3.view?f=json&query=&songCount=2');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is(count($json['subsonic-response']['searchResult3']['song']), 2, 'search3 : songCount respecte');

$browser->get('/rest/search3.view?f=json&query=&songCount=2&songOffset=1');
$json = json_decode($browser->getResponse()->getContent(), true);
$songs = $json['subsonic-response']['searchResult3']['song'];
$t->is(array_map(function ($s) { return $s['id']; }, $songs), array('2', '1'), 'search3 : songOffset respecte');

// --- search3 : "%" et "_" traites comme des caracteres litteraux ---------
//
// Ni le titre ni l'auteur d'aucun morceau visible ne contient un "%" ou un
// "_" litteral : un client qui saisirait l'un de ces caracteres ne doit
// jamais obtenir "tout" en retour (ce que produirait un joker LIKE non
// echappe), mais bien zero resultat.

$browser->get('/rest/search3.view?f=json&query='.urlencode('%'));
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is(count($json['subsonic-response']['searchResult3']['song']), 0, 'search3 : "%" traite comme un caractere litteral, pas comme un joker');

$browser->get('/rest/search3.view?f=json&query='.urlencode('_'));
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is(count($json['subsonic-response']['searchResult3']['song']), 0, 'search3 : "_" traite comme un caractere litteral, pas comme un joker');

// --- search2 : alias legacy de search3, sous "searchResult2" -------------

$browser->get('/rest/search2.view?f=json&query=Rock');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is(count($json['subsonic-response']['searchResult2']['song']), 1, 'search2 : meme donnees que search3');
$t->is($json['subsonic-response']['searchResult2']['song'][0]['id'], '1', 'search2 : c est bien le morceau 1');
$t->ok(!isset($json['subsonic-response']['searchResult3']), 'search2 : pas de cle searchResult3 residuelle');

// --- getAlbumList : alias legacy de getAlbumList2, sous "albumList" ------

$browser->get('/rest/getAlbumList.view?f=json&type=newest');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['albumList']['album'][0]['id'], 'al-2024-06', 'getAlbumList : meme donnees que getAlbumList2');
$t->ok(!isset($json['subsonic-response']['albumList2']), 'getAlbumList : pas de cle albumList2 residuelle');

// --- getRandomSongs --------------------------------------------------------

$browser->get('/rest/getRandomSongs.view?f=json&size=2');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is(count($json['subsonic-response']['randomSongs']['song']), 2, 'getRandomSongs : size respecte');

// size=10 depasse le nombre de morceaux visibles (5) : jamais plus que ce
// qui existe, et jamais un des quatre posts invisibles de Fantôme (4 a 7).
$browser->get('/rest/getRandomSongs.view?f=json&size=10');
$json = json_decode($browser->getResponse()->getContent(), true);
$songs = $json['subsonic-response']['randomSongs']['song'];
$t->is(count($songs), 5, 'getRandomSongs : jamais plus que le nombre de morceaux visibles');
$hasInvisible = false;
foreach ($songs as $song) {
  if (in_array($song['id'], array('4', '5', '6', '7'), true)) {
    $hasInvisible = true;
  }
}
$t->ok(!$hasInvisible, 'getRandomSongs : jamais un post invisible');

// --- getPlaylists -----------------------------------------------------------
//
// Fixtures (src/data/fixtures/subsonic.sql) : trois contributeurs ont au
// moins un post visible.
//   alice : posts 1, 3, 9 -> songCount 3, duration 245 + NULL + 90 = 335
//   bob   : post 2        -> songCount 1, duration 180
//   carol : post 8, sans ligne user_profile -> nom replie sur le username
// Alice possede aussi les posts 4 a 7, tous invisibles : ils ne doivent
// jamais compter dans son songCount/duration.

$browser->get('/rest/getPlaylists.view?f=json');
$json = json_decode($browser->getResponse()->getContent(), true);
$playlists = $json['subsonic-response']['playlists']['playlist'];
$t->is(count($playlists), 3, 'getPlaylists : trois playlists, une par contributeur visible');

$byOwner = [];
foreach ($playlists as $playlist) {
  $byOwner[$playlist['owner']] = $playlist;
}

$t->is($byOwner['alice']['songCount'], 3, 'getPlaylists : alice songCount = 3 (les posts 4 a 7 sont invisibles)');
$t->is($byOwner['alice']['duration'], 335, 'getPlaylists : alice duration = 335 (SUM ignore le NULL du post 3)');
$t->is($byOwner['bob']['songCount'], 1, 'getPlaylists : bob songCount = 1');
$t->is($byOwner['bob']['duration'], 180, 'getPlaylists : bob duration = 180');
$t->is($byOwner['carol']['songCount'], 1, 'getPlaylists : carol songCount = 1');
$t->is($byOwner['carol']['name'], 'La playlist de carol', 'getPlaylists : carol, sans profil, repli sur le username (COALESCE)');

foreach ($playlists as $playlist) {
  $t->ok(isset($playlist['songCount']), 'getPlaylists : songCount present sur '.$playlist['owner']);
}

// --- getPlaylist -------------------------------------------------------------

$aliceId = SubsonicId::forPlaylist('alice');
$browser->get('/rest/getPlaylist.view?f=json&id='.urlencode($aliceId));
$json = json_decode($browser->getResponse()->getContent(), true);
$playlist = $json['subsonic-response']['playlist'];
$t->is($playlist['songCount'], 3, 'getPlaylist : alice, songCount = 3');
$t->is(count($playlist['entry']), 3, 'getPlaylist : alice, trois morceaux dans entry');

$entryIds = array_map(function ($entry) { return $entry['id']; }, $playlist['entry']);
sort($entryIds);
$t->is($entryIds, ['1', '3', '9'], 'getPlaylist : alice, exactement les posts 1, 3, 9');

foreach (['4', '5', '6', '7'] as $invisibleId) {
  $t->ok(!in_array($invisibleId, $entryIds, true), 'getPlaylist : alice, jamais le post invisible '.$invisibleId);
}

// Les numeros de piste n'ont de sens que dans un album (un mois) : une
// playlist n'en porte pas.
$t->ok(!isset($playlist['entry'][0]['track']), 'getPlaylist : un morceau de la playlist ne porte pas d attribut track');

$carolId = SubsonicId::forPlaylist('carol');
$browser->get('/rest/getPlaylist.view?f=json&id='.urlencode($carolId));
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['playlist']['name'], 'La playlist de carol', 'getPlaylist : carol, nom replie sur le username');

$browser->get('/rest/getPlaylist.view?f=json&id=nimportequoi');
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 70, 'getPlaylist : id malforme -> 70');

// Aucun contributeur "personne" dans les fixtures : username invente, sans
// aucun post, pour verifier le cas "contributeur sans post visible".
$personneId = SubsonicId::forPlaylist('personne');
$browser->get('/rest/getPlaylist.view?f=json&id='.urlencode($personneId));
$json = json_decode($browser->getResponse()->getContent(), true);
$t->is($json['subsonic-response']['error']['code'], 70, 'getPlaylist : contributeur sans post visible -> 70');
