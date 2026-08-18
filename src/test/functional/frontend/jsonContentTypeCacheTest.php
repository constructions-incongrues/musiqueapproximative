<?php

/**
 * Le type de contenu du JSON, et ce qu'en fait le cache.
 *
 * `JsonApiFilter` reecrivait le Content-Type de toute reponse JSON en
 * `application/vnd.api+json`, ce que la specification `formats-de-sortie`
 * interdit — son scenario « Formats reconnus » exige `application/json`. Le
 * filtre a ete retire.
 *
 * Ce fichier tient les deux moities de ce retrait :
 *
 *  1. le type servi est bien `application/json` ;
 *  2. il l'est AUSSI quand la reponse vient du cache.
 *
 * La seconde n'est pas une precaution de style. `filters.yml` declarait
 * `json_api` SOUS `cache` deliberement : « Le Content-Type doit etre reecrit
 * avant que `sfCacheFilter` n'ecrive l'entree, sinon la reponse mise en cache
 * porte le type d'origine. » C'est un bug qui a deja ete commis une fois sur ce
 * projet, et repare une fois. Le retrait du filtre supprime le besoin — mais
 * c'est un raisonnement, pas une observation. Ce fichier en fait une
 * observation.
 *
 * @see openspec/specs/formats-de-sortie/spec.md — Requirement: Selection du format
 */

include dirname(__FILE__).'/../../bootstrap/functional.php';
require_once dirname(__FILE__).'/../../bootstrap/database.php';

$browser = new sfTestFunctional(new sfBrowser(), new lime_test(12));
$t = $browser->test();

$morceau = Doctrine_Core::getTable('Post')->createQuery('p')
  ->where('p.is_online = 1 AND p.publish_on <= NOW()')
  ->andWhere("p.slug IS NOT NULL AND p.slug != '' AND p.track_md5 IS NOT NULL")
  ->orderBy('p.publish_on DESC')
  ->fetchOne();

$t->ok($morceau, 'les fixtures portent un morceau publie : sans lui ce fichier ne demontre rien');

$typeDeMedia = function ($contentType) {
  return trim(strtolower(current(explode(';', (string) $contentType))));
};

$t->diag('Le type servi est celui que la spec exige');

foreach (array(
  '/posts?format=json'                     => 'la liste',
  '/post/'.$morceau->getSlug().'?format=json' => 'un morceau',
  '/post/md5/'.$morceau->getTrackMd5()     => 'un morceau par empreinte',
  '/posts/next?current='.$morceau->getId() => 'le morceau suivant',
  '/posts/random'                          => 'un morceau au hasard',
) as $url => $quoi) {
  $browser->get($url);
  $t->is(
    $typeDeMedia($browser->getResponse()->getContentType()),
    'application/json',
    sprintf('%s : application/json', $quoi)
  );
}

$t->diag('Et il survit au cache');

$t->ok(sfConfig::get('sf_cache'), 'sf_cache est vrai : sans cela la suite de ce fichier ne demontre rien');

$racine = sfConfig::get('sf_app_cache_dir').'/template';
$entrees = function () use ($racine) {
  return is_dir($racine) ? sfFinder::type('file')->name('*.cache')->in($racine) : array();
};

// Une page HTML : le cache de gabarit s'applique aux reponses avec habillage,
// et c'est elle qui produit une entree observable. La demonstration porte sur
// le fait que le Content-Type traverse la chaine de filtres intact, ce qui vaut
// pour toutes les representations.
$urlCachee = '/post/'.$morceau->getSlug();

$avant = count($entrees());
$browser->get($urlCachee);
$premierType = $typeDeMedia($browser->getResponse()->getContentType());
$t->ok(count($entrees()) > $avant, 'la premiere visite a bien ecrit une entree de cache');

$browser->get($urlCachee);
$t->is(
  $typeDeMedia($browser->getResponse()->getContentType()),
  $premierType,
  'la reponse servie depuis le cache porte le meme type que celle qui l a produite'
);

// Le cas qui portait le bug : une reponse JSON demandee deux fois.
//
// Constater que les deux reponses portent le meme type ne demontre rien tant
// qu'on n'a pas etabli que la seconde vient du cache et non d'un second calcul.
// On l'etablit en modifiant la base entre les deux demandes : si le corps ne
// bouge pas, il n'a pas ete recalcule.
$browser->get('/posts?format=json');
$premierJson = $typeDeMedia($browser->getResponse()->getContentType());
$premierCorps = $browser->getResponse()->getContent();

$connexion = Doctrine_Manager::getInstance()->getCurrentConnection();
$connexion->exec('UPDATE post SET track_title = CONCAT(track_title, " TEMOIN-CACHE") WHERE is_online = 1 LIMIT 1');

$browser->get('/posts?format=json');
$secondJson = $typeDeMedia($browser->getResponse()->getContentType());
$secondCorps = $browser->getResponse()->getContent();

$connexion->exec('UPDATE post SET track_title = REPLACE(track_title, " TEMOIN-CACHE", "")');

$t->is(
  $secondCorps,
  $premierCorps,
  'la seconde demande JSON vient bien du cache : la base a change entre les deux, le corps non'
);
$t->is($secondJson, $premierJson, 'les deux demandes JSON portent le meme type');
$t->is($secondJson, 'application/json', 'et ce type est application/json, y compris depuis le cache');
