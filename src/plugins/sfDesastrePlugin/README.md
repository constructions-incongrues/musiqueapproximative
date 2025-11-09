# sfDesastrePlugin

Plugin Symfony 1.5 pour gérer un système de "désastres" basé sur des règles. Ce plugin permet d'injecter dynamiquement des assets (CSS/JavaScript) dans vos pages en fonction de conditions définies par des règles YAML.

## Caractéristiques

- **Moteur de règles flexible** : Évaluez des expressions complexes basées sur les paramètres de requête et le contexte
- **Support des regex** : Testez les valeurs avec des expressions régulières
- **Contexte temporel** : Déclenchez des désastres selon la date, l'heure, le jour de la semaine
- **Probabilités** : Contrôlez la fréquence d'apparition des désastres
- **Configuration YAML** : Définissez vos règles et recettes dans un fichier de configuration simple
- **Assets automatiques** : Injection automatique de CSS et JavaScript dans la réponse

## Installation

1. Copiez le plugin dans votre répertoire `plugins/` :

```bash
cp -r sfDesastrePlugin /path/to/your/symfony/project/plugins/
```

2. Activez le plugin dans votre `config/ProjectConfiguration.class.php` :

```php
class ProjectConfiguration extends sfProjectConfiguration
{
  public function setup()
  {
    $this->enablePlugins(array(
      'sfDoctrinePlugin',
      'sfDesastrePlugin',  // Ajoutez cette ligne
      // ... autres plugins
    ));
  }
}
```

3. Activez le filtre dans votre `apps/frontend/config/filters.yml` :

```yaml
rendering: ~
security:  ~

# Filtre pour injecter les options des desastres dans le HTML
desastre:
  class: sfDesastreFilter

cache:     ~
execution: ~
```

4. Créez votre fichier de configuration :

```bash
cp plugins/sfDesastrePlugin/data/config/desastres.yml.example apps/frontend/config/desastres.yml
```

5. Créez la structure de dossiers pour vos assets :

```bash
mkdir -p web/desastres
```

## Configuration

### Structure du fichier `desastres.yml`

```yaml
regles:
  - query: "query.title ~ /.*amour.*/i || (context.date.day == '14' && context.date.month == '2')"
    recettes: [amour_caca, amour_pipi]
    probability: 0.5

recettes:
  amour_pipi:
    enabled: true
    desastre: amour
    options:
      color: yellow
      text: PIPI
```

### Syntaxe des règles

#### Variables disponibles

- **query.*** : Tous les paramètres de la requête
  - `query.title` : Titre d'un post
  - `query.artist` : Artiste
  - `query.contributor` : Contributeur
  - etc.

- **context.date.*** : Informations de date/heure
  - `context.date.day` : Jour du mois (1-31)
  - `context.date.month` : Mois (1-12)
  - `context.date.year` : Année (ex: 2025)
  - `context.date.hour` : Heure (0-23)
  - `context.date.minute` : Minute (0-59)
  - `context.date.weekday` : Jour de la semaine (1=lundi, 7=dimanche)

#### Opérateurs

- `~` : Test de regex
  - Exemple : `query.title ~ /.*love.*/i`

- `==`, `!=`, `>`, `<`, `>=`, `<=` : Comparaisons
  - Exemple : `context.date.month == '12'`

- `&&` : ET logique
  - Exemple : `query.artist ~ /.*beatles.*/i && context.date.hour >= '18'`

- `||` : OU logique
  - Exemple : `query.title ~ /.*rock.*/i || query.title ~ /.*metal.*/i`

- `()` : Groupement
  - Exemple : `(context.date.day == '25' && context.date.month == '12')`

#### Exemples de règles

```yaml
# Saint-Valentin ou titre contenant "amour"
- query: "(context.date.day == '14' && context.date.month == '2') || query.title ~ /.*amour.*/i"
  recettes: [valentines_day]

# Weekend uniquement
- query: "context.date.weekday == '6' || context.date.weekday == '7'"
  recettes: [weekend_special]
  probability: 0.3

# Artiste spécifique
- query: "query.artist ~ /.*beatles.*/i"
  recettes: [beatles_theme]

# Combinaison complexe
- query: "query.contributor == 'john' && (query.title ~ /.*rock.*/i || query.title ~ /.*punk.*/i)"
  recettes: [john_rock_playlist]
```

## Utilisation

### Méthode 1 : Utilisation directe du sfDesastreManager

