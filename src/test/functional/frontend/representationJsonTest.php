<?php

/**
 * Ce que porte la representation JSON d'un morceau.
 *
 * `formats-de-sortie` la decrit par six scenarios. Aucun n'etait exerce : le test
 * de contrat ne regarde que les cles de premier niveau, et rien ne verifiait ni le
 * detail des objets `track`, `contributor` et `links`, ni — surtout — le scenario
 * « Champs jamais exposes », qui est le seul garde-fou contre une fuite.
 *
 * Chaque assertion nomme le scenario qu'elle exerce.
 *
 * @see openspec/specs/formats-de-sortie/spec.md — Requirement: Representation JSON d'un morceau
 */

include dirname(__FILE__).'/../../bootstrap/functional.php';
require_once dirname(__FILE__).'/../../bootstrap/database.php';

$browser = new sfTestFunctional(new sfBrowser(), new lime_test(24));
$t = $browser->test();

$table = Doctrine_Core::getTable('Post');

// Un morceau qui a un voisin de chaque cote : le scenario « Liens de navigation »
// attend `post_previous` et `post_next` lorsque ces morceaux sont connus.
$morceau = null;

foreach ($table->createQuery('p')
  ->where('p.is_online = 1 AND p.publish_on <= NOW()')
  ->andWhere("p.slug IS NOT NULL AND p.slug != ''")
  ->orderBy('p.publish_on DESC')
  ->execute() as $candidat)
{
  $suivant = $table->getNextPost($candidat, array());
  $precedent = $table->getPreviousPost($candidat, array());

  if ($suivant && $suivant->slug && $precedent && $precedent->slug) {
    $morceau = $candidat;
    break;
  }
}

$t->ok($morceau, 'les fixtures portent un morceau ayant un voisin de chaque cote');

// Requete de chauffe, et il faut dire pourquoi elle existe.
//
// L'environnement de test declare `error_reporting: (E_ALL | E_STRICT) ^ E_NOTICE`,
// qui laisse passer E_DEPRECATED. Or la bibliotheque PHP-Markdown vendorisee
// emploie `$matches[2]{0}` (markdown.php:910), syntaxe depreciee en PHP 7.4. Le
// premier rendu Markdown d'un processus emet donc des avertissements, et ceux-ci
// atterrissent DANS le corps de la reponse — qui cesse d'etre du JSON analysable.
//
// Ce n'est pas propre a ce test : c'est ce qui rendait erratiques les sondes
// ecrites pendant les changes precedents, selon l'ordre des requetes. La production
// y est immunisee, huit echantillons successifs y renvoyant du JSON valide.
//
// Cette requete purge donc les avertissements sur une page HTML, ou ils sont sans
// consequence, pour que la suivante soit propre. C'est un contournement, il est
// nomme comme tel, et le defaut est consigne dans tasks.md.
$browser->get('/post/'.$morceau->slug);

$browser->get('/post/'.$morceau->slug.'?format=json');
$brut = $browser->getResponse()->getContent();
$corps = json_decode($brut, true);

$t->ok(is_array($corps) && isset($corps['posts'][0]), 'la reponse est un JSON analysable portant un morceau');

$objet = $corps['posts'][0];

// ------------------------------------------------ Identite et adresse

$t->diag('Requirement: Representation JSON d un morceau');

$t->is($objet['id'], $morceau->slug, 'Scenario Identite et adresse : id vaut l identifiant d URL du morceau');
$t->like($objet['href'], '#^https?://#', 'Scenario Identite et adresse : href est une adresse absolue');
$t->like($objet['href'], '#format=json#', 'Scenario Identite et adresse : href designe la representation JSON');
$t->like($objet['href'], '#'.preg_quote($morceau->slug, '#').'#', 'Scenario Identite et adresse : href designe ce morceau');

// ------------------------------------------------------ Corps du morceau

$t->ok(is_array($objet['body']), 'Scenario Corps du morceau : body est un objet');
$t->ok(array_key_exists('markdown', $objet['body']), 'Scenario Corps du morceau : body porte markdown');
$t->ok(array_key_exists('html', $objet['body']), 'Scenario Corps du morceau : body porte html');
$t->is($objet['body']['markdown'], $morceau->body, 'Scenario Corps du morceau : markdown est le texte source');
$t->isnt($objet['body']['html'], $objet['body']['markdown'], 'Scenario Corps du morceau : html en est le rendu, non une copie');

// ------------------------------------------------- Description de la piste

$t->ok(is_array($objet['track']), 'Scenario Description de la piste : track est un objet');
$t->is(
  array_diff(array('href', 'title', 'author', 'md5'), array_keys($objet['track'])),
  array(),
  'Scenario Description de la piste : track porte href, title, author et md5'
);
$t->is($objet['track']['title'], $morceau->track_title, 'Scenario Description de la piste : title est celui du morceau');
$t->is($objet['track']['author'], $morceau->track_author, 'Scenario Description de la piste : author est celui du morceau');

$bruts = array('track_title', 'track_author', 'track_md5', 'track_filename');
$presents = array_values(array_intersect($bruts, array_keys($objet)));
$t->is($presents, array(),
  'Scenario Description de la piste : les champs bruts n apparaissent pas a la racine');

// -------------------------------------------- Description du contributeur

$t->ok(is_array($objet['contributor']), 'Scenario Description du contributeur : contributor est un objet');
$t->is(
  array_diff(array('name', 'slug', 'href_website'), array_keys($objet['contributor'])),
  array(),
  'Scenario Description du contributeur : contributor porte name, slug et href_website'
);
$t->is($objet['contributor']['slug'], $morceau->getSfGuardUser()->username,
  'Scenario Description du contributeur : slug est l identifiant du contributeur');

// ----------------------------------------------------- Liens de navigation

$t->ok(is_array($objet['links']), 'Scenario Liens de navigation : links est un objet');
$t->like($objet['links']['contributor_playlist'], '#c='.preg_quote($morceau->getSfGuardUser()->username, '#').'#',
  'Scenario Liens de navigation : contributor_playlist designe la liste du contributeur');
$t->ok(!empty($objet['links']['avatar']), 'Scenario Liens de navigation : links porte avatar');
$t->is(
  array_diff(array('post_previous', 'post_next'), array_keys($objet['links'])),
  array(),
  'Scenario Liens de navigation : post_previous et post_next quand ces morceaux sont connus'
);

// ---------------------------------------------------- Champs jamais exposes

$t->diag('Le garde-fou contre une fuite');

// « les champs internes de mise en ligne, de revision et l'objet utilisateur
// complet sont absents ». Ce sont ces trois-la que la spec nomme.
$interdits = array('is_online', 'svn_revision', 'sfGuardUser');
$fuites = array_values(array_intersect($interdits, array_keys($objet)));

$t->is($fuites, array(),
  'Scenario Champs jamais exposes : ni mise en ligne, ni revision, ni objet utilisateur complet');
