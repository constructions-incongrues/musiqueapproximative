<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';
require_once sfConfig::get('sf_lib_dir').'/helper/SubsonicResponse.php';

$t = new lime_test(16);

$t->diag('SubsonicResponse::ok() et ::error()');

$ok = SubsonicResponse::ok(array('musicFolders' => array()));
$t->is($ok['status'], 'ok', '::ok() marque le statut');

$err = SubsonicResponse::error(70, 'Not found');
$t->is($err['status'], 'failed', '::error() marque le statut');
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
$t->ok(false === strpos($xml, 'duration'), 'XML : un attribut null est omis');

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
