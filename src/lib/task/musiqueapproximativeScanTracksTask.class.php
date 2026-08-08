<?php
/**
 * Tache de rattrapage pour track_duration / track_size.
 *
 * preSave() renseigne ces colonnes pour tout nouveau post, mais ne dit rien
 * des ~7000 posts existants (colonnes ajoutees apres coup, nulles tant que
 * non calculees) ni d'un fichier qui arrive apres la creation du post. Cette
 * tache parcourt les posts auxquels il manque l'une des deux colonnes (ou
 * tous les posts avec --force) et appelle Post::fillTrackMetadata() sur
 * chacun.
 *
 * Un fichier manquant est frequent sur l'archive de production : ce n'est
 * pas une erreur fatale, juste un post compte a part dans le resume.
 */
class musiqueapproximativeScanTracksTask extends sfBaseTask
{
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
      new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Nombre maximum de posts traites', null),
      new sfCommandOption('force', null, sfCommandOption::PARAMETER_NONE, 'Recalcule meme les posts deja renseignes'),
    ));

    $this->namespace        = 'musiqueapproximative';
    $this->name             = 'scan-tracks';
    $this->briefDescription = 'Renseigne track_duration et track_size a partir des fichiers audio';
    $this->detailedDescription = <<<EOF
La tache [musiqueapproximative:scan-tracks|INFO] parcourt les posts auxquels
il manque track_duration ou track_size, lit le fichier audio correspondant
via getID3, et enregistre la duree et la taille trouvees.

  [php symfony musiqueapproximative:scan-tracks|INFO]

Avec --force, recalcule aussi les posts deja entierement renseignes :

  [php symfony musiqueapproximative:scan-tracks --force|INFO]

Avec --limit, borne le nombre de posts traites (utile pour un test ou un
traitement par lots) :

  [php symfony musiqueapproximative:scan-tracks --limit=100|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'] ? $options['connection'] : null)->getConnection();

    $force = (bool) $options['force'];

    $q = Doctrine_Query::create()
      ->from('Post p')
      ->where("p.track_filename IS NOT NULL AND p.track_filename != ''");

    if (!$force) {
      $q->andWhere('p.track_duration IS NULL OR p.track_size IS NULL');
    }

    if ($options['limit']) {
      $q->limit((int) $options['limit']);
    }

    $posts = $q->execute();

    $total     = count($posts);
    $filled    = 0;
    $unchanged = 0;
    $missing   = 0;
    $errors    = 0;

    $this->logSection('scan-tracks', sprintf('%d post(s) a traiter', $total));

    foreach ($posts as $post) {
      $path = $post->getTrackPath();

      if (!is_readable($path)) {
        $missing++;
        // $size=500 : la troncature par defaut du formatter (~72
        // caracteres) coupe le nom de fichier au milieu, illisible dans un
        // log d'audit ou seul le chemin importe.
        $this->logSection('scan-tracks', sprintf('#%d [%s] fichier introuvable : %s', $post->id, $post->track_filename, $path), 500, 'ERROR');
        continue;
      }

      try {
        $changed = $post->fillTrackMetadata($force);
      } catch (Exception $e) {
        $errors++;
        $this->logSection('scan-tracks', sprintf('#%d [%s] erreur getID3 : %s', $post->id, $post->track_filename, $e->getMessage()), 500, 'ERROR');
        continue;
      }

      if ($changed) {
        $post->save();
        $filled++;
      } else {
        $unchanged++;
      }
    }

    $this->logSection('scan-tracks', sprintf(
      'Termine : %d renseigne(s), %d inchange(s), %d fichier(s) manquant(s), %d erreur(s) (sur %d)',
      $filled,
      $unchanged,
      $missing,
      $errors,
      $total
    ), 500);
  }
}
