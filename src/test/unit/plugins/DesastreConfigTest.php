<?php

/**
 * Couvre les neuf scenarios de configuration de la spec « desastres ».
 *
 * Perimetre : le chargement de la configuration et la selection des regles.
 * Hors perimetre : le forcage par page servie, l'invariance sur le cache et les
 * scenarios statistiques, qui demandent chacun un dispositif different.
 *
 * @see openspec/specs/desastres/spec.md
 */

require_once dirname(__FILE__).'/../../bootstrap/unit.php';
require_once sfConfig::get('sf_plugins_dir').'/sfDesastrePlugin/lib/sfDesastreRuleEngine.class.php';
require_once sfConfig::get('sf_plugins_dir').'/sfDesastrePlugin/lib/sfDesastreManager.class.php';
require_once sfConfig::get('sf_plugins_dir').'/sfDesastrePlugin/lib/helper/DesastreHelper.php';

$t = new lime_test(26);

$fixtures = dirname(__FILE__).'/../../fixtures/desastres';

/**
 * Renvoie les noms des recettes retenues, dans l'ordre.
 */
function noms_recettes(array $recettes)
{
  $noms = array();
  foreach ($recettes as $recette)
  {
    $noms[] = $recette['name'];
  }

  return $noms;
}

// ---------------------------------------------------------------- Requirement: Resolution des imports

$t->diag('Resolution des imports');

$complet = new sfDesastreManager($fixtures.'/complet/desastres.yml');

$t->is_deeply($complet->getUnresolvedImports(), array(), 'tous les imports resolus : aucun import non resolu signale');

$config = $complet->getConfig();
$t->is(count($config['regles']), 2, 'les regles des deux fichiers importes sont chargees');
$t->is(count($config['recettes']), 2, 'les recettes des deux fichiers importes sont chargees');

$retenues = $complet->findRecettes(array('marqueur' => 'oui'));
$t->is_deeply(noms_recettes($retenues), array('premiere', 'seconde'), 'les regles des deux fichiers participent a l evaluation');

// L'ordre de declaration des imports fixe l'ordre d'evaluation : « regles-un »
// est declare en premier, sa recette sort donc en premier.
$t->is($config['regles'][0]['recettes'][0], 'premiere', 'l ordre de declaration des imports fixe l ordre des regles');
$t->is($config['regles'][1]['recettes'][0], 'seconde', 'la regle du second fichier vient apres celle du premier');

$t->diag('Un import ne se resout pas');

$casse = new sfDesastreManager($fixtures.'/import-casse/desastres.yml');

$t->is_deeply(
  $casse->getUnresolvedImports(),
  array('desastres/regles-absentes.yml'),
  'l import non resolu est nomme par son chemin declare'
);

$retenues = $casse->findRecettes(array('marqueur' => 'oui'));
$t->is_deeply(noms_recettes($retenues), array('premiere'), 'les regles des imports valides restent chargees et evaluees');

$t->diag('Configuration partiellement invalide : l ecart entre declare et charge est constatable');

$declares = 2; // desastres.yml declare deux imports de regles
$config = $casse->getConfig();
$t->is(count($config['regles']), 1, 'une seule des deux sources de regles a ete chargee');
$t->is(
  count($casse->getUnresolvedImports()),
  $declares - count($config['regles']),
  'le nombre d imports non resolus rend l ecart constatable, sans avoir a relire le fichier'
);

// ---------------------------------------------------------------- Requirement: Absence de configuration

$t->diag('Absence de configuration');

$absent = $fixtures.'/nexiste-pas/desastres.yml';
$t->ok(!file_exists($absent), 'le chemin de fixture absent l est bien');

// Le constructeur garde par file_exists() et retombe sur une configuration vide.
$vide = new sfDesastreManager($absent);
$t->is_deeply($vide->findRecettes(array('marqueur' => 'oui')), array(), 'aucune alteration n est retenue quand la configuration est absente');

