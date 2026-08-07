<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';
require_once sfConfig::get('sf_lib_dir').'/helper/SubsonicResponse.php';

$t = new lime_test(36);

$t->diag('SubsonicResponse::ok() et ::error()');

$ok = SubsonicResponse::ok(array('musicFolders' => array()));
$t->is($ok['status'], 'ok', '::ok() marque le statut');

// Code passe en chaine : verifie le cast (int), pas seulement la valeur --
// is() compare en mode == et laisserait passer une chaine "70".
$err = SubsonicResponse::error('70', 'Not found');
$t->is($err['status'], 'failed', '::error() marque le statut');
$t->ok(is_int($err['error']['code']), '::error() caste le code en entier');
$t->is($err['error']['code'], 70, '::error() porte le code');

$t->diag('Rendu XML');

$xml = SubsonicResponse::render(SubsonicResponse::ok(), 'xml');
$doc = new SimpleXMLElement($xml);
$t->is((string) $doc['status'], 'ok', 'XML : status en attribut');
$t->is((string) $doc['version'], '1.16.1', 'XML : version annoncee');

$xml = SubsonicResponse::render(
  SubsonicResponse::ok(array('song' => array(
    array('id' => '1', 'title' => 'Rock & Roll'),
    array('id' => '2', 'title' => 'A < B'),
  ))),
  'xml'
);
$doc = new SimpleXMLElement($xml);
$t->is(count($doc->song), 2, 'XML : une collection repetable produit N elements');
$t->is((string) $doc->song[0]['title'], 'Rock & Roll', 'XML : esperluette echappee une seule fois');
$t->is((string) $doc->song[1]['title'], 'A < B', 'XML : chevron echappe une seule fois');
$t->ok(false === strpos($xml, '&amp;amp;'), 'XML : aucun double encodage');

$xml = SubsonicResponse::render(
  SubsonicResponse::ok(array('album' => array(array('id' => 'al-2024-06', 'duration' => null)))),
  'xml'
);
$doc = new SimpleXMLElement($xml);
// L'ancienne assertion (strpos($xml, 'duration') === false) passerait aussi
// si render() avait renvoye une chaine vide : on verifie en plus qu'un
// attribut voisin a bien survecu, preuve que le document n'est pas vide.
$t->is((string) $doc->album[0]['id'], 'al-2024-06', 'XML : un attribut voisin survit au rendu');
$t->ok(!isset($doc->album[0]['duration']), 'XML : un attribut null est omis');

$t->diag('Rendu de ::error()');

$xml = SubsonicResponse::render(SubsonicResponse::error(70, 'Not found'), 'xml');
$doc = new SimpleXMLElement($xml);
$t->is((string) $doc['status'], 'failed', 'XML erreur : status="failed" survit au re-merge de l\'enveloppe');
$t->is((string) $doc->error['code'], '70', 'XML erreur : <error> devient un element enfant, code en attribut');
$t->is((string) $doc->error['message'], 'Not found', 'XML erreur : message en attribut');

$t->diag('Rendu JSON');

$json = json_decode(SubsonicResponse::render(SubsonicResponse::ok(), 'json'), true);
$t->is($json['subsonic-response']['status'], 'ok', 'JSON : enveloppe subsonic-response');

$raw = SubsonicResponse::render(SubsonicResponse::ok(array('starred2' => array())), 'json');
$t->ok(false !== strpos($raw, '"starred2":{}'), 'JSON : un conteneur vide est {} et non []');

$raw = SubsonicResponse::render(SubsonicResponse::ok(array('song' => array())), 'json');
$t->ok(false !== strpos($raw, '"song":[]'), 'JSON : une collection repetable vide reste []');

$raw = SubsonicResponse::render(
  SubsonicResponse::ok(array('song' => array(array('id' => '1')))),
  'json'
);
$decoded = json_decode($raw, true);
$t->ok(is_array($decoded['subsonic-response']['song']), 'JSON : une collection a un element reste un tableau');

$t->diag('JSONP');

$raw = SubsonicResponse::render(SubsonicResponse::ok(), 'jsonp', 'cb');
$t->ok(0 === strpos($raw, 'cb('), 'JSONP : enveloppe par le callback');
$t->ok(substr($raw, -2) === ');', 'JSONP : parenthese fermante');

// L'ancien couple prefixe/suffixe laisserait passer cb(n'importe quoi);
$body = substr($raw, strlen('cb('), -2);
$t->ok(false !== json_decode($body), 'JSONP : le corps entre les parentheses est du JSON valide');

$t->diag('JSONP : callback hostile (XSS reflechie)');

