## Why

Deux exigences de la spec `desastres` décrivent un comportement que le code n'a pas. Les
tests unitaires du changement `couvrir-la-configuration-des-desastres` les ont mises au
jour et les portent aujourd'hui en `todo()`, faute de mandat pour les corriger.

**Une règle déclarée dans deux fichiers importés est chargée deux fois.**
`sfDesastreManager::processImports()` fusionne les fichiers par `array_merge()`, qui
concatène deux tableaux à clés numériques. La règle est donc évaluée deux fois. Avec une
probabilité inférieure à 1 — et toutes les règles du site déclarent `probability: 0.7` —
sa probabilité effective devient le cumul de deux tirages indépendants : 0,91 au lieu de
0,7. La spec dit l'inverse, sous « Requirement: Unicité des règles ».

**Une recette désignée par plusieurs règles satisfaites est appliquée plusieurs fois.**
`findRecettes()` empile par `$selectedRecettes[] = $recette` sans contrôle, et
`applyRecettesToResponse()` itère sans mémoire de ce qui a déjà été injecté. Le test le
démontre : `findRecettes()` retourne `premiere, premiere`. La spec dit que « ses ressources
ne sont injectées qu'une fois dans la réponse ».

**Ce que les deux écarts coûtent réellement, mesuré et non supposé.** Une première rédaction
de cette proposition affirmait qu'ils produisaient une réponse portant deux fois les mêmes
ressources. C'est faux, et la vérification l'a montré sur la configuration de production :

```
avant   findRecettes() -> quickos, quickos     scripts injectés : 1
après   findRecettes() -> quickos              scripts injectés : 1
```

`sfWebResponse` indexe feuilles et scripts par chemin, et `applyRecettesToResponse()`
accumule les options dans un tableau indexé par nom de désastre avant un unique appel à
`injectDesastreOptions()`. La duplication d'une recette n'a donc **aucun effet observable
sur la réponse servie**.

Ce qui reste, et qui justifie ce changement :

- **Le bug de probabilité est réel.** Une règle déclarée deux fois subit deux tirages
  indépendants. Toutes les règles du site déclarent `probability: 0.7` : la probabilité
  effective devient 0,91. C'est une violation directe de l'exigence « Unicité des règles »,
  et elle n'est constatable que statistiquement — donc jamais en pratique.
- **`findRecettes()` qui retourne des doublons est un piège latent.** Ses deux seuls
  appelants aujourd'hui, `applyRecettesToResponse()` et les tests, indexent ou comparent
  par nom. Le premier qui ne le fera pas dupliquera pour de bon.
- **`findAssets()` balaie le disque une fois par occurrence**, pour un résultat identique.

Le cas n'est pas théorique. `desastres.yml` importe sept fichiers de règles, et la
configuration livrée contient déjà un recouvrement : les 24 et 25 décembre, deux règles
satisfaites désignent la recette `quickos`, une redirection vers le site de Noël. Sans
conséquence visible, mais ce n'était pas l'intention.

## What Changes

- `processImports()` ne charge qu'une occurrence d'une règle identique, quel que soit le
  nombre de fichiers qui la déclarent.
- `findRecettes()` ne retourne qu'une occurrence d'une recette, quel que soit le nombre de
  règles satisfaites qui la désignent.
- La spec `desastres` gagne un scénario fixant le **rang** d'une recette dédoublonnée,
  seule garantie réellement nouvelle et vérifiable qu'apporte ce changement.
- Les deux assertions en `todo()` de `DesastreConfigTest` deviennent des assertions
  franches.

## Hors périmètre

- **L'ordre d'application des recettes.** Dédoublonner impose de choisir laquelle des deux
  occurrences est retenue. Ce changement retient la première, conformément à l'exigence
  existante « l'ordre de déclaration détermine l'ordre d'évaluation ». Il ne réexamine pas
  cet ordre.
- **La définition de « règle identique ».** Ce changement compare condition, probabilité et
  recettes, ce que la spec nomme déjà. Il n'introduit pas d'identifiant de règle.
- **Les 21 autres scénarios de `desastres`** non couverts par des tests. Le forçage,
  l'invariance sur le cache et les scénarios statistiques restent hors couverture.
- **`cache: false` dans l'environnement de test.** Toujours le point le plus lourd de cette
  zone, toujours son propre changement.

## Capabilities

### New Capabilities

Aucune.

### Modified Capabilities

- `desastres` : l'exigence « Unicité des règles » gagne un scénario fixant le rang qu'occupe
  une recette désignée par plusieurs règles satisfaites.

## Impact

- **Modifié** : `src/plugins/sfDesastrePlugin/lib/sfDesastreManager.class.php`
  (`processImports`, `findRecettes`).
- **Modifié** : `src/test/unit/plugins/DesastreConfigTest.php`, dont deux `todo()`
  deviennent des assertions.
- **Contrat public** : pas concerné. Vérifié sur la configuration de production : la
  réponse servie est identique avant et après, aux mêmes ressources et aux mêmes options.
  Seule change la probabilité effective d'une règle qui serait déclarée deux fois, ce
  qu'aucune configuration livrée ne fait aujourd'hui.
- **Risque de régression** : aucune recette livrée ne dépend d'être appliquée deux fois —
  vérifié en tâche 4.1 sur les vingt recettes du dépôt.