```php
class postActions extends sfActions
{
  public function executeShow(sfWebRequest $request)
  {
    $post = Doctrine_Core::getTable('Post')->find($request->getParameter('id'));

    // Charger le gestionnaire de desastres
    $desastreManager = new sfDesastreManager(
      sfConfig::get('sf_app_config_dir') . '/desastres.yml'
    );

    // Parametres supplementaires pour l'evaluation des regles
    $extraParams = array(
      'title' => $post->track_title,
      'artist' => $post->track_author,
      'contributor' => strtolower($post->getContributorDisplayName())
    );

    // Applique automatiquement les desastres (trouve les recettes + applique les assets)
    $desastreManager->applyToRequest($request, $this->getResponse(), $extraParams);

    $this->post = $post;
  }
}
```

### Méthode 2 : Utilisation avec le helper (recommandé)

```php
class postActions extends sfActions
{
  public function executeShow(sfWebRequest $request)
  {
    $post = Doctrine_Core::getTable('Post')->find($request->getParameter('id'));

    // Charger le helper
    $this->getContext()->getConfiguration()->loadHelpers('Desastre');

    // Parametres supplementaires
    $extraParams = array(
      'title' => $post->track_title,
      'artist' => $post->track_author,
      'contributor' => strtolower($post->getContributorDisplayName())
    );

    // Application en une seule ligne !
    apply_desastre($request, $this->getResponse(), $extraParams);

    $this->post = $post;
  }
}
```

### Chargement automatique du helper

Vous pouvez charger le helper automatiquement dans `settings.yml` :

```yaml
# apps/frontend/config/settings.yml
all:
  .settings:
    standard_helpers: [Partial, Cache, Desastre]
```

Ainsi, `apply_desastre()` sera disponible partout sans avoir besoin de `loadHelpers('Desastre')`.

## Structure des assets

Les assets doivent être organisés par "désastre" :

```
web/
└── desastres/
    ├── amour/
    │   ├── stylesheets/
    │   │   └── style.css
    │   └── javascript/
    │       └── amour.js
    ├── noel/
    │   ├── stylesheets/
    │   │   └── snow.css
    │   └── javascript/
    │       └── snowfall.js
    └── robot/
        ├── stylesheets/
        │   └── robot.css
        └── javascript/
            └── robot-animation.js
```

Le plugin détectera automatiquement tous les fichiers `.css` et `.js` dans ces dossiers.

### Ordre de chargement des assets

Les fichiers sont chargés par **ordre alphabétique naturel**. Pour contrôler l'ordre de chargement, utilisez des préfixes numériques :

```
web/
└── desastres/
    └── robot/
        ├── stylesheets/
        │   ├── 01-base.css        # Chargé en premier
        │   ├── 02-animations.css  # Chargé en second
        │   └── 03-theme.css       # Chargé en dernier
        └── javascript/
            ├── 01-config.js       # Chargé en premier
            ├── 02-core.js         # Chargé en second
            ├── 03-animations.js   # Chargé en troisième
            └── 10-effects.js      # Chargé en dernier
```

**Note** : Le tri naturel (`natsort`) gère correctement les nombres, donc `10-effects.js` sera chargé après `02-core.js` (et non entre `01` et `02`).

## Options des desastres

Les options definies dans la configuration YAML sont automatiquement injectees dans le HTML et accessibles de deux facons :

### Acces via JavaScript

Les options sont disponibles dans l'objet global `window.DesastreOptions` :

```javascript
// Exemple : acceder aux options du desastre "amour"
if (window.DesastreOptions && window.DesastreOptions.amour) {
  var options = window.DesastreOptions.amour;
  console.log('Theme:', options.theme);
  console.log('Color:', options.color);
}

// Structure de l'objet :
// window.DesastreOptions = {
//   "amour": {
//     "theme": "love",
//     "color": "red"
//   },
//   "robot": {
//     "theme": "robot",
//     "speed": "fast"
//   }
// }
```

### Acces via CSS (Custom Properties)

Les options sont aussi injectees comme variables CSS :

```css
/* Les options deviennent des variables CSS au format --desastre-NOM-CLE */

/* Exemple avec le desastre "amour" ayant l'option color: "red" */
.heart {
  background-color: var(--desastre-amour-color);
  /* Equivalent a: background-color: red; */
}

/* Exemple avec le desastre "robot" ayant l'option speed: "2s" */
.robot-animation {
  animation-duration: var(--desastre-robot-speed);
  /* Equivalent a: animation-duration: 2s; */
}
```

### Exemple complet

Configuration YAML :
```yaml
recettes:
  amour:
    enabled: true
    desastre: amour
    options:
      color: "#ff0066"
      intensity: "high"
      duration: "3s"
```

JavaScript (`web/desastres/amour/javascript/amour.js`) :
```javascript
(function() {
  var options = window.DesastreOptions.amour;

  // Utiliser les options
  if (options.intensity === 'high') {
    // Animation intense
    createHearts(100);
  } else {
    // Animation normale
    createHearts(20);
  }
})();
```

