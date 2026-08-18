## Context

La spec `desastres` compte 30 scénarios et zéro test. Ce changement en couvre neuf, ceux
qui portent sur le chargement de la configuration. Le reste — forçage, invariance sur le
cache, statistique — est écarté et motivé dans la proposition.

Ce document existe pour une seule raison : une couture n'est pas là où on l'attend, et un
test écrit sans le savoir conclurait à l'envers.

## Goals / Non-Goals

**But.** Couvrir les neuf scénarios de configuration par des tests unitaires Lime, dans le
conteneur existant, sans dépendance nouvelle.

**Non-buts.** Modifier `sfDesastrePlugin`. Toucher à la configuration des environnements.
Couvrir les 21 autres scénarios.

## Decisions

### Le scénario « Fichier de configuration manquant » vise le helper, pas le manager

C'est la décision qui justifie ce document.

La spec dit : « QUAND le fichier de configuration des désastres n'existe pas, ALORS aucune
altération n'est appliquée et la page est servie normalement. »

Or `sfDesastreManager::loadConfig()` fait l'inverse :

```php
if (!file_exists($configPath)) {
    throw new sfException(...);
}
```

La garantie de la spec est tenue un niveau plus haut, dans `apply_desastre()` :

```php
if (!file_exists($configPath)) {
    return;
}
$manager = new sfDesastreManager($configPath);
```

Un test qui viserait `sfDesastreManager` constaterait l'exception et conclurait à une
violation de la spec. Il aurait tort : la spec parle de ce que voit le visiteur, et le
visiteur passe par le helper.

**Retenu** : ce scénario teste `apply_desastre()`. Les huit autres testent
`sfDesastreManager`, qui expose les coutures nécessaires — constructeur acceptant une
configuration, `loadConfig()`, `getConfig()`, `getUnresolvedImports()`, `findRecettes()`.

**Correction apportée à l'implémentation.** Cette décision était trop absolue. Le
constructeur garde lui aussi :

```php
if (is_string($config) && file_exists($config)) { $this->loadConfig($config); }
else { $this->config = array('regles' => array(), 'recettes' => array()); }
```

`new sfDesastreManager('/chemin/absent')` ne lève donc pas : il retombe sur une
configuration vide. Seul un appel direct à `loadConfig()` lève. Le test couvre les trois
faits — le constructeur est sûr, `loadConfig()` lève, le helper garde — plutôt que le seul
qui était prévu.

### Les classes du plugin sont chargées par `require_once`

`src/config/autoload.yml` n'existe pas : l'autochargement des tests unitaires ne couvre que
symfony. Les tests du dépôt résolvent déjà cela par un `require_once` explicite, comme
`JsonApiFilterTest` et `SubsonicIdTest`. Ce changement suit le même motif plutôt que
d'introduire un `autoload.yml`, qui affecterait toute la suite pour le bénéfice d'un
fichier.

### Les fixtures sont des fichiers, pas des tableaux en mémoire

Cinq des neuf scénarios portent sur la résolution de chemins d'import. Ils ne peuvent pas
être exercés par une configuration passée en tableau : c'est `file_exists()` sur des
chemins relatifs à `dirname($configPath)` qui est en cause. Les fixtures sont donc de vrais
fichiers YAML sous `src/test/fixtures/desastres/`.

## Risks / Trade-offs

- **Les tests figeront le comportement actuel, pas le comportement voulu.** Si l'un des
  neuf scénarios ne passe pas, la question sera de savoir si le code est fautif ou si la
  spec décrit une intention jamais réalisée. Le trancher au cas par cas, sans ajuster le
  test pour qu'il passe.
- **Neuf scénarios sur trente peut donner un faux sentiment de couverture.** La spec
  restera majoritairement non testée, et les deux bugs archivés de cette zone relevaient de
  la partie non couverte. La proposition le dit ; les tâches le rappellent.

## Open Questions

- ~~`findRecettes()` dédoublonne-t-il une recette désignée par plusieurs règles
  satisfaites ?~~ **Répondu par l'implémentation : non.** Ni `findRecettes()`, qui empile
  par `$selectedRecettes[] = $recette` sans contrôle, ni `applyRecettesToResponse()`, qui
  itère sans mémoire de ce qui a déjà été injecté. Le test le démontre : la même recette
  ressort deux fois. Deux exigences de la spec `desastres` décrivent donc un comportement
  que le code n'a pas — voir la section « Écarts constatés » des tâches.
