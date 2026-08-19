<?php

/**
 * Un contributeur sans profil s'affiche sans erreur, quelle que soit la requete qui
 * l'a charge.
 *
 * Ce que ce fichier garde, et pourquoi il n'a pas pu etre ecrit plus tot :
 *
 * Le profil est facultatif — les 210 comptes de la base de production en sont
 * depourvus, et `carol` l'est dans les fixtures. La relation absente se presente sous
 * DEUX formes, selon la requete :
 *
 *   chargement paresseux  ->  objet UserProfile vide
 *   charge par jointure   ->  null
 *
 * La seconde date du `leftJoin('u.UserProfile pr')` ajoute a
 * PostTable::buildOnlinePostsQuery pour supprimer un N+1. Lire une propriete sur
 * `null` n'est qu'une NOTICE en PHP 7.4 et un AVERTISSEMENT en PHP 8 ; dans les deux
 * cas la valeur vaut null, la retombee sur `username` produit le meme affichage, et
 * rien ne casse. Le defaut est donc reste invisible a toute la suite pendant une
 * journee, jusqu'a une execution sous PHP 8.1 ou le navigateur de test de symfony 1
 * transforme l'avertissement en exception : 64 assertions sur 408.
 *
 * D'ou la methode employee ici, qui est le vrai objet de ce fichier :
 *
 *   ASSERTER SUR L'ABSENCE DE DIAGNOSTIC, PAS SEULEMENT SUR LA VALEUR RENDUE.
 *
 * Un test qui se contente de comparer le nom affiche passe sur le code fautif, des
 * deux cotes. En piegeant le gestionnaire d'erreurs, la notice de PHP 7.4 devient
 * elle aussi une defaillance : le defaut est rattrapable sur l'interpreteur du projet,
 * sans attendre une passe PHP 8. C'est plus utile que l'asymetrie que le packet de ce
 * change avait prevue.
 *
 * @see openspec/changes/archive/*-auditer-la-compatibilite-php-8/
 */

require_once dirname(__FILE__).'/../../bootstrap/database.php';

$t = new lime_test(8);

/**
 * Execute $callback en collectant tout diagnostic PHP emis pendant son execution.
 *
 * Retourne array($resultat, $diagnostics). Les notices comptent autant que les
 * avertissements : c'est precisement la difference entre PHP 7.4 et PHP 8 sur ce
 * defaut, et la gommer redonnerait un test aveugle sur l'interpreteur du projet.
 */
function executerEnCollectantLesDiagnostics($callback)
{
  $diagnostics = array();

  set_error_handler(function ($niveau, $message, $fichier, $ligne) use (&$diagnostics) {
    $diagnostics[] = sprintf('%s (%s ligne %d)', $message, basename($fichier), $ligne);

    return true;
  });

  try
  {
    $resultat = $callback();
  }
  catch (Exception $e)
  {
    restore_error_handler();

    throw $e;
  }

  restore_error_handler();

  return array($resultat, $diagnostics);
}

$table = Doctrine_Core::getTable('Post');
$conn = Doctrine_Manager::connection();

// ---------------------------------------------------------------------------
// De quoi mesurer : les fixtures portent bien un contributeur sans profil.
// ---------------------------------------------------------------------------

$carol = Doctrine_Core::getTable('sfGuardUser')->findOneByUsername('carol');

$t->ok($carol, 'les fixtures portent un contributeur sans profil (carol)');

// ---------------------------------------------------------------------------
// Forme 1 : la relation chargee paresseusement.
// ---------------------------------------------------------------------------

$conn->clear();
list($nom, $diagnostics) = executerEnCollectantLesDiagnostics(function () {
  return Doctrine_Core::getTable('sfGuardUser')->findOneByUsername('carol')->getDisplayName();
});

$t->is($nom, 'carol', 'chargement paresseux : le nom d affichage retombe sur l identifiant');
$t->is(
  $diagnostics,
  array(),
  sprintf('chargement paresseux : aucun diagnostic PHP emis%s', $diagnostics ? ' — '.implode(' | ', $diagnostics) : '')
);

// ---------------------------------------------------------------------------
// Forme 2 : la relation chargee par la jointure de buildOnlinePostsQuery.
//
// C'est la forme servie a chaque rendu de liste, et celle qui a casse.
// ---------------------------------------------------------------------------

$conn->clear();
list($resultat, $diagnostics) = executerEnCollectantLesDiagnostics(function () use ($table) {
  $noms = array();
  foreach ($table->buildOnlinePostsQuery(null, 20)->execute() as $post)
  {
    $noms[] = $post->getContributorDisplayName();
  }

  return $noms;
});

$t->ok(in_array('carol', $resultat, true), 'charge par jointure : le contributeur sans profil est nomme par son identifiant');
$t->is(
  $diagnostics,
  array(),
  sprintf('charge par jointure : aucun diagnostic PHP emis%s', $diagnostics ? ' — '.implode(' | ', $diagnostics) : '')
);

// ---------------------------------------------------------------------------
// Le site web, second site de lecture a travers la relation. Il a casse pour la
// meme raison, une passe apres le nom d'affichage.
// ---------------------------------------------------------------------------

$conn->clear();
list($adresses, $diagnostics) = executerEnCollectantLesDiagnostics(function () use ($table) {
  $adresses = array();
  foreach ($table->buildOnlinePostsQuery(null, 20)->execute() as $post)
  {
    $adresses[] = $post->getSfGuardUser()->getWebsiteUrl();
  }

  return $adresses;
});

$t->is(
  $diagnostics,
  array(),
  sprintf('site web du contributeur : aucun diagnostic PHP emis%s', $diagnostics ? ' — '.implode(' | ', $diagnostics) : '')
);
$t->ok(in_array(null, $adresses, true), 'site web du contributeur sans profil : null, et non une chaine fabriquee');

// ---------------------------------------------------------------------------
// Contre-epreuve. Sans elle, les assertions ci-dessus passeraient aussi si le
// collecteur de diagnostics ne collectait rien du tout — un gestionnaire mal pose
// rend tout le fichier complaisant.
// ---------------------------------------------------------------------------

list($ignore, $diagnostics) = executerEnCollectantLesDiagnostics(function () {
  $absent = null;

  return @$absent->propriete_qui_n_existe_pas;
});

$t->ok(
  count($diagnostics) > 0,
  'contre-epreuve : le collecteur voit bien une lecture sur null, donc son silence plus haut a une valeur'
);
