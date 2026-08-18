<?php

/**
 * Bootstrap des tests qui ont besoin de la base.
 *
 * Ouvre la connexion « test » declaree dans databases.yml (base
 * musiqueapproximative_test) et recharge les fixtures, de sorte que chaque
 * test parte du meme etat connu.
 *
 * Usage dans un test :
 *
 *   require_once dirname(__FILE__).'/../../bootstrap/database.php';
 *
 * Les fixtures sont chargees en SQL et non via doctrine:data-load, qui est
 * inoperant sur ce projet — voir l'en-tete de data/fixtures/subsonic.sql.
 */

require_once dirname(__FILE__).'/../../config/ProjectConfiguration.class.php';

$configuration = ProjectConfiguration::getApplicationConfiguration('frontend', 'test', true);
$databaseManager = new sfDatabaseManager($configuration);

// lime
include $configuration->getSymfonyLibDir().'/vendor/lime/lime.php';

/**
 * Recharge les fixtures Subsonic dans la base de test.
 *
 * Garde-fou : refuse de s'executer si la connexion ne pointe pas sur une base
 * dont le nom se termine par « _test ». Les fixtures commencent par des
 * TRUNCATE, et la base de developpement contient un dump de production.
 */
function subsonic_load_fixtures()
{
  $connection = Doctrine_Manager::getInstance()->getCurrentConnection();
  $dbName = $connection->fetchOne('SELECT DATABASE()');

  if ('_test' !== substr($dbName, -5)) {
    throw new RuntimeException(sprintf(
      'Refus de charger les fixtures : la connexion pointe sur « %s », qui '
      ."n'est pas une base de test. Les fixtures commencent par des TRUNCATE.",
      $dbName
    ));
  }

  $sql = file_get_contents(dirname(__FILE__).'/../../data/fixtures/subsonic.sql');

  // Retirer les commentaires ligne par ligne AVANT de decouper : un bloc de
  // commentaires colle a l'instruction qui le suit ferait sauter celle-ci.
  $lines = array();
  foreach (explode("\n", $sql) as $line) {
    if (0 !== strpos(ltrim($line), '--')) {
      $lines[] = $line;
    }
  }

  // Le pilote n'accepte qu'une instruction par appel : on decoupe sur les
  // points-virgules en fin de ligne, ce qui suffit pour ce fichier (aucune
  // valeur n'en contient).
  foreach (preg_split('/;\s*\n/', implode("\n", $lines)) as $statement) {
    $statement = trim($statement, " \t\n\r;");

    if ('' === $statement) {
      continue;
    }

    $connection->exec($statement);
  }

  return $dbName;
}

/**
 * Reconstruit l'index de recherche plein texte.
 *
 * Les fixtures sont chargees en SQL brut, ce qui ne declenche pas l'indexation :
 * `post_index` reste vide, `PostTable::search()` ne remonte jamais rien, et tous
 * les scenarios de recherche de `catalogue-morceaux` sont invérifiables — sans
 * qu'aucun test n'echoue, puisqu'ils n'existaient pas.
 *
 * L'index est donc reconstruit apres chaque chargement, de sorte qu'un test de
 * recherche exerce le meme chemin qu'un visiteur.
 *
 * Aucun TRUNCATE n'est necessaire ici, et c'est verifie : `subsonic.sql` vide
 * `post_index` avant de recharger. Ca compte, parce que `batchUpdateIndex()` est
 * INCREMENTAL — sa requete ne selectionne que les morceaux absents de l'index.
 * Sur un index deja peuple, elle ne fait rien. En production, apres un changement
 * de collation, il faut donc vider d'abord : voir
 * docs/modules/ROOT/pages/migration-utf8mb4.adoc.
 */
function subsonic_build_search_index()
{
  $table = Doctrine_Core::getTable('Post');
  $table->batchUpdateIndex();

  return $table->getConnection()->fetchOne('SELECT COUNT(*) FROM post_index');
}

subsonic_load_fixtures();
subsonic_build_search_index();
