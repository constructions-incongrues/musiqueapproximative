<?php

/**
 * Configuration du plugin sfDesastrePlugin.
 *
 * @package    sfDesastrePlugin
 * @subpackage config
 * @author     Musique Approximative
 */
class sfDesastrePluginConfiguration extends sfPluginConfiguration
{
  /**
   * @see sfPluginConfiguration
   */
  public function initialize()
  {
    // Autoload des classes du plugin
    $this->dispatcher->connect('context.load_factories', array($this, 'listenToContextLoadFactoriesEvent'));
  }

  /**
   * Écoute l'événement de chargement des factories
   *
   * @param sfEvent $event
   */
  public function listenToContextLoadFactoriesEvent(sfEvent $event)
  {
    // Vous pouvez ajouter du code d'initialisation ici si nécessaire
  }
}
