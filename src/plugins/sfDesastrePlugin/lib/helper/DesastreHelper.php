<?php

/**
 * Helper pour faciliter l'utilisation des désastres dans les templates.
 *
 * @package    sfDesastrePlugin
 * @subpackage helper
 * @author     Musique Approximative
 */

/**
 * Applique automatiquement les desastres bases sur les parametres de la requete
 *
 * Cette fonction simplifie l'utilisation du plugin en une seule ligne.
 * Elle charge la configuration, evalue les regles et applique les recettes automatiquement.
 *
 * @param sfWebRequest $request La requete Symfony
 * @param sfWebResponse $response La reponse Symfony
 * @param array $extraParams Parametres supplementaires pour l'evaluation des regles (optionnel)
 * @param string $configPath Chemin vers le fichier de configuration (optionnel, par defaut: apps/APP/config/desastres.yml)
 * @param sfContext $context Contexte Symfony (optionnel, sera recupere automatiquement si non fourni)
 */
function apply_desastre(sfWebRequest $request, sfWebResponse $response, array $extraParams = array(), $configPath = null, sfContext $context = null)
{
  if ($configPath === null) {
    $configPath = sfConfig::get('sf_app_config_dir') . '/desastres.yml';
  }

  if (!file_exists($configPath)) {
    return;
  }

  if ($context === null) {
    $context = sfContext::getInstance();
  }

  $manager = new sfDesastreManager($configPath);
  $manager->applyToRequest($request, $response, $extraParams, '/desastres', null, $context);
}

/**
 * Vérifie si une règle de désastre correspond pour les paramètres donnés
 *
 * @param string $ruleExpression Expression de la règle à tester
 * @param array $query Paramètres de requête
 * @param array $context Contexte additionnel
 * @return bool True si la règle correspond
 */
function desastre_rule_matches($ruleExpression, array $query = array(), array $context = array())
{
  $engine = new sfDesastreRuleEngine($query, $context);
  return $engine->evaluate($ruleExpression);
}

/**
 * Obtient les recettes correspondant aux critères donnés
 *
 * @param array $query Paramètres de requête
 * @param string $configPath Chemin vers le fichier de configuration
 * @param array $context Contexte additionnel
 * @return array Tableau de recettes
 */
function get_desastre_recettes(array $query = array(), $configPath = null, array $context = array())
{
  if ($configPath === null) {
    $configPath = sfConfig::get('sf_app_config_dir') . '/desastres.yml';
  }

  if (!file_exists($configPath)) {
    return array();
  }

  $manager = new sfDesastreManager($configPath);
  return $manager->findRecettes($query, $context);
}
