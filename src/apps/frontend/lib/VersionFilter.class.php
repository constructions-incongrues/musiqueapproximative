<?php

class VersionFilter extends sfFilter
{
  public function execute($filterChain)
  {
    if ($this->isFirstCall())
    {
      $version = 'dev';
      $versionFile = sfConfig::get('sf_root_dir').'/VERSION';

      if (file_exists($versionFile))
      {
        // Le fichier porte l'annotation que release-please cherche pour savoir
        // quoi remplacer : "1.10.0 # x-release-please-version". On ne retient
        // donc que le premier jeton, ce qui laisse un fichier réduit à la seule
        // version fonctionner à l'identique.
        if (preg_match('/^\s*(\S+)/', file_get_contents($versionFile), $matches))
        {
          $version = $matches[1];
        }
      }

      sfConfig::set('app_version', $version);
    }

    $filterChain->execute();
  }
}
