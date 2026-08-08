<?php

include(dirname(__FILE__).'/../../bootstrap/functional.php');

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
