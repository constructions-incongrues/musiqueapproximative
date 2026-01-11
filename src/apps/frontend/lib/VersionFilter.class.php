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
        $version = trim(file_get_contents($versionFile));
      }
      
      sfConfig::set('app_version', $version);
    }

    $filterChain->execute();
  }
}
