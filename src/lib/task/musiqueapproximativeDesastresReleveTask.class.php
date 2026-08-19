<?php

/**
 * Denombre les tirages de desastres a partir du journal dedie.
 *
 * Ce que cette tache compte, et ce qu'elle refuse de laisser croire :
 *
 * Elle compte des TIRAGES, c'est-a-dire des productions de page. Le cache englobe la
 * mise en page et vaut vingt-quatre heures : une meme representation est servie a
 * toutes les consultations de la periode. Un tirage peut donc correspondre a une
 * consultation comme a dix mille, et le rapport entre les deux n'est pas connu.
 *
 * C'est pourquoi la portee est ecrite dans la sortie meme de la tache, et non dans une
 * page de documentation qu'on ne lira pas a cote du chiffre.
 *
 * Deux precautions de fond :
 *
 * 1. LES RECETTES JAMAIS TIREES APPARAISSENT A ZERO. La tache croise le journal avec la
 *    configuration au lieu de grouper ce qu'elle lit. Une recette absente du journal et
 *    une recette a zero se confondraient autrement — or c'est exactement la question
 *    posee : laquelle ne sort jamais ?
 *
 * 2. LES APPLICATIONS FORCEES SONT EXCLUES DU DECOMPTE. Le champ `trigger` d'une regle
 *    nomme un parametre d'URL qui applique la regle sans tirer : `?danse=1` applique
 *    `danse`. C'est l'outil par lequel on essaie un desastre a la main. Les compter
 *    gonflerait precisement les recettes sur lesquelles on travaille.
 */
class musiqueapproximativeDesastresReleveTask extends sfBaseTask
{
  /**
   * Application dont la configuration de desastres est lue.
   */
  protected $application = 'frontend';

  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'Application portant desastres.yml', 'frontend'),
      new sfCommandOption('journal', null, sfCommandOption::PARAMETER_REQUIRED, 'Chemin du journal a lire', null),
      new sfCommandOption('forces', null, sfCommandOption::PARAMETER_NONE, 'Denombrer les applications forcees au lieu des tirages'),
    ));

    $this->namespace        = 'musiqueapproximative';
    $this->name             = 'desastres-releve';
    $this->briefDescription = 'Denombre les tirages de desastres par recette.';
    $this->detailedDescription = <<<EOF
La tache [musiqueapproximative:desastres-releve|INFO] denombre, par recette, les tirages
enregistres dans le journal des desastres.

  [php symfony musiqueapproximative:desastres-releve|INFO]

Elle compte des TIRAGES — des productions de page — et non des consultations. Le cache
sert la meme representation pendant vingt-quatre heures : un tirage peut valoir une
consultation comme dix mille.

Les recettes declarees mais jamais tirees apparaissent a zero, ce qui est le principal
interet du releve.

Les applications forcees par un parametre d'URL (`?danse=1`) sont exclues du decompte,
car ce sont des essais manuels. [--forces|COMMENT] les denombre a la place.
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $mode = $options['forces'] ? sfDesastreManager::MODE_FORCE : sfDesastreManager::MODE_TIRE;
    $this->application = $options['application'];

    $journal = $options['journal']
      ? $options['journal']
      : sfConfig::get('sf_log_dir').DIRECTORY_SEPARATOR.sfDesastreManager::JOURNAL;

    // Les recettes DECLAREES d'abord, toutes a zero. C'est ce croisement qui fait
    // apparaitre celles qui ne sortent jamais.
    $decompte = array();
    foreach ($this->recettesDeclarees() as $nom) {
      $decompte[$nom] = 0;
    }

    $productions = 0;
    $sansRecette = 0;
    $inconnues = array();

    if (is_readable($journal)) {
      foreach (file($journal, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $ligne) {
        $entree = json_decode($ligne, true);

        if (!is_array($entree) || !isset($entree['recettes'])) {
          continue;
        }

        $productions++;

        if (!$entree['recettes']) {
          $sansRecette++;

          continue;
        }

        foreach ($entree['recettes'] as $r) {
          if (!isset($r['recette']) || (isset($r['mode']) && $r['mode'] !== $mode)) {
            continue;
          }

          $nom = $r['recette'];

          if (!array_key_exists($nom, $decompte)) {
            // Une recette journalisee mais plus declaree : elle a ete retiree de la
            // configuration depuis. La signaler plutot que la fondre dans le total.
            $inconnues[$nom] = isset($inconnues[$nom]) ? $inconnues[$nom] + 1 : 1;

            continue;
          }

          $decompte[$nom]++;
        }
      }
    } else {
      $this->logSection('desastres', sprintf('journal introuvable ou illisible : %s', $journal), null, 'ERROR');
    }

    arsort($decompte);

    $this->log('');
    $this->log(sprintf('  Journal : %s', $journal));
    $this->log(sprintf('  %d production(s) de page relevee(s), dont %d sans aucune recette.', $productions, $sansRecette));
    $this->log('');
    $this->log(sprintf('  %-38s %s', 'RECETTE', sfDesastreManager::MODE_FORCE === $mode ? 'APPLICATIONS FORCEES' : 'TIRAGES'));
    $this->log(sprintf('  %s', str_repeat('-', 60)));

    $jamais = 0;
    foreach ($decompte as $nom => $n) {
      if (0 === $n) {
        $jamais++;
      }

      $this->log(sprintf('  %-38s %d', $nom, $n));
    }

    $this->log('');
    $this->log(sprintf('  %d recette(s) declaree(s), dont %d jamais %s sur la periode du journal.',
      count($decompte), $jamais, sfDesastreManager::MODE_FORCE === $mode ? 'forcee(s)' : 'tiree(s)'));

    foreach ($inconnues as $nom => $n) {
      $this->logSection('desastres', sprintf('%s : %d releve(s), mais la recette n\'est plus declaree', $nom, $n), null, 'ERROR');
    }

    // La portee est ecrite ICI, a cote du chiffre. Dans une page de documentation, elle
    // ne serait pas lue au moment ou le chiffre est interprete.
    $this->log('');
    $this->log('  Ces nombres sont des TIRAGES, pas des visiteurs.');
    $this->log('  Le cache sert la meme representation pendant 24 h : un tirage peut valoir');
    $this->log('  une consultation comme dix mille, et le rapport n\'est pas connu.');
    $this->log('  Les journaux tournent : la periode couverte n\'est pas l\'histoire du site.');
    $this->log('');
  }

  /**
   * Retourne les noms des recettes declarees dans la configuration.
   *
   * @return array
   */
  protected function recettesDeclarees()
  {
    // Le manager construit sans chemin part sur une configuration VIDE — il ne devine
    // pas l'emplacement. C'est le meme chemin que celui employe par le helper.
    // `sf_app_config_dir` n'est resolu que dans le contexte d'une application ; une
    // tache n'en a pas par defaut, d'ou le chemin reconstruit depuis la racine.
    $chemin = sfConfig::get('sf_root_dir').sprintf('/apps/%s/config/desastres.yml', $this->application);

    if (!file_exists($chemin)) {
      $this->logSection('desastres', sprintf('configuration introuvable : %s', $chemin), null, 'ERROR');

      return array();
    }

    $manager = new sfDesastreManager($chemin);
    $config = $manager->getConfig();

    return isset($config['recettes']) && is_array($config['recettes'])
      ? array_keys($config['recettes'])
      : array();
  }
}
