<?php

/**
 * Construit le schema de la base de TEST, et rend un code de sortie qui dit la verite.
 *
 * POURQUOI CETTE TACHE EXISTE
 *
 * `doctrine:insert-sql` rend 1 sous PHP 8 alors qu'il a fait tout son travail. Le defaut
 * est dans une dependance vendue :
 *
 *   Doctrine_Export::exportClasses() ouvre une transaction, emet les CREATE TABLE puis les
 *   ALTER TABLE, et appelle commit(). Or les instructions DDL de MySQL VALIDENT
 *   IMPLICITEMENT : la transaction est deja refermee quand Doctrine la valide. PHP 7.4
 *   laissait PDO silencieux ; PHP 8 leve « There is no active transaction ».
 *
 *   Trace : Doctrine/Transaction.php:417 <- Export.php:1224 <- sfDoctrineInsertSqlTask:57
 *
 * On ne corrige pas la dependance : `src/vendor` est ignore par git et reinstalle a chaque
 * `composer install`, un correctif y serait perdu au premier deploiement.
 *
 * CE QUI DISTINGUE CETTE TACHE D'UN `|| true`
 *
 * Elle ne tolere QUE cette exception-la, et seulement apres avoir verifie que les tables
 * existent reellement. Avaler l'erreur sans verifier laisserait passer un schema
 * incomplet — c'est le contournement qui vivait dans le fichier d'integration continue, et
 * l'argument qui l'accompagnait tient toujours.
 *
 * Elle remplace ce contournement parce qu'il ne couvrait qu'un poste de travail sur deux :
 * `make test-init` lance la meme tache sans garde, et un developpeur voyait echouer ce que
 * l'integration continue voyait reussir.
 */
class musiqueapproximativeBuildTestSchemaTask extends sfBaseTask
{
  /**
   * Tables que le schema DOIT porter. La liste est explicite plutot que deduite de
   * `schema.yml` : c'est elle qui doit signaler qu'une table a disparu du schema.
   */
  const TABLES_ATTENDUES = array(
    'post',
    'post_index',
    'sf_guard_user',
    'sf_guard_user_profile',
    'user_profile',
  );

  /** Le seul message d'erreur que cette tache accepte de ne pas propager. */
  const EXCEPTION_TOLEREE = 'There is no active transaction';

  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environnement', 'test'),
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'Application', 'frontend'),
    ));

    $this->namespace = 'musiqueapproximative';
    $this->name = 'build-test-schema';
    $this->briefDescription = 'Construit le schema de test et verifie qu il est complet';
    $this->detailedDescription = <<<EOF
La tache [musiqueapproximative:build-test-schema|INFO] emet le schema dans la base de
test, puis VERIFIE que les tables attendues existent avant de rendre 0.

Elle enveloppe [doctrine:insert-sql|COMMENT], qui rend un code non nul sous PHP 8 alors
qu'il a cree toutes les tables — un defaut de la dependance vendue, pas du projet.

  [./symfony musiqueapproximative:build-test-schema --env=test|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $this->logSection('schema', sprintf('construction dans l environnement « %s »', $options['env']));

    $echec = null;

    try
    {
      $tache = new sfDoctrineInsertSqlTask($this->dispatcher, $this->formatter);
      $tache->setCommandApplication($this->commandApplication);
      $tache->setConfiguration($this->configuration);
      $tache->run(array(), array('--env='.$options['env']));
    }
    catch (Exception $exception)
    {
      $echec = $exception;
    }

    $manquantes = $this->tablesManquantes();

    if ($manquantes)
    {
      // Le schema est reellement incomplet : peu importe qu'une exception ait ete levee
      // ou non, c'est un echec et il nomme ce qui manque.
      throw new RuntimeException(sprintf(
        'schema incomplet, table(s) absente(s) : %s', implode(', ', $manquantes)
      ));
    }

    if (null === $echec)
    {
      $this->logSection('schema', 'complet');

      return 0;
    }

    if (false === strpos($echec->getMessage(), self::EXCEPTION_TOLEREE))
    {
      // Une autre erreur que celle qu'on connait. Les tables sont la, mais on ne sait pas
      // ce qui s'est passe : on ne l'avale pas.
      throw $echec;
    }

    $this->logSection('schema', sprintf(
      'complet — « %s » ignoree, defaut connu de Doctrine sous PHP 8',
      self::EXCEPTION_TOLEREE
    ));

    return 0;
  }

  /**
   * @return array les tables attendues qui ne repondent pas
   */
  protected function tablesManquantes()
  {
    $connexion = Doctrine_Manager::getInstance()->getCurrentConnection();
    $manquantes = array();

    foreach (self::TABLES_ATTENDUES as $table)
    {
      try
      {
        $connexion->execute(sprintf('SELECT 1 FROM %s LIMIT 0', $table));
      }
      catch (Exception $exception)
      {
        $manquantes[] = $table;
      }
    }

    return $manquantes;
  }
}
