<?php

/**
 * La verification d'encodage, et ce qu'elle refuse de laisser croire.
 *
 * Cette route existe parce que `databases.yml` n'expose aucune valeur
 * observable : si sa ligne `encoding` disparaissait — un `make configure`
 * malheureux sur le serveur suffit, c'est arrive le 2026-08-18 — la connexion
 * se remettrait a convertir les caracteres, et personne ne le saurait avant le
 * prochain titre detruit.
 *
 * Ce que ce fichier garde tient en deux moities, et la seconde compte autant :
 *
 *   1. Le verdict est juste. `utf8mb4` -> conforme.
 *   2. Le verdict NE DIT PAS PLUS QU'IL NE SAIT. Il porte sa portee, ce qu'il
 *      ne prouve pas, le nombre de caracteres hors cp1252 reellement stockes
 *      et le nombre de titres deja alteres. Sans ces champs, un « conforme »
 *      quotidien vaudrait pour la preuve qu'il n'est pas — le voyant etait
 *      deja vert la veille du jour ou l'on a compte 61 titres mutiles.
 *
 * Les chiffres sont CALCULES et non ecrits en dur : le jour ou un titre
 * cyrillique survivra, le champ se remplira seul.
 */

include(dirname(__FILE__).'/../../bootstrap/functional.php');
require_once dirname(__FILE__).'/../../bootstrap/database.php';

$browser = new sfTestFunctional(new sfBrowser(), new lime_test(12));
$t = $browser->test();

$browser->get('/encodage');

$t->is($browser->getResponse()->getStatusCode(), 200, 'la verification repond');
$t->like(
  $browser->getResponse()->getContentType(),
  '#application/json#',
  'elle se sert en JSON, donc elle est interrogeable par une machine'
);

$corps = $browser->getResponse()->getContent();
$verification = json_decode($corps, true);

$t->ok(is_array($verification), 'la reponse s analyse');

// ---------------------------------------------------------------------------
// 1. Le verdict est juste.
// ---------------------------------------------------------------------------

$t->is($verification['verdict'], 'conforme', 'l environnement de test est en utf8mb4, le verdict le dit');
$t->is($verification['encodage_attendu'], 'utf8mb4', 'l encodage attendu est nomme');
$t->is($verification['encodage_constate'], 'utf8mb4', 'l encodage constate est nomme, pas seulement compare');

// ---------------------------------------------------------------------------
// 2. Le verdict ne dit pas plus qu'il ne sait.
//
// C'est la moitie qui existe a cause d'une critique : le voyant etait deja vert
// la veille du jour ou l'on a denombre les titres detruits. Un « conforme » nu
// occuperait la place d'une reparation qui n'a pas eu lieu.
// ---------------------------------------------------------------------------

$t->is(
  $verification['portee'],
  'connexion',
  'le verdict dit sur quoi il porte : la connexion, et non les titres'
);

$t->ok(
  isset($verification['ne_prouve_pas']) && '' !== $verification['ne_prouve_pas'],
  'la reponse porte ce qu elle ne prouve pas, en clair et non dans un fichier de plan'
);

$t->ok(
  array_key_exists('caracteres_hors_cp1252_stockes', $verification),
  'elle porte le nombre de caracteres hors cp1252 REELLEMENT stockes'
);

$t->ok(
  array_key_exists('dernier_stocke_hors_cp1252', $verification),
  'elle porte la date du dernier, vide tant que la preuve n a jamais ete faite'
);

$t->ok(
  array_key_exists('titres_alteres_en_base', $verification),
  'elle porte le nombre de titres deja alteres : un conforme affiche a cote ne se lit plus pareil'
);

// ---------------------------------------------------------------------------
// La verification ne modifie rien. Une route publique en lecture seule est ce
// qui lui permet d'etre publique.
// ---------------------------------------------------------------------------

$table = Doctrine_Core::getTable('Post');
$avant = $table->countOnlinePosts();
$browser->get('/encodage');
$t->is($table->countOnlinePosts(), $avant, 'la verification n ecrit rien : le catalogue est inchange');
