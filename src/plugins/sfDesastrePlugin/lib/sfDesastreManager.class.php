<?php

/**
 * Gestionnaire de desastres.
 *
 * Cette classe charge les regles et recettes depuis un fichier YAML,
 * evalue les regles correspondantes et selectionne les recettes a appliquer.
 *
 * @package    sfDesastrePlugin
 * @subpackage lib
 * @author     Musique Approximative
 */
class sfDesastreManager
{
  protected $config = null;
  protected $ruleEngine = null;

  /**
   * Constructeur
   *
   * @param string|array $config Chemin vers le fichier YAML de configuration ou tableau de configuration
   */
  public function __construct($config = null)
  {
    if (is_string($config) && file_exists($config)) {
      $this->config = sfYaml::load($config);
    } elseif (is_array($config)) {
      $this->config = $config;
    } else {
      $this->config = array('regles' => array(), 'recettes' => array());
    }

    $this->ruleEngine = new sfDesastreRuleEngine();
  }

  /**
   * Charge la configuration depuis un fichier YAML
   *
   * @param string $configPath Chemin vers le fichier de configuration
   * @return sfDesastreManager Instance courante pour chainage
   */
  public function loadConfig($configPath)
  {
    if (!file_exists($configPath)) {
      throw new sfException(sprintf('Le fichier de configuration "%s" n\'existe pas.', $configPath));
    }

    $this->config = sfYaml::load($configPath);
    return $this;
  }

  /**
   * Trouve les recettes correspondant aux regles pour une requete donnee
   *
   * @param sfWebRequest|array $request Objet requete Symfony ou tableau de parametres
   * @param array $context Contexte additionnel (optionnel)
   * @return array Tableau de recettes selectionnees
   */
  public function findRecettes($request = array(), array $context = array())
  {
    if (!isset($this->config['regles']) || !is_array($this->config['regles'])) {
      return array();
    }

    // Extraire les parametres depuis sfWebRequest ou utiliser le tableau directement
    if ($request instanceof sfWebRequest) {
      $query = $request->getParameterHolder()->getAll();
    } else {
      $query = is_array($request) ? $request : array();
    }

    $this->ruleEngine->setQuery($query);
    $this->ruleEngine->setContext($context);

    $selectedRecettes = array();

    foreach ($this->config['regles'] as $regle) {
      if (!isset($regle['query'])) {
        continue;
      }

      // Evaluer la regle
      if ($this->ruleEngine->evaluate($regle['query'])) {
        // Verifier la probabilite si definie
        $probability = isset($regle['probability']) ? (float) $regle['probability'] : 1.0;

        if (mt_rand() / mt_getrandmax() <= $probability) {
          // Ajouter les recettes associees
          if (isset($regle['recettes']) && is_array($regle['recettes'])) {
            // Appliquer toutes les recettes listees
            foreach ($regle['recettes'] as $recetteName) {
              if (isset($this->config['recettes'][$recetteName])) {
                $recette = $this->config['recettes'][$recetteName];

                // Verifier si la recette est activee
                if (!isset($recette['enabled']) || $recette['enabled'] === true) {
                  $recette['name'] = $recetteName;
                  $selectedRecettes[] = $recette;
                }
              }
            }
          }
        }
      }
    }

    return $selectedRecettes;
  }

  /**
   * Applique automatiquement les desastres pour une requete/reponse donnee
   *
   * @param sfWebRequest $request Objet requete Symfony
   * @param sfWebResponse $response Objet reponse Symfony
   * @param array $extraParams Parametres supplementaires (optionnel)
   * @param string $webRoot Chemin racine web (ex: /desastres)
   * @param string $fsRoot Chemin systeme de fichiers racine
   * @param sfContext $context Contexte Symfony (optionnel)
   */
  public function applyToRequest(sfWebRequest $request, sfWebResponse $response, array $extraParams = array(), $webRoot = '/desastres', $fsRoot = null, sfContext $context = null)
  {
    // Fusionner les parametres de la requete avec les parametres supplementaires
    $allParams = array_merge($request->getParameterHolder()->getAll(), $extraParams);

    // Trouver les recettes correspondantes
    $recettes = $this->findRecettes($allParams);

    // Appliquer les recettes a la reponse
    if (!empty($recettes)) {
      $this->applyRecettesToResponse($response, $recettes, $webRoot, $fsRoot, $context);
    }
  }

