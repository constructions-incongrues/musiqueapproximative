<?php

/**
 * L'invariance des desastres.
 *
 * Le tirage se fait a la production de la page, et son resultat vaut pour toutes
 * les consultations servies depuis la meme representation mise en cache. C'est ce
 * qui distingue un desastre d'un effet aleatoire, et c'est ce que le cache peut
 * casser en silence : les deux bugs archives de cette zone n'etaient rien d'autre
 * que des ruptures d'invariance.
 *
 * Ce fichier n'a de sens que si l'environnement de test met en cache. Il le
 * verifie plutot que de le supposer.
 *
 * @see openspec/specs/desastres/spec.md — Requirements « Part d'aleatoire »,
 *      « Granularite du tirage », « Declenchement conditionnel »
 */

include dirname(__FILE__).'/../../bootstrap/functional.php';
require_once dirname(__FILE__).'/../../bootstrap/database.php';

$browser = new sfTestFunctional(new sfBrowser(), new lime_test(12));
$t = $browser->test();

$racine = sfConfig::get('sf_app_cache_dir').'/template';

/** Nombre d'entrees de cache ecrites a ce jour. */
$entrees = function () use ($racine) {
  return is_dir($racine) ? count(sfFinder::type('file')->name('*.cache')->in($racine)) : 0;
};

/** Ensemble des recettes servies, tel qu'un visiteur le recoit. */
$recettes = function ($html) {
  preg_match_all('#/desastres/([a-z0-9_]+)/#i', $html, $m);
  $noms = array_values(array_unique($m[1]));
  sort($noms);

  return $noms;
};

$nue    = '/post/sigur-ros-rock-roll';
$forcee = '/post/sigur-ros-rock-roll?danse';

// ------------------------------------------------------------------ garde-fou

$t->diag('Garde-fou : sans cache et sans recette, tout ce qui suit serait vrai a vide');

$t->ok(sfConfig::get('sf_cache'), 'le cache est actif dans l environnement de test');

$browser->get($forcee);
$forceesUn = $recettes($browser->getResponse()->getContent());
$t->ok(count($forceesUn) > 0, 'une adresse forcee par son declencheur porte bien des recettes');

// ------------------------------------- Consultations successives d une adresse

$t->diag('Consultations successives d une meme adresse');

$browser->get($forcee);
$t->is_deeply($recettes($browser->getResponse()->getContent()), $forceesUn, 'la seconde visite porte le meme ensemble de recettes');

$avantNue = $entrees();
$browser->get($nue);
$nueUn = $recettes($browser->getResponse()->getContent());
$t->ok($entrees() > $avantNue, 'l adresse nue est une entree de cache distincte de l adresse forcee');
$t->isnt($nueUn, $forceesUn, 'le declencheur change ce qui est servi : les parametres font partie de la cle');

$apresNue = $entrees();
$browser->get($nue);
$t->is_deeply($recettes($browser->getResponse()->getContent()), $nueUn, 'le tirage de l adresse nue est fige : meme ensemble a la visite suivante');
$t->is($entrees(), $apresNue, 'aucune entree nouvelle : la reponse vient bien du cache');

// -------------------------------------------- Deux visiteurs, meme adresse

$t->diag('Deux visiteurs sur la meme adresse');

$autre = new sfBrowser();
$avant = $entrees();
$autre->get($nue);
$t->is($autre->getResponse()->getStatusCode(), 200, 'un second navigateur obtient la page');
$t->is_deeply($recettes($autre->getResponse()->getContent()), $nueUn, 'il recoit les memes recettes, quelle que soit sa session');
$t->is($entrees(), $avant, 'et sans provoquer de production nouvelle');

// ---------------------------------------------- Une meme adresse dans le temps

$t->diag('Une meme adresse dans le temps');

// La duree de vie declaree est de 86 400 secondes : on ne l attend pas, on place
// le systeme dans l etat qui suit l expiration.
sfToolkit::clearDirectory($racine);
$t->is($entrees(), 0, 'cache vide : on est dans l etat qui suit une expiration');

$browser->get($nue);
$t->ok($entrees() > 0, 'la page est reproduite et une entree est reecrite');
