<?php

require_once dirname(__FILE__).'/../../vendor/lexpress/symfony1/lib/autoload/sfCoreAutoload.class.php';
sfCoreAutoload::register();

class ProjectConfiguration extends sfProjectConfiguration
{
    public function setup()
    {
        $this->enablePlugins('sfDoctrinePlugin');
        $this->enablePlugins('sfDoctrineGuardPlugin');
        $this->enablePlugins('sfAdminDashPlugin');
        $this->enablePlugins('sfFeed2Plugin');
        $this->enablePlugins('sfJqueryReloadedPlugin');

        // For legacy PEAR packages
        set_include_path(get_include_path().PATH_SEPARATOR.__DIR__.'/../lib/vendor');

        if (strpos(__DIR__, 'vagrant') !== false) {
            $this->setCacheDir('/tmp/symfony_cache');
            $this->setLogDir('/tmp/symfony_logs');
        }
    }
}
