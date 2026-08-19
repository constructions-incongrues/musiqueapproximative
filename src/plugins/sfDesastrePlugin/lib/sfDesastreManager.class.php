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
  /**
   * Une regle a ete appliquee parce que le tirage l'a designee.
   */
  const MODE_TIRE = 'tire';

  /**
   * Une regle a ete appliquee parce que son parametre `trigger` etait dans l'URL.
   *
   * Ce n'est PAS un tirage : la probabilite est court-circuitee. C'est l'outil par
   * lequel on essaie un desastre a la main — `?danse=1` applique `danse`. Compter ces
   * applications avec les tirages gonflerait exactement les recettes sur lesquelles on
   * travaille, d'ou la distinction jusque dans le releve.
   */
  const MODE_FORCE = 'force';

  /**
   * Nom du journal dedie, sous sf_log_dir.
   */
  const JOURNAL = 'desastres.log';

  /**
   * En-tete nommant le ou les desastres appliques a la reponse.
   */
  const ENTETE = 'X-Desastre';

  /**
   * Valeur de l'en-tete quand aucune recette n'a ete retenue.
   */
  const AUCUN = 'aucun';

  protected $config = null;
  protected $ruleEngine = null;

  /**
   * Releve du dernier appel a findRecettes() : une entree par recette retenue.
   *
   * Tableau vide = les regles ont ete evaluees et aucune n'a designe de recette. C'est
   * une information, pas une absence d'information : une recette qui ne sort jamais et
   * une recette qu'on n'a jamais evaluee sont deux situations differentes.
   */
  protected $dernierReleve = array();

  /**
   * Chemins declares sous "imports" qui ne designent aucun fichier.
   *
   * Un import non resolu ne fait pas echouer le chargement : la page reste
   * servie, avec les regles et recettes des imports valides. Il faut donc
   * conserver la liste pour la signaler, faute de quoi une configuration
   * partiellement invalide se comporte exactement comme une configuration
   * complete.
   *
   * @var array
   */
  protected $unresolvedImports = array();

  /**
   * Constructeur
   *
   * @param string|array $config Chemin vers le fichier YAML de configuration ou tableau de configuration
   */
  public function __construct($config = null)
  {
    if (is_string($config) && file_exists($config)) {
      // Charger via loadConfig pour traiter les imports
      $this->ruleEngine = new sfDesastreRuleEngine();
      $this->loadConfig($config);
    } elseif (is_array($config)) {
      $this->config = $config;
      $this->ruleEngine = new sfDesastreRuleEngine();
    } else {
      $this->config = array('regles' => array(), 'recettes' => array());
      $this->ruleEngine = new sfDesastreRuleEngine();
    }
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

    $config = sfYaml::load($configPath);

    // Traiter les imports si presents
    if (isset($config['imports'])) {
      $config = $this->processImports($config, dirname($configPath));
    }

    $this->config = $config;
    return $this;
  }

  /**
   * Traite les imports de fichiers YAML
   *
   * @param array $config Configuration avec imports
   * @param string $baseDir Repertoire de base pour les chemins relatifs
   * @return array Configuration fusionnee
   */
  protected function processImports(array $config, $baseDir)
  {
    $imports = isset($config['imports']) ? $config['imports'] : array();
    unset($config['imports']);

    $this->unresolvedImports = array();

    // Initialiser les tableaux si non definis
    if (!isset($config['regles'])) {
      $config['regles'] = array();
    }
    if (!isset($config['recettes'])) {
      $config['recettes'] = array();
    }

    // Importer les regles
    if (isset($imports['regles']) && is_array($imports['regles'])) {
      foreach ($imports['regles'] as $importPath) {
        $fullPath = $baseDir . '/' . $importPath;
        if (file_exists($fullPath)) {
          $imported = sfYaml::load($fullPath);
          if (isset($imported['regles']) && is_array($imported['regles'])) {
            $config['regles'] = array_merge($config['regles'], $imported['regles']);
          }
        } else {
          // Log warning mais ne pas echouer
          error_log(sprintf('[sfDesastreManager] WARNING: Import file not found: %s', $fullPath));
          $this->unresolvedImports[] = $importPath;
        }
      }
    }

    // Importer les recettes
    if (isset($imports['recettes']) && is_array($imports['recettes'])) {
      foreach ($imports['recettes'] as $importPath) {
        $fullPath = $baseDir . '/' . $importPath;
        if (file_exists($fullPath)) {
          $imported = sfYaml::load($fullPath);
          if (isset($imported['recettes']) && is_array($imported['recettes'])) {
            // Fusionner les recettes (les cles sont les noms)
            $config['recettes'] = array_merge($config['recettes'], $imported['recettes']);
          }
        } else {
          error_log(sprintf('[sfDesastreManager] WARNING: Import file not found: %s', $fullPath));
          $this->unresolvedImports[] = $importPath;
        }
      }
    }

    $config['regles'] = $this->dedupliquerRegles($config['regles']);

    return $config;
  }

  /**
   * Ne conserve qu'une occurrence d'une regle declaree plusieurs fois.
   *
   * Deux regles sont la meme lorsque leur condition, leur probabilite, leur
   * liste de recettes et leur declencheur sont identiques.
   *
   * `trigger` fait partie de la comparaison. L'exclure reviendrait a fusionner
   * une regle forcable avec une regle qui ne l'est pas, et a conserver la
   * premiere declaree — donc a supprimer un declencheur. L'exigence
   * « Couverture des declencheurs » veut precisement qu'aucun desastre ne soit
   * observable seulement par tirage : un dedoublonnage qui retire un
   * declencheur rend la configuration moins conforme qu'avant.
   *
   * La premiere occurrence est conservee a son rang, la seconde disparait sans
   * decaler ce qui la precede : l'ordre de declaration determine l'ordre
   * d'evaluation.
   *
   * @param  array $regles
   * @return array
   */
  protected function dedupliquerRegles(array $regles)
  {
    $vues = array();
    $uniques = array();

    foreach ($regles as $regle) {
      if (!is_array($regle)) {
        continue;
      }

      // Champs nommes un a un, dans un ordre fixe : serialiser la regle entiere
      // ferait dependre l'unicite de l'ordre des cles dans le fichier YAML.
      $signature = serialize(array(
        isset($regle['query']) ? $regle['query'] : null,
        isset($regle['probability']) ? (float) $regle['probability'] : 1.0,
        isset($regle['recettes']) ? (array) $regle['recettes'] : array(),
        isset($regle['trigger']) ? $regle['trigger'] : null,
      ));

      if (isset($vues[$signature])) {
        continue;
      }

      $vues[$signature] = true;
      $uniques[] = $regle;
    }

    return $uniques;
  }

  /**
   * Retourne les chemins declares sous "imports" qui ne designent aucun fichier.
   *
   * @return array Tableau de chemins relatifs, vide si tous les imports resolvent
   */
  public function getUnresolvedImports()
  {
    return $this->unresolvedImports;
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

    // Releve du tirage. Rempli ici parce que c'est le seul endroit qui sache COMMENT une
    // regle a ete appliquee ; ecrit ailleurs, par applyToRequest, pour que les appels
    // directs a findRecettes — tests, helper get_desastre_recettes — ne journalisent pas.
    $this->dernierReleve = array();

    // Une recette peut etre designee par plusieurs regles satisfaites — c'est le
    // cas nominal quand deux conditions se recouvrent. Elle ne doit enrichir la
    // reponse qu'une fois, au rang de la premiere regle qui la designe.
    $dejaRetenues = array();

    foreach ($this->config['regles'] as $regle) {
      if (!isset($regle['query'])) {
        continue;
      }

      $mode = null;

      // Verifier si un parametre trigger est defini et present dans l'URL
      $triggerMatch = false;
      if (isset($regle['trigger'])) {
        $triggerParam = $regle['trigger'];
        // Le trigger matche si le parametre existe dans la query, quelque soit sa valeur
        if (isset($query[$triggerParam])) {
          $triggerMatch = true;
        }
      }

      // Si le trigger matche, on applique la regle systematiquement
      // Sinon, on evalue la regle normalement avec query + probability
      $shouldApply = false;

      if ($triggerMatch) {
        // Trigger present : application systematique
        $shouldApply = true;
        $mode = self::MODE_FORCE;
      } else {
        // Pas de trigger ou trigger absent : evaluation normale
        if ($this->ruleEngine->evaluate($regle['query'])) {
          // Verifier la probabilite si definie
          $probability = isset($regle['probability']) ? (float) $regle['probability'] : 1.0;

          if (mt_rand() / mt_getrandmax() <= $probability) {
            $shouldApply = true;
            $mode = self::MODE_TIRE;
          }
        }
      }

      if ($shouldApply) {
        // Ajouter les recettes associees
        if (isset($regle['recettes']) && is_array($regle['recettes'])) {
          // Appliquer toutes les recettes listees
          foreach ($regle['recettes'] as $recetteName) {
            if (isset($this->config['recettes'][$recetteName])) {
              // Deja retenue par une regle precedente : on garde son rang
              // d'origine plutot que de l'ajouter une seconde fois.
              if (isset($dejaRetenues[$recetteName])) {
                continue;
              }

              $recette = $this->config['recettes'][$recetteName];

              // Verifier si la recette est activee
              if (!isset($recette['enabled']) || $recette['enabled'] === true) {
                $recette['name'] = $recetteName;
                $selectedRecettes[] = $recette;
                $this->dernierReleve[] = array(
                  'recette' => $recetteName,
                  'mode' => $mode,
                  'trigger' => isset($regle['trigger']) ? $regle['trigger'] : null,
                );
                $dejaRetenues[$recetteName] = true;
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

    // Le releve s'ecrit ICI et pas dans findRecettes : c'est le chemin de production
    // d'une page, et lui seul. Une page servie depuis le cache ne passe pas par la —
    // le releve compte donc des TIRAGES, jamais des consultations.
    $this->journaliser($request);

    // L'en-tete est pose PENDANT la production, avec le reste de la reponse, et non sur
    // un succes de cache : sfViewCacheManager::setPageCache() serialise la reponse
    // entiere — en-tetes compris — et getPageCache() remplace l'objet reponse par celui
    // qu'il desérialise. Ce qu'on poserait sur un succes serait donc jete.
    // La consequence utile est l'invariance : deux consultations de la meme
    // representation portent le meme en-tete, comme elles portent le meme desastre.
    $this->nommerDesastreDansLaReponse($response);

    // Appliquer les recettes a la reponse
    if (!empty($recettes)) {
      $this->applyRecettesToResponse($response, $recettes, $webRoot, $fsRoot, $context);
    }

    // Signaler les imports non resolus, qu'une recette s'applique ou non :
    // une configuration cassee doit se voir meme quand aucune regle ne matche.
    $this->injectUnresolvedImportsWarning($context);
  }

  /**
   * Nomme dans la reponse le ou les desastres appliques.
   *
   * L'absence est DECLAREE et non omise : un en-tete absent ne distingue pas « aucun
   * desastre » d'« en-tete casse », et c'est precisement la question a laquelle cette
   * mesure doit repondre.
   *
   * @param sfWebResponse $response Reponse en cours de production
   */
  protected function nommerDesastreDansLaReponse(sfWebResponse $response)
  {
    $noms = array();

    foreach ($this->dernierReleve as $entree) {
      $noms[] = $entree['recette'];
    }

    $response->setHttpHeader(self::ENTETE, $noms ? implode(',', $noms) : self::AUCUN);
  }

  /**
   * Retourne le releve du dernier appel a findRecettes().
   *
   * @return array Une entree par recette retenue : recette, mode, trigger
   */
  public function getDernierReleve()
  {
    return $this->dernierReleve;
  }

  /**
   * Ecrit une ligne de releve dans le journal dedie.
   *
   * Une ligne par PRODUCTION de page, y compris quand aucune recette n'est retenue.
   *
   * Le journal est distinct du journal applicatif pour qu'un denombrement n'ait pas a
   * filtrer le reste, et le format est une ligne JSON par production : un nom de recette
   * ne contient pas d'espace aujourd'hui, mais compter en supposant le contraire est le
   * genre de pari qui se perd silencieusement.
   *
   * L'ecriture ne doit jamais faire echouer une page. Un desastre est un ornement ; un
   * journal indisponible n'est pas une raison de rendre une erreur au visiteur.
   *
   * @param sfWebRequest $request Requete en cours, pour situer le releve
   */
  protected function journaliser(sfWebRequest $request = null)
  {
    $repertoire = sfConfig::get('sf_log_dir');

    if (!$repertoire || !is_dir($repertoire)) {
      return;
    }

    $ligne = array(
      'date' => date('c'),
      'uri' => $request ? $request->getPathInfo() : null,
      'recettes' => $this->dernierReleve,
    );

    @file_put_contents(
      $repertoire.DIRECTORY_SEPARATOR.self::JOURNAL,
      json_encode($ligne, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n",
      FILE_APPEND | LOCK_EX
    );
  }

  /**
   * Emet un avertissement dans la console du navigateur pour chaque import non resolu.
   *
   * L'attribut est toujours ecrit, y compris a vide : les attributs utilisateur de
   * Symfony 1.x persistent en session, et un avertissement d'une requete precedente
   * survivrait sinon a la correction du chemin fautif.
   *
   * L'injection elle-meme est faite par sfDesastreFilter, qui n'ecrit que dans les
   * reponses HTML : les formats json, xspf, max, le flux et oEmbed sont donc epargnes
   * sans qu'il y ait rien a verifier ici.
   *
   * @param sfContext $context Contexte Symfony (optionnel)
   */
  protected function injectUnresolvedImportsWarning(sfContext $context = null)
  {
    if ($context === null) {
      return;
    }

    $jsCode = null;

    if (!empty($this->unresolvedImports)) {
      $jsCode = '<script type="text/javascript">' . "\n";
      $jsCode .= '/* Desastre - Auto-generated */' . "\n";
      $jsCode .= 'console.warn(' . json_encode(sprintf(
        'Désastres : %d import(s) déclaré(s) dans desastres.yml ne désignent aucun fichier. Les règles et recettes qu\'ils portent ne sont pas chargées.',
        count($this->unresolvedImports)
      )) . ', ' . json_encode(array_values($this->unresolvedImports)) . ');' . "\n";
      $jsCode .= '</script>';
    }

    $context->getUser()->setAttribute('desastre_warnings_js', $jsCode);
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

      // Ajouter les scripts externes si definis dans la recette
      if (isset($recette['scripts']) && is_array($recette['scripts'])) {
        foreach ($recette['scripts'] as $script) {
          $response->addJavascript($script . $this->empreinteDepuisCheminWeb($script));
        }
      }

      // Ajouter les stylesheets
      $stylesheets = $this->findAssets($fsRoot, $desastreName, 'stylesheets', array('css'));
      foreach ($stylesheets as $stylesheet) {
        $webPath = $webRoot . '/' . $desastreName . '/stylesheets/' . basename($stylesheet);
        $response->addStylesheet($webPath . $this->empreinte($stylesheet));
      }

      // Ajouter les javascripts
      $javascripts = $this->findAssets($fsRoot, $desastreName, 'javascript', array('js'));
      foreach ($javascripts as $javascript) {
        $webPath = $webRoot . '/' . $desastreName . '/javascript/' . basename($javascript);
        $response->addJavascript($webPath . $this->empreinte($javascript));
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
   * Suffixe de version d'un fichier, deduit de sa date de modification.
   *
   * POURQUOI CE SUFFIXE EXISTE
   *
   * Cloudflare sert les fichiers de desastre depuis son cache et rien ne le previent
   * d'un deploiement : le 2026-08-19, l'edge a servi pendant des heures un
   * `bande-usee.js` anterieur a deux correctifs deja en ligne sur l'origine. Le suffixe
   * change l'adresse a chaque modification du fichier, donc le cache va forcement
   * chercher la nouvelle version. Le probleme disparait sans purge et sans reglage de
   * tableau de bord.
   *
   * CE N'EST PAS LE `?cachebust=` QUI PIEGE LES DIAGNOSTICS
   *
   * Ajouter une chaine au hasard a la main pour « contourner le cache » teste une adresse
   * que personne ne demande, et conclut a tort que le deploiement est bon. Ici c'est la
   * page elle-meme qui emet l'adresse versionnee : c'est celle que tout le monde recoit.
   *
   * POURQUOI `filemtime` ET PAS UNE EMPREINTE DU CONTENU
   *
   * Un `md5_file` se lit a chaque rendu de page, sur chaque fichier de chaque recette
   * retenue. La date est dans l'inode, deja chaude dans le cache du systeme de fichiers.
   * Une reecriture sans changement invalide inutilement le cache, ce qui coute un
   * telechargement de plus, jamais une page fausse.
   *
   * @param string $cheminFichier Chemin systeme de fichiers de l'asset
   * @return string `?v=<horodatage>`, ou chaine vide si le fichier est illisible
   */
  protected function empreinte($cheminFichier)
  {
    // Un desastre est un ornement : un fichier illisible ne doit pas faire echouer la page.
    $mtime = @filemtime($cheminFichier);

    return $mtime ? '?v=' . $mtime : '';
  }

  /**
   * Meme empreinte, pour un `scripts:` de recette.
   *
   * Ces chemins-la sont declares dans `desastres.yml` en adresses web enracinees
   * (`/desastres/shared/desastre-audio.js`), non en chemins systeme. On les ramene sous
   * `sf_web_dir` pour lire la date. Une URL absolue vers un tiers n'a pas de fichier
   * local, donc pas d'empreinte : la resolution echoue et on rend une chaine vide.
   *
   * @param string $cheminWeb Adresse enracinee declaree dans la recette
   * @return string `?v=<horodatage>`, ou chaine vide
   */
  protected function empreinteDepuisCheminWeb($cheminWeb)
  {
    if (strpos($cheminWeb, '/') !== 0) {
      return '';
    }

    return $this->empreinte(sfConfig::get('sf_web_dir') . $cheminWeb);
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
