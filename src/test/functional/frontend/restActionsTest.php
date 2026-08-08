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
$t->is($albums[0]['id'], 'al-2024-05', 'getAlbumList2 : alphabeticalByName trie par ordre croissant');

$browser->get('/rest/getAlbumList2.view?f=json&type=byYear&size=500');
$json = json_decode($browser->getResponse()->getContent(), true);
$albums = $json['subsonic-response']['albumList2']['album'];
$t->is($albums[0]['id'], 'al-2024-05', 'getAlbumList2 : byYear trie par ordre croissant');

$browser->get('/rest/getAlbumList2.view?f=json&type=random&size=500');
$json = json_decode($browser->getResponse()->getContent(), true);
$ids = array();
foreach ($json['subsonic-response']['albumList2']['album'] as $album) {
  $ids[] = $album['id'];
}
sort($ids);
$t->is($ids, array('al-2024-05', 'al-2024-06'), 'getAlbumList2 : random retourne les memes mois, sans en perdre');

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