// Un callback comme "alert(document.cookie)//" serait execute tel quel par
// le navigateur puisque contentType('jsonp') sert du text/javascript.
$raw = SubsonicResponse::render(SubsonicResponse::ok(), 'jsonp', 'alert(document.cookie)//');
$t->ok(0 === strpos($raw, 'callback('), 'JSONP : un callback non identifiant est remplace par "callback"');

$t->diag('Garde-fou : liste non declaree dans $repeatable');

try {
  SubsonicResponse::render(SubsonicResponse::ok(array(
    'shortcut' => array(array('id' => '1'), array('id' => '2')),
  )), 'xml');
  $t->fail('XML : une liste non declaree aurait du lever une exception');
} catch (InvalidArgumentException $e) {
  $t->pass('XML : une liste non declaree leve une exception plutot que produire du XML invalide');
}

try {
  SubsonicResponse::render(SubsonicResponse::ok(array(
    'shortcut' => array(array('id' => '1'), array('id' => '2')),
  )), 'json');
  $t->fail('JSON : une liste non declaree aurait du lever une exception');
} catch (InvalidArgumentException $e) {
  $t->pass('JSON : une liste non declaree leve une exception plutot que masquer les donnees dans {}');
}

$t->diag('Null a l\'interieur d\'une liste repetable (XML et JSON doivent converger)');

$xml = SubsonicResponse::render(SubsonicResponse::ok(array('song' => array(null, array('id' => '1')))), 'xml');
$doc = new SimpleXMLElement($xml);
$t->is(count($doc->song), 1, 'XML : un element non-tableau dans une liste est ignore (pas de fatal)');

$raw = SubsonicResponse::render(SubsonicResponse::ok(array('song' => array(null, array('id' => '1')))), 'json');
$decoded = json_decode($raw, true);
$t->is(count($decoded['subsonic-response']['song']), 1, 'JSON : meme regle, pour rester coherent avec le XML');

$t->diag('SubsonicResponse::contentType()');

$t->is(SubsonicResponse::contentType('json'), 'application/json; charset=utf-8', 'contentType() : json');
$t->is(SubsonicResponse::contentType('jsonp'), 'text/javascript; charset=utf-8', 'contentType() : jsonp');
$t->is(SubsonicResponse::contentType('xml'), 'text/xml; charset=utf-8', 'contentType() : xml (et tout le reste)');

$t->diag('Booleen : divergence XML (attribut string) / JSON (natif)');

$xml = SubsonicResponse::render(SubsonicResponse::ok(array('album' => array(array('id' => '1', 'isDir' => false)))), 'xml');
$doc = new SimpleXMLElement($xml);
$t->is((string) $doc->album[0]['isDir'], 'false', 'XML : un booleen devient la chaine "false" (attribut XML)');

$raw = SubsonicResponse::render(SubsonicResponse::ok(array('album' => array(array('id' => '1', 'isDir' => false)))), 'json');
$t->ok(false !== strpos($raw, '"isDir":false'), 'JSON : un booleen reste natif, pas la chaine "false"');

$t->diag('Repetable imbriquee dans une repetable (getIndexes-like)');

$nested = array('artists' => array('index' => array(
  array('name' => 'S', 'artist' => array(
    array('id' => 'ar-1'),
    array('id' => 'ar-2'),
  )),
)));

$xml = SubsonicResponse::render(SubsonicResponse::ok($nested), 'xml');
$doc = new SimpleXMLElement($xml);
$t->is((string) $doc->artists->index[0]['name'], 'S', 'XML : index imbrique porte son nom');
$t->ok(
  2 === count($doc->artists->index[0]->artist)
  && 'ar-1' === (string) $doc->artists->index[0]->artist[0]['id']
  && 'ar-2' === (string) $doc->artists->index[0]->artist[1]['id'],
  'XML : artist (repetable) imbrique dans index (repetable) produit N elements'
);

$raw = SubsonicResponse::render(SubsonicResponse::ok($nested), 'json');
$decoded = json_decode($raw, true);
$t->is_deeply(
  $decoded['subsonic-response']['artists'],
  array('index' => array(array(
    'name'   => 'S',
    'artist' => array(array('id' => 'ar-1'), array('id' => 'ar-2')),
  ))),
  'JSON : meme imbrication, le nom n\'est pas transmis a l\'element de la liste (Fix 6)'
);

$t->diag('Les cles reservees de l\'enveloppe ne sont pas ecrasables par le corps');

$xml = SubsonicResponse::render(SubsonicResponse::ok(array('version' => '9.9')), 'xml');
$doc = new SimpleXMLElement($xml);
$t->is((string) $doc['version'], '1.16.1', 'XML : "version" dans le corps n\'ecrase pas la version annoncee');
