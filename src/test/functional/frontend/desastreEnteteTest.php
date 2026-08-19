<?php

/**
 * L'en-tete qui nomme le desastre applique, et son invariance.
 *
 * Pourquoi un fichier distinct de desastreInvarianceTest : celui-la garde l'invariance
 * de ce qui est SERVI DANS LE CORPS. L'en-tete est un canal separe, avec un mecanisme
 * de survie au cache qui lui est propre — sfViewCacheManager::setPageCache() serialise
 * la reponse entiere, en-tetes compris, et getPageCache() remplace l'objet reponse par
 * celui qu'il desérialise. Un en-tete pose au mauvais endroit serait donc jete sans que
 * le corps change d'un octet, et l'ancien fichier resterait vert.
 *
 * Ce que ce fichier garde :
 *
 *   1. L'en-tete nomme la recette appliquee.
 *   2. L'ABSENCE est declaree, pas omise. Un en-tete manquant ne distingue pas « aucun
 *      desastre » d'« en-tete casse » — or c'est precisement ce qu'on cherche a savoir.
 *   3. Il est invariant : deux consultations de la meme representation portent le meme
 *      en-tete, comme elles portent le meme desastre.
 *
 * @see openspec/specs/desastres/spec.md — Requirement « La reponse nomme le desastre applique »
 */

include dirname(__FILE__).'/../../bootstrap/functional.php';
require_once dirname(__FILE__).'/../../bootstrap/database.php';

$browser = new sfTestFunctional(new sfBrowser(), new lime_test(9));
$t = $browser->test();

$entete = function () use ($browser) {
  return $browser->getResponse()->getHttpHeader(sfDesastreManager::ENTETE);
};

$racine = sfConfig::get('sf_app_cache_dir').'/template';
$entrees = function () use ($racine) {
  return is_dir($racine) ? count(sfFinder::type('file')->name('*.cache')->in($racine)) : 0;
};

$nue    = '/post/sigur-ros-rock-roll';
$forcee = '/post/sigur-ros-rock-roll?danse';

// ---------------------------------------------------------------- garde-fou
//
// Sans cache, tout ce qui suit serait vrai a vide : « le meme en-tete deux fois »
// ne demontre rien si la page est reproduite a chaque appel.

$t->diag('Garde-fou');

sfToolkit::clearDirectory($racine);
$browser->get($nue);
$t->ok($entrees() > 0, 'l environnement de test met bien en cache, sans quoi ce fichier ne prouverait rien');

// ------------------------------------------------------- L en-tete est pose

$t->diag('L en-tete nomme ce qui a ete applique');

$t->ok(null !== $entete(), 'la reponse porte l en-tete '.sfDesastreManager::ENTETE);

$browser->get($forcee);
$forceeUn = $entete();

$t->like($forceeUn, '#danse#', 'un desastre force est nomme dans l en-tete');

// ----------------------------------------------- L absence est DECLAREE

$t->diag('L absence est declaree, non omise');

sfToolkit::clearDirectory($racine);
$browser->get($nue);
$nueUn = $entete();

$t->ok(null !== $nueUn && '' !== $nueUn, 'meme sans desastre, l en-tete est present : absent, il ne distinguerait pas « aucun » de « casse »');

if (sfDesastreManager::AUCUN === $nueUn) {
  $t->pass('aucune recette : l en-tete le declare explicitement ('.sfDesastreManager::AUCUN.')');
} else {
  // Les regles de cette adresse peuvent tirer : ce n est pas un echec, mais l en-tete
  // doit alors nommer une recette et non rester vide.
  $t->unlike($nueUn, '#^\s*$#', 'une recette a ete tiree : l en-tete la nomme ('.$nueUn.')');
}

// ------------------------------------------------------- L en-tete est invariant

$t->diag('L en-tete est invariant sur la representation en cache');

$apres = $entrees();
$browser->get($nue);
$t->is($entete(), $nueUn, 'seconde consultation : le meme en-tete');
$t->is($entrees(), $apres, 'et sans production nouvelle : la reponse vient du cache');

$autre = new sfBrowser();
$autre->get($nue);
$t->is(
  $autre->getResponse()->getHttpHeader(sfDesastreManager::ENTETE),
  $nueUn,
  'un second navigateur recoit le meme en-tete : l en-tete suit la representation, pas la session'
);

// --------------------------------- L en-tete suit la cle de cache, comme le corps

$t->diag('L en-tete suit la cle de cache');

$browser->get($forcee);
$t->isnt($entete(), $nueUn, 'adresse forcee et adresse nue portent des en-tetes distincts : les parametres font partie de la cle');
