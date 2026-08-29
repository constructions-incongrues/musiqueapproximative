<?php

/**
 * Reconstruit `post_index`, la table qui porte la recherche plein texte.
 *
 * POURQUOI ELLE EXISTE
 *
 * `actAs: Searchable` n'alimente `post_index` QU'A L'ECRITURE d'un Post. Ajouter un champ a
 * la liste des champs indexes ne reecrit donc rien : les morceaux deja publies restent
 * indexes sans ce champ, et seuls les morceaux postes APRES la livraison en beneficient.
 *
 * C'est un demi-etat pire que l'etat precedent : la recherche rend alors des resultats qui
 * dependent de la date de publication, sans que rien ne le dise.
 *
 * REJOUABLE ET INTERRUPTIBLE
 *
 * La tache traite les morceaux par lots et n'a pas d'etat propre : chaque lot supprime les
 * lignes d'index du morceau puis les reecrit. L'arreter au milieu laisse une partie du
 * catalogue reindexee et le reste tel quel ; la relancer termine le travail. Deux executions
 * completes donnent le meme index.
 *
 * Le deploiement n'execute aucune migration — Plesk tire `main`, un point c'est tout. Cette
 * tache se lance donc a la main, APRES la livraison du code, jamais avant : un index
 * reconstruit avant que le champ soit declare indexable ne le contiendrait pas.
 *
 * Voir docs/modules/ROOT/pages/reconstruction-index-recherche.adoc.
 */
class musiqueapproximativeReconstruireIndexRechercheTask extends sfBaseTask
{
  const LOT_DEFAUT = 200;

  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environnement', 'prod'),
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'Application', 'frontend'),
      new sfCommandOption('lot', null, sfCommandOption::PARAMETER_REQUIRED, 'Nombre de morceaux par lot', self::LOT_DEFAUT),
      new sfCommandOption('mesurer-seulement', null, sfCommandOption::PARAMETER_NONE, 'Rendre la taille de l index sans rien reconstruire'),
    ));

    $this->namespace = 'musiqueapproximative';
    $this->name = 'reconstruire-index-recherche';
    $this->briefDescription = 'Reconstruit post_index pour tout le catalogue';
    $this->detailedDescription = <<<EOF
La tache [musiqueapproximative:reconstruire-index-recherche|INFO] reecrit `post_index`
pour TOUS les morceaux, ce que l ajout d un champ a `actAs: Searchable` ne fait pas.

Elle est rejouable et interruptible : l arreter puis la relancer aboutit au meme index.

  [./symfony musiqueapproximative:reconstruire-index-recherche --env=prod|INFO]
  [./symfony musiqueapproximative:reconstruire-index-recherche --mesurer-seulement|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $table = Doctrine_Core::getTable('Post');

    $this->logSection('index', sprintf('avant : %s', $this->decrireIndex()));

    if ($options['mesurer-seulement'])
    {
      return 0;
    }

    $lot = max(1, (int) $options['lot']);
    $total = $table->createQuery('p')->count();
    $traites = 0;

    $this->logSection('index', sprintf('%d morceau(x) a reindexer, par lots de %d', $total, $lot));

    while ($traites < $total)
    {
      $morceaux = $table->createQuery('p')
        ->orderBy('p.id ASC')
        ->limit($lot)
        ->offset($traites)
        ->execute();

      if (0 === count($morceaux))
      {
        break;
      }

      foreach ($morceaux as $morceau)
      {
        // `save()` declenche le comportement Searchable, qui reecrit les lignes d index de
        // ce morceau. On ne modifie aucune colonne : `Timestampable` ne touche `updated_at`
        // que si quelque chose a change.
        $morceau->getTable()->getTemplate('Searchable')->getPlugin()->updateIndex($morceau->toArray());
      }

      $traites += count($morceaux);
      $this->logSection('index', sprintf('%d / %d', $traites, $total));

      // Rendre la memoire entre les lots : l identity map de Doctrine garde tout sinon.
      Doctrine_Manager::getInstance()->getCurrentConnection()->clear();
    }

    $this->logSection('index', sprintf('apres : %s', $this->decrireIndex()));
    $this->logSection('index', 'reconstruction terminee');

    return 0;
  }

  /**
   * Taille de l index, telle qu'on veut pouvoir la comparer avant et apres.
   *
   * Le nombre de lignes ET les octets : le premier dit combien de mots distincts sont
   * indexes, le second ce que cela coute reellement a la base.
   */
  protected function decrireIndex()
  {
    $connexion = Doctrine_Manager::getInstance()->getCurrentConnection();

    try
    {
      $lignes = $connexion->fetchOne('SELECT COUNT(*) FROM post_index');
      $octets = $connexion->fetchAssoc(
        'SELECT data_length + index_length AS taille FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?', array('post_index')
      );
      $taille = isset($octets[0]['taille']) ? (int) $octets[0]['taille'] : 0;

      return sprintf('%s ligne(s), %.1f Mo', number_format($lignes, 0, ',', ' '), $taille / 1048576);
    }
    catch (Exception $exception)
    {
      return 'taille indisponible ('.$exception->getMessage().')';
    }
  }
}
