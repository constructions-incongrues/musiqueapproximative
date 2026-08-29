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
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'Connexion Doctrine', 'doctrine'),
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
    // Une tache n'a pas de connexion ouverte : c'est a elle de l'etablir. Meme idiome que
    // `musiqueapproximativeRebuildMd5Task` et `musiqueapproximativeScanTracksTask`.
    $gestionnaire = new sfDatabaseManager($this->configuration);
    $gestionnaire->getDatabase($options['connection'] ? $options['connection'] : null)->getConnection();

    $table = Doctrine_Core::getTable('Post');

    $this->logSection('index', sprintf('avant : %s', $this->decrireIndex()));

    if ($options['mesurer-seulement'])
    {
      return 0;
    }

    $lot = max(1, (int) $options['lot']);
    $total = $table->createQuery('p')->count();
    $traites = 0;
    $dernierId = 0;

    $this->logSection('index', sprintf('%d morceau(x) a reindexer, par lots de %d', $total, $lot));

    // PAGINATION PAR CLE, ET AUCUNE HYDRATATION.
    //
    // Deux mesures, faites en se trompant deux fois :
    //
    // 1. Un `offset` croissant oblige la base a parcourir puis jeter tout ce qui precede :
    //    le cout monte avec l'avancement, et la reprise est impossible. Avancer par
    //    `id > dernier` donne un cout constant et une reprise triviale.
    //
    // 2. HYDRATER DES OBJETS EPUISE LA MEMOIRE. Sur 8 216 morceaux avec 128 Mo, la tache
    //    mourait a 1 000 — et `clear()` sur l'identity map, puis `free()` sur la
    //    collection, n'y ont rien change : la fuite est dans l'hydratation de Doctrine 1.
    //    Or `updateIndex()` prend UN TABLEAU. On n'a jamais eu besoin des objets.
    //    `HYDRATE_ARRAY` supprime le probleme au lieu de le contourner.
    $plugin = $table->getTemplate('Searchable')->getPlugin();

    while (true)
    {
      $morceaux = $table->createQuery('p')
        ->select('p.id, p.track_author, p.track_title, p.body')
        ->where('p.id > ?', $dernierId)
        ->orderBy('p.id ASC')
        ->limit($lot)
        ->setHydrationMode(Doctrine_Core::HYDRATE_ARRAY)
        ->execute();

      if (!$morceaux)
      {
        break;
      }

      foreach ($morceaux as $morceau)
      {
        $dernierId = $morceau['id'];

        // Reecrit les lignes d index de ce morceau. On ne passe pas par `save()` : cela
        // toucherait `updated_at` via Timestampable, alors qu'aucune colonne ne change.
        $plugin->updateIndex($morceau);
      }

      $traites += count($morceaux);
      $this->logSection('index', sprintf('%d / %d (dernier id %d)', $traites, $total, $dernierId));

      unset($morceaux);
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