// La garantie de la spec — « la page est servie normalement » — est tenue par le
// helper, qui garde avant de construire le manager. loadConfig(), lui, leve.
$leve = false;
try
{
  $manager = new sfDesastreManager();
  $manager->loadConfig($absent);
}
catch (Exception $e)
{
  $leve = true;
}
$t->ok($leve, 'loadConfig() leve sur un chemin absent : c est le helper qui garde, pas lui');

$dispatcher = new sfEventDispatcher();
$request = new sfWebRequest($dispatcher);
$response = new sfWebResponse($dispatcher);
$avant = $response->getContent();
apply_desastre($request, $response, array(), $absent);
$t->is($response->getContent(), $avant, 'apply_desastre() ne touche pas la reponse quand la configuration est absente');

// ---------------------------------------------------------------- Requirement: Unicite des regles

$t->diag('Unicite des regles');

$dupliquee = new sfDesastreManager($fixtures.'/regle-dupliquee/desastres.yml');
$config = $dupliquee->getConfig();

$t->is(count($config['regles']), 1, 'une regle identique declaree dans deux fichiers importes n est chargee qu une fois');
$t->is($config['regles'][0]['recettes'][0], 'premiere', 'la regle conservee est bien celle qui etait declaree');

$t->diag('Recette selectionnee plusieurs fois');

// Dans cette fixture, la regle dupliquee designe deux fois la recette
// « premiere » : deux regles satisfaites, une seule recette.
$noms = noms_recettes($dupliquee->findRecettes(array('premiere' => 1)));

$t->is_deeply($noms, array('premiere'), 'une recette designee par une regle dupliquee n est retenue qu une fois');

$t->diag('Deux regles differentes designant la meme recette');

// Cas nominal, distinct de la regle dupliquee : trois regles differentes, dont
// la premiere et la troisieme designent « premiere ».
$partagee = new sfDesastreManager($fixtures.'/recette-partagee/desastres.yml');
$t->is(count($partagee->getConfig()['regles']), 3, 'trois regles differentes restent trois regles : le dedoublonnage ne les confond pas');

$noms = noms_recettes($partagee->findRecettes(array('marqueur' => 'oui', 'autre' => 'oui')));
$t->is_deeply($noms, array('premiere', 'seconde'), 'la recette designee par deux regles differentes n est retenue qu une fois');
$t->is($noms[0], 'premiere', 'elle occupe le rang de la premiere regle qui la designe, pas celui de la derniere');

$partage = new sfDesastreManager($fixtures.'/sans-declencheur/desastres.yml');

// ---------------------------------------------------------------- Requirement: Couverture des declencheurs

$t->diag('Couverture des declencheurs');

$config = $partage->getConfig();

$sansTrigger = array();
foreach ($config['regles'] as $index => $regle)
{
  if (!isset($regle['trigger']))
  {
    $sansTrigger[] = $index;
  }
}
$t->is_deeply($sansTrigger, array(0), 'une regle sans parametre de declenchement est reperable dans la configuration chargee');

// Cette regle n est observable que par tirage : aucun parametre ne la force.
$retenues = $partage->findRecettes(array('premiere' => 1));
$t->is_deeply(noms_recettes($retenues), array(), 'aucun parametre ne force la regle sans declencheur, elle reste hors d atteinte');

$t->diag('Unicite des declencheurs');

$triggers = array();
foreach ($config['regles'] as $regle)
{
  if (isset($regle['trigger']))
  {
    $triggers[] = $regle['trigger'];
  }
}
$t->is(count($triggers) - count(array_unique($triggers)), 1, 'un declencheur est declare par deux regles : l ambiguite est constatable dans la configuration');

$retenues = $partage->findRecettes(array('partage' => 1));
$t->ok(count($retenues) >= 2, 'le declencheur partage force les deux regles a la fois');
$t->ok(in_array('premiere', noms_recettes($retenues)), 'la recette de la premiere regle forcee est retenue');
$t->ok(in_array('seconde', noms_recettes($retenues)), 'la recette de la seconde regle forcee est retenue');
