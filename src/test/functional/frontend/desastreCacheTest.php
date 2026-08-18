<?php

/**
 * Ce que voit le DEUXIEME visiteur.
 *
 * L'environnement de test met en cache. Une reponse servie depuis le cache doit
 * etre aussi complete que celle qui l'a produite — ressources du desastre ET
 * options. C'est precisement ce qui manquait avant
 * `2026-08-07-reparer-injection-des-options` : `sfDesastreFilter` injectait ses
 * options apres que `sfCacheFilter` avait ecrit l'entree, si bien qu'un visiteur
 * sur des milliers recevait un desastre configure, et tous les autres un desastre
 * muet.
 *
 * @see openspec/specs/desastres/spec.md — Requirement: Application d'une recette
 */

include dirname(__FILE__).'/../../bootstrap/functional.php';
require_once dirname(__FILE__).'/../../bootstrap/database.php';

$browser = new sfTestFunctional(new sfBrowser(), new lime_test(8));
$t = $browser->test();

$url = '/post/sigur-ros-rock-roll?bleu';

$t->diag('Le cache est bien actif dans l environnement de test');

$t->ok(sfConfig::get('sf_cache'), 'sf_cache est vrai : sans cela ce fichier ne demontre rien');

$racine = sfConfig::get('sf_app_cache_dir').'/template';
$entrees = function () use ($racine) {
  if (!is_dir($racine)) {
    return array();
  }

  return sfFinder::type('file')->name('*.cache')->in($racine);
};

$t->is(count($entrees()), 0, 'le bootstrap a vide le cache : chaque fichier de test part au propre');

$t->diag('Premiere visite : la page est produite');

$browser->get($url);
$t->is($browser->getResponse()->getStatusCode(), 200, 'la page repond');

$premier = $browser->getResponse()->getContent();
$t->ok(false !== strpos($premier, 'DesastreOptions'), 'la page produite porte les options du desastre');

$t->ok(count($entrees()) > 0, 'la reponse a bien ete mise en cache');

$t->diag('Deuxieme visite : la page vient du cache');

$browser->get($url);
$t->is($browser->getResponse()->getStatusCode(), 200, 'la page repond encore');

$second = $browser->getResponse()->getContent();
$t->ok(
  false !== strpos($second, 'DesastreOptions'),
  'la reponse servie depuis le cache porte elle aussi les options : c est la regression de reparer-injection-des-options'
);

// Les recettes retenues sont figees par le tirage de la premiere production ;
// seul ce qui se rejoue dans le navigateur peut differer.
preg_match_all('#/desastres/([a-z0-9_]+)/#i', $premier, $m1);
preg_match_all('#/desastres/([a-z0-9_]+)/#i', $second, $m2);
$t->is_deeply(
  array_values(array_unique($m2[1])),
  array_values(array_unique($m1[1])),
  'les deux visites portent les memes recettes, le tirage etant fige par le cache'
);
