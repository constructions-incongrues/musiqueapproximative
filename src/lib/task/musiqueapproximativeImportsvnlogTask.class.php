<?php

class musiqueapproximativeImportsvnlogTask extends sfBaseTask
{
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('log_file', sfCommandArgument::REQUIRED, 'SVN XML log file'),
      new sfCommandArgument('source_dir', sfCommandArgument::REQUIRED, 'Directory containing source files'),
      new sfCommandArgument('target_dir', sfCommandArgument::REQUIRED, 'Directory where to put files once processed'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
    ));

    $this->namespace        = 'musiqueapproximative';
    $this->name             = 'import-svn-log';
    $this->briefDescription = '';
    $this->detailedDescription = <<<EOF
The [musiqueapproximative:import-svn-log|INFO] task does things.
Call it with:

  [php symfony musiqueapproximative:import-svn-log|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'] ? $options['connection'] : null)->getConnection();

    // Sanity checks
    if (!is_readable($arguments['log_file']))
    {
      throw new InvalidArgumentException(sprintf('File "%s" is not readable', $arguments['log_file']));
    }
    if (!is_readable($arguments['source_dir']))
    {
      throw new InvalidArgumentException(sprintf('Directory "%s" is not readable', $arguments['source_dir']));
    }
    if (!is_writable($arguments['target_dir']))
    {
      throw new InvalidArgumentException(sprintf('Directory "%s" is not readable', $arguments['target_dir']));
    }

    // Load XML
    if (!$xml = simplexml_load_file($arguments['log_file']))
    {
      throw new InvalidArgumentException(sprintf('Impossible to parse XML from "%s"', $arguments['log_file']));
    }

    // Parse svn log and create a useful data structure
    $revisions = array();
    foreach ($xml->logentry as $entry)
    {
      $revisions[(int)$entry['revision']] = array(
        'message' => (string)$entry->msg, 
        'date'    => (string)$entry->date,
        'author'  => (string)$entry->author ? (string)$entry->author : 'bertier'
      );
    } 

    // Aggregate informations for all files in source directory
    $iterator = new RecursiveDirectoryIterator($arguments['source_dir']);
    $seen_revisions =  array();
    foreach ($iterator as $file => $info)
    {
      $filename = (array)$file;
      $filename = $filename[0];
      if (Doctrine_Core::getTable('Post')->findByTrackMd5(md5($filename), Doctrine_Core::HYDRATE_ARRAY))
      {
        $this->logSection('notice', sprintf('Track "%s" was previously imported', basename($filename)));
        continue;
      }

      if (is_dir($filename))
      {
        continue;
      }

      $cmd = sprintf('/usr/bin/svn info --xml %s;', escapeshellarg($filename));
      $res = shell_exec($cmd);
      $this->logSection('info', sprintf('Importing "%s"', $filename));
      $xml = simplexml_load_string($res);
      if (!$xml)
      {
        $this->logSection('info', sprintf('Could not get versionning information from "%s"', $filename));
      }
      $revision = (string)$xml->entry->commit['revision']; 
      if ($revision == 520)
      {
        var_dump(basename($filename));
      }
      // Exceptions
      if (basename($filename) == "evariste - connais tu l'animal qui inventa le calcul intégral.mp3")
      {
        $revision = 152;
      }
      if (basename($filename) == "Fantome Fesse - gimme ghost.mp3")
      {
        $revision = 156;
      }
      if (isset($seen_revisions[$revision]))
      {
        throw new RuntimeException(sprintf('Conflicting files "%s" and "%s" for revision "%s"', basename($filename), $seen_revisions[$revision], $revision));
      }
      $seen_revisions[$revision] = basename($filename);
      if (!$revision)
      {
        continue;
      }
      
      // Create object in database
      $track_parts = explode('-', basename($filename, '.mp3'));
      $clean_filename = filter_var(basename($filename), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
      $post = new Post();
      $post->id = $revision;
      $post->body = $revisions[$revision]['message'];
      $post->created_at = $revisions[$revision]['date'];
      $post->publish_on = $revisions[$revision]['date'];
      $post->track_filename = $clean_filename;
      $post->track_author = $track_parts[0];
      $post->track_title = $track_parts[1];
      $post->track_md5 = md5($filename);
      $post->svn_revision = $revision;
      $post->is_online = true;
      $contributor_id = Doctrine_Query::create()
        ->select('u.id')
        ->from('sfGuardUser u')
        ->where('username = ?')
        ->execute(array($revisions[$revision]['author']), Doctrine_Core::HYDRATE_SINGLE_SCALAR);
      $post->contributor_id = $contributor_id;
      $post->save();

      // Copy track to destination directory
      copy($filename, sprintf('%s/%s', $arguments['target_dir'], $clean_filename));
    } 
  }
}