  /**
   * Applique les desastres a une reponse Symfony
   *
   * @param sfWebResponse $response L'objet reponse Symfony
   * @param array $recettes Les recettes a appliquer
   * @param string $webRoot Chemin racine web (ex: /desastre/recettes)
   * @param string $fsRoot Chemin systeme de fichiers racine
   * @param sfContext $context Contexte Symfony (optionnel)
   */
  public function applyRecettesToResponse(sfWebResponse $response, array $recettes, $webRoot = '/desastres', $fsRoot = null, sfContext $context = null)
  {
    if ($fsRoot === null) {
      $fsRoot = sfConfig::get('sf_web_dir') . $webRoot;
    }

    $allOptions = array();

    foreach ($recettes as $recette) {
      if (!isset($recette['desastre'])) {
        continue;
      }

      $desastreName = $recette['desastre'];
      $options = isset($recette['options']) ? $recette['options'] : array();

      // Stocker les options pour injection globale
      $allOptions[$desastreName] = $options;

      // Ajouter les stylesheets
      $stylesheets = $this->findAssets($fsRoot, $desastreName, 'stylesheets', array('css'));
      foreach ($stylesheets as $stylesheet) {
        $webPath = $webRoot . '/' . $desastreName . '/stylesheets/' . basename($stylesheet);
        $response->addStylesheet($webPath);
      }

      // Ajouter les javascripts
      $javascripts = $this->findAssets($fsRoot, $desastreName, 'javascript', array('js'));
      foreach ($javascripts as $javascript) {
        $webPath = $webRoot . '/' . $desastreName . '/javascript/' . basename($javascript);
        $response->addJavascript($webPath);
      }
    }

    // Injecter les options dans le HTML via un script inline
    if (!empty($allOptions)) {
      $this->injectDesastreOptions($response, $allOptions, $context);
    }
  }

  /**
   * Injecte les options des desastres dans le HTML
   * Les options sont accessibles via :
   * - JavaScript: window.DesastreOptions
   * - CSS: variables CSS custom properties --desastre-*
   *
   * @param sfWebResponse $response L'objet reponse Symfony
   * @param array $options Options a injecter
   * @param sfContext $context Contexte Symfony (optionnel)
   */
  protected function injectDesastreOptions(sfWebResponse $response, array $options, sfContext $context = null)
  {
    // Creer le code JavaScript pour les options
    $jsCode = '<script type="text/javascript">' . "\n";
    $jsCode .= '/* Desastre Options - Auto-generated */' . "\n";
    $jsCode .= 'window.DesastreOptions = ' . json_encode($options) . ';' . "\n";
    $jsCode .= '</script>';

    // Creer des variables CSS custom properties
    $cssVars = array();
    foreach ($options as $desastreName => $desastreOptions) {
      foreach ($desastreOptions as $key => $value) {
        $cssVarName = '--desastre-' . $desastreName . '-' . $key;
        $cssVars[$cssVarName] = $value;
      }
    }

    $cssCode = '';
    if (!empty($cssVars)) {
      $cssCode = '<style type="text/css">' . "\n";
      $cssCode .= '/* Desastre Options - Auto-generated */' . "\n";
      $cssCode .= ':root {' . "\n";
      foreach ($cssVars as $varName => $varValue) {
        $cssCode .= '  ' . $varName . ': ' . $varValue . ';' . "\n";
      }
      $cssCode .= '}' . "\n";
      $cssCode .= '</style>';
    }

    // Stocker dans les attributs utilisateur du contexte pour injection par le filtre
    if ($context !== null) {
      $context->getUser()->setAttribute('desastre_options_js', $jsCode);
      $context->getUser()->setAttribute('desastre_options_css', $cssCode);
    }
  }

  /**
   * Trouve les assets (JS/CSS) pour un desastre donne
   * Les fichiers sont tries par ordre alphabetique naturel
   * (ex: 01-base.js, 02-animations.js, 10-effects.js)
   *
   * @param string $fsRoot Racine du systeme de fichiers
   * @param string $desastreName Nom du desastre
   * @param string $assetType Type d'asset (javascript, stylesheets)
   * @param array $extensions Extensions a rechercher
   * @return array Tableau de chemins de fichiers tries
   */
  protected function findAssets($fsRoot, $desastreName, $assetType, array $extensions)
  {
    $assets = array();
    $dir = $fsRoot . '/' . $desastreName . '/' . $assetType;

    if (!is_dir($dir)) {
      return $assets;
    }

    $files = scandir($dir);

    // Filtrer les fichiers valides
    $validFiles = array();
    foreach ($files as $file) {
      $filePath = $dir . '/' . $file;

      if (!is_file($filePath)) {
        continue;
      }

      $ext = pathinfo($file, PATHINFO_EXTENSION);
      if (in_array($ext, $extensions)) {
        $validFiles[] = $file;
      }
    }

    // Trier par ordre alphabetique naturel (gere correctement 01, 02, 10, etc.)
    natsort($validFiles);

    // Creer les chemins complets
    foreach ($validFiles as $file) {
      $assets[] = $dir . '/' . $file;
    }

    return $assets;
  }

  /**
   * Recupere le moteur de regles
   *
   * @return sfDesastreRuleEngine
   */
  public function getRuleEngine()
  {
    return $this->ruleEngine;
  }

  /**
   * Recupere la configuration
   *
   * @return array
   */
  public function getConfig()
  {
    return $this->config;
  }
}
