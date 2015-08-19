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
    }
}
