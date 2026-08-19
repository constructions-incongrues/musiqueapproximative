<?php

/**
 * Les assets de desastre portent une empreinte de version dans leur adresse.
 *
 * POURQUOI CE TEST EXISTE
 *
 * Le 2026-08-19, Cloudflare a servi pendant des heures un `bande-usee.js` anterieur a
 * deux correctifs deja en ligne sur l'origine : rien ne previent l'edge d'un
 * deploiement, et l'adresse du fichier, elle, ne bougeait pas. L'empreinte fait changer
 * l'adresse a chaque modification, ce qui force le cache a redescendre le fichier.
 *
 * Ce que ce test verifie, c'est l'adresse EMISE PAR LA PAGE. Il ne dit rien de ce que
 * Cloudflare sert : ca se constate en ligne, sur l'URL nue.
 *
 * @see src/plugins/sfDesastrePlugin/lib/sfDesastreManager.class.php
 */

require_once dirname(__FILE__).'/../../bootstrap/unit.php';
require_once sfConfig::get('sf_plugins_dir').'/sfDesastrePlugin/lib/sfDesastreRuleEngine.class.php';
require_once sfConfig::get('sf_plugins_dir').'/sfDesastrePlugin/lib/sfDesastreManager.class.php';

$t = new lime_test(7);

// Un desastre bidon sur disque, avec une date de modification connue.
$racine = sys_get_temp_dir().'/desastres-empreinte-'.getmypid();
$horodatage = 1200000000; // 2008-01-10, une date qu'aucun checkout ne produira par hasard

foreach (array('javascript' => 'bidon.js', 'stylesheets' => 'bidon.css') as $type => $nom)
{
  mkdir($racine.'/bidon/'.$type, 0777, true);
  file_put_contents($racine.'/bidon/'.$type.'/'.$nom, '/* bidon */');
  touch($racine.'/bidon/'.$type.'/'.$nom, $horodatage);
}

/**
 * Applique une recette et rend la reponse, pour lire ce qu'elle a accroche.
 */
function rendre_reponse(array $recette, $racine)
{
  $reponse = new sfWebResponse(new sfEventDispatcher());
  $manager = new sfDesastreManager();
  $manager->applyRecettesToResponse($reponse, array($recette), '/desastres', $racine);

  return $reponse;
}

// --------------------------------------------------- assets decouverts sur le disque

$reponse = rendre_reponse(array('desastre' => 'bidon'), $racine);
$js = array_keys($reponse->getJavascripts());
$css = array_keys($reponse->getStylesheets());

$t->is($js, array('/desastres/bidon/javascript/bidon.js?v='.$horodatage),
  'le javascript du desastre porte la date du fichier');
$t->is($css, array('/desastres/bidon/stylesheets/bidon.css?v='.$horodatage),
  'la feuille de style du desastre porte la date du fichier');

// L'empreinte SUIT le fichier : c'est tout l'interet, sinon elle ne sert a rien.
touch($racine.'/bidon/javascript/bidon.js', $horodatage + 86400);
$js = array_keys(rendre_reponse(array('desastre' => 'bidon'), $racine)->getJavascripts());
$t->is($js, array('/desastres/bidon/javascript/bidon.js?v='.($horodatage + 86400)),
  'modifier le fichier change l\'adresse emise');

// ------------------------------------------------------- scripts: declares en recette

$reponse = rendre_reponse(array(
  'desastre' => 'bidon',
  'scripts'  => array('/frontend/assets/javascripts/jquery.js'),
), $racine);
$js = array_keys($reponse->getJavascripts());

$attendu = '/frontend/assets/javascripts/jquery.js?v='
  .filemtime(sfConfig::get('sf_web_dir').'/frontend/assets/javascripts/jquery.js');
$t->ok(in_array($attendu, $js), 'un script: de recette porte la date de son fichier');

// ------------------------------------------------------------------- cas degrades
//
// Un desastre est un ornement. Aucun de ces cas ne doit faire echouer la page : ils
// rendent l'adresse nue, qui reste servie, meme si le cache la garde trop longtemps.

$js = array_keys(rendre_reponse(array(
  'desastre' => 'bidon',
  'scripts'  => array('/n-existe-pas/nulle-part.js'),
), $racine)->getJavascripts());
$t->ok(in_array('/n-existe-pas/nulle-part.js', $js),
  'un script: introuvable est emis sans empreinte, pas en erreur');

$js = array_keys(rendre_reponse(array(
  'desastre' => 'bidon',
  'scripts'  => array('https://exemple.test/tiers.js'),
), $racine)->getJavascripts());
$t->ok(in_array('https://exemple.test/tiers.js', $js),
  'une URL absolue vers un tiers est emise telle quelle');

$js = array_keys(rendre_reponse(array('desastre' => 'inconnu'), $racine)->getJavascripts());
$t->is($js, array(), 'un desastre sans repertoire n\'accroche rien');

// Menage
foreach (array('javascript/bidon.js', 'stylesheets/bidon.css') as $fichier)
{
  unlink($racine.'/bidon/'.$fichier);
}
foreach (array('/bidon/javascript', '/bidon/stylesheets', '/bidon', '') as $repertoire)
{
  rmdir($racine.$repertoire);
}
