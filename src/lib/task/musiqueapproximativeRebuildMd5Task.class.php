<?php
class musiqueapproximativeRebuildMd5Task extends sfBaseTask
{
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
    ));

    $this->namespace        = 'musiqueapproximative';
    $this->name             = 'rebuild-md5';
    $this->briefDescription = '';
    $this->detailedDescription = <<<EOF
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'] ? $options['connection'] : null)->getConnection();

    $q = Doctrine_Query::create()
      ->select(PostTable::FIELDS_BASIC)
      ->from('Post p')
      ->leftJoin('p.sfGuardUser u on p.contributor_id = u.id')
      ->where('p.is_online = 1 and p.track_md5 = ""');

      $posts = $q->execute();
      var_dump(count($posts));
      foreach ($posts as $post) {
        var_dump($post->getTrackTitle());
        $filename = sprintf('%s/../../web/tracks/%s', __DIR__, $post->getTrackFilename());
        $post->setTrackMd5(md5(file_get_contents($filename)));
        $post->save();
      }
  }
}