CSS (`web/desastres/amour/stylesheets/style.css`) :
```css
.heart {
  /* Utiliser la couleur depuis les options */
  background-color: var(--desastre-amour-color);

  /* Utiliser la duree depuis les options */
  animation-duration: var(--desastre-amour-duration);
}
```

### Avantages de ce systeme

1. **Centralisation** : Toutes les options sont dans le fichier YAML, pas besoin de modifier le JS/CSS
2. **Reutilisabilite** : Un meme desastre peut avoir differentes configurations selon la recette
3. **Flexibilite** : Les options sont accessibles cote client (JS et CSS)
4. **Maintenabilite** : Modification facile sans toucher au code

### Exemple de recettes multiples avec options differentes

```yaml
regles:
  # Saint-Valentin : coeurs rouges intenses
  - query: "context.date.day == '14' && context.date.month == '2'"
    recettes: [amour_intense]

  # Titre contient "love" : coeurs roses doux
  - query: "query.title ~ /.*love.*/i"
    recettes: [amour_doux]

recettes:
  amour_intense:
    enabled: true
    desastre: amour  # Meme dossier d'assets
    options:
      color: "#ff0033"
      intensity: high
      count: 50

  amour_doux:
    enabled: true
    desastre: amour  # Meme dossier d'assets
    options:
      color: "#ffb3d9"
      intensity: low
      count: 10
```

## API

### sfDesastreManager

#### `__construct($config = null)`

Cree une nouvelle instance du gestionnaire.

- **$config** : Chemin vers le fichier YAML ou tableau de configuration

#### `loadConfig($configPath)`

Charge la configuration depuis un fichier YAML.

- **$configPath** : Chemin vers le fichier de configuration
- **Retour** : Instance courante (chainage)

#### `applyToRequest(sfWebRequest $request, sfWebResponse $response, array $extraParams = array(), $webRoot = '/desastres', $fsRoot = null)`

**NOUVEAU** : Methode tout-en-un qui applique automatiquement les desastres pour une requete donnee.

- **$request** : Objet requete Symfony (sfWebRequest)
- **$response** : Objet reponse Symfony
- **$extraParams** : Parametres supplementaires a ajouter aux parametres de la requete
- **$webRoot** : Chemin web racine
- **$fsRoot** : Chemin systeme de fichiers racine

Cette methode :
1. Fusionne les parametres de `$request` avec `$extraParams`
2. Trouve les recettes correspondantes
3. Applique automatiquement les assets CSS/JS a la reponse

#### `findRecettes($request = array(), array $context = array())`

Trouve les recettes correspondant aux parametres.

- **$request** : Objet sfWebRequest ou tableau de parametres
- **$context** : Contexte additionnel (optionnel)
- **Retour** : Tableau de recettes

#### `applyRecettesToResponse(sfWebResponse $response, array $recettes, $webRoot = '/desastres', $fsRoot = null)`

Applique les recettes a une reponse Symfony.

- **$response** : Objet reponse Symfony
- **$recettes** : Recettes a appliquer
- **$webRoot** : Chemin web racine
- **$fsRoot** : Chemin systeme de fichiers racine

### sfDesastreRuleEngine

#### `__construct(array $query = array(), array $context = array())`

Crée un moteur de règles.

- **$query** : Paramètres de requête
- **$context** : Contexte (date par défaut)

#### `evaluate($expression)`

Évalue une expression de règle.

- **$expression** : Expression à évaluer
- **Retour** : Boolean

## Exemples avancés

### Désastre de Noël avec probabilité

```yaml
regles:
  - query: "context.date.month == '12' && context.date.day >= '20'"
    recettes: [noel_snow, noel_lights]
    probability: 0.7  # 70% de chances
```

### Désastre par plage horaire

```yaml
regles:
  - query: "context.date.hour >= '22' || context.date.hour <= '6'"
    recettes: [night_mode]
```

### Désastre basé sur plusieurs critères

```yaml
regles:
  - query: "query.contributor ~ /.*john.*/i && query.title ~ /.*rock.*/i && context.date.weekday >= '5'"
    recettes: [weekend_rock_special]
```

## Tests

Pour tester le moteur de règles :

```php
// Test simple
$engine = new sfDesastreRuleEngine(
  array('title' => 'Love Song'),
  array('date' => array('day' => '14', 'month' => '2'))
);

$result = $engine->evaluate("query.title ~ /.*love.*/i");
var_dump($result); // bool(true)

// Test avec date
$result = $engine->evaluate("context.date.day == '14' && context.date.month == '2'");
var_dump($result); // bool(true)
```

## Licence

Ce plugin est développé pour Musique Approximative.

## Support

Pour toute question ou problème, contactez l'équipe de développement.
