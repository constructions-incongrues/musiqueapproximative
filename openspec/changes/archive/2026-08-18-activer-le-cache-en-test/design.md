## Context

Le réglage à changer tient en un mot. Ce document existe parce que le chemin pour y
arriver a écarté deux autres options, et que l'une d'elles semblait plus fine.

## Goals / Non-Goals

**But.** Que la suite fonctionnelle puisse observer une réponse servie depuis le cache.

**Non-buts.** Modifier le comportement du site. Couvrir les six scénarios d'invariance.
Toucher à l'application `admin`.

## Decisions

### Le réglage passe par l'environnement, pas par le code du test

Première intention : forcer `sfConfig::set('sf_cache', true)` dans le bootstrap fonctionnel,
sans toucher à `settings.yml`. Vérifié, et faux. `sfBrowser::getContext()` reconstruit la
configuration à chaque requête, puis prend son instantané :

```php
$configuration = ProjectConfiguration::getApplicationConfiguration(
    $currentConfiguration->getApplication(),
    $currentConfiguration->getEnvironment(),   // relit settings.yml → sf_cache = false
    ...);
...
$this->rawConfiguration = sfConfig::getAll();  // instantané pris APRÈS le rechargement
```

L'instantané capture la valeur relue, pas celle qu'on avait posée. Toute requête ultérieure
la restaure. Seul `settings.yml` est un levier.

### On allume le cache dans `test`, plutôt que de créer un `test_cache`

L'environnement dédié fonctionne — vérifié jusqu'à l'écriture du fichier de cache :

```
cache/frontend/test_cache/template/localhost/all/post/show/bleu/…/sigur-ros-rock-roll.cache
```

Il impose en revanche trois duplications : `settings.yml`, `factories.yml` pour
`sfSessionTestStorage`, et `databases.yml-dist`, sans quoi le garde-fou des fixtures refuse
une connexion qui ne pointe pas sur une base de test. `factories.yml` n'ayant pas
d'héritage entre environnements, son bloc `test_cache:` serait une copie mot pour mot de
`test:` — deux sources de vérité qui divergeront.

Allumer le cache dans `test` coûte une ligne, ne duplique rien, et rend l'ensemble de la
suite fonctionnelle existante sensible au cache plutôt qu'un seul test opt-in.

**Vérifié avant de retenir cette option** : la suite complète passe à l'identique, 14
scripts et 441 assertions, seul `getID3` échouant comme avant.

### L'isolation repose sur le vidage déjà fait par le bootstrap

`test/bootstrap/functional.php` se termine par
`sfToolkit::clearDirectory(sfConfig::get('sf_app_cache_dir'))`. Chaque fichier de test
démarre donc sur un cache vide. L'isolation entre fichiers est acquise sans rien ajouter.

À l'intérieur d'un fichier, en revanche, deux demandes de la même adresse renvoient
désormais la même réponse. C'est le comportement recherché ; c'est aussi un piège pour qui
l'ignore, d'où la ligne de documentation prévue en tâche.

## Risks / Trade-offs

- **Un futur test pourrait attendre une réponse fraîche à chaque demande.** Il obtiendra la
  réponse mise en cache et échouera d'une manière déroutante. La documentation le dit ; on
  ne peut pas faire mieux sans renoncer au bénéfice.
- **Le cache masque les erreurs de production de page.** Une page qui échouerait à la
  deuxième production ne sera pas produite deux fois. C'est le revers exact du bénéfice
  recherché, et il vaut moins que lui : les bugs constatés dans ce dépôt sont du côté du
  deuxième visiteur, pas du deuxième rendu.

## Open Questions

- L'application `admin` a son propre `settings.yml`. Faut-il l'aligner ? Aucun bug connu
  n'y touche au cache, et ses tests fonctionnels sont protégés par sfGuard. Laissé de côté
  faute de motif.
