## Why

`src/apps/frontend/config/settings.yml` déclare `cache: false` pour l'environnement `test`.
La suite fonctionnelle ne voit donc jamais une réponse servie depuis le cache — elle voit
toujours le premier visiteur.

Or c'est exactement là que les bugs de cette zone se produisent. Deux changements archivés
le disent, et le premier dans ses propres termes :

> « C'est vrai pour un visiteur sur des milliers. Pour tous les autres, les fichiers du
> désastre se chargent et le désastre ne s'applique pas — silencieusement. »

`sfDesastreFilter` injectait ses options **après** que `sfCacheFilter` avait écrit l'entrée.
Le second changement, `reparer-format-xspf` mis à part, portait sur le même ordre de
filtres : le `Content-Type` réécrit après l'écriture du cache, donc une réponse mise en
cache portant le type d'origine.

Aucun de ces deux bugs n'est observable avec `cache: false`. La suite est structurellement
aveugle à la seule classe de faute qui a réellement frappé cette zone — quel que soit le
formalisme de test employé.

Six scénarios de la spec `desastres` décrivent par ailleurs un comportement qui **n'a pas
lieu** dans l'environnement de test : « toutes les réponses portent le même résultat de
tirage », « deux visiteurs reçoivent les mêmes recettes ». Avec le cache éteint, chaque
requête reproduit la page et retire au sort. Ces scénarios ne sont pas seulement non
testés : ils sont intestables tant que ce réglage ne change pas.

## What Changes

- `settings.yml` passe `cache: false` à `cache: true` pour l'environnement `test`. Une
  ligne.
- Un test fonctionnel vérifie qu'une réponse servie depuis le cache porte les options du
  désastre, c'est-à-dire que le bug de `reparer-injection-des-options` ne peut pas revenir.
- La documentation des tests dit que l'environnement `test` met en cache, et ce que cela
  implique quand un fichier de test demande deux fois la même adresse.

Mesuré avant d'écrire ces lignes : la suite complète passe à l'identique avec le cache
allumé — 14 scripts, 441 assertions, le seul échec étant `getID3`, préexistant et étranger
à ce sujet.

## Hors périmètre

- **Un environnement `test_cache` dédié.** Étudié et écarté : il fonctionne, mais impose de
  dupliquer trois blocs de configuration — `settings.yml`, `factories.yml` pour le stockage
  de session de test, `databases.yml-dist` pour le garde-fou des fixtures. `factories.yml`
  n'a pas d'héritage entre environnements : la copie divergerait.
- **Les six scénarios d'invariance de `desastres`.** Ce changement les rend testables ; il
  n'en écrit qu'un. Les cinq autres relèvent d'un travail de couverture à part.
- **Les deux scénarios statistiques.** Le cache ne les concerne pas.
- **L'environnement de test de l'application `admin`.** Ce changement ne touche que
  `frontend`.

## Capabilities

### New Capabilities

Aucune.

### Modified Capabilities

Aucune. Le comportement du site ne change pas : seul l'environnement de test est
concerné. Le changement pose donc `skip_specs: true`.

## Impact

- **Modifié** : `src/apps/frontend/config/settings.yml`, une ligne.
- **Ajouté** : un test fonctionnel sous `src/test/functional/frontend/`.
- **Modifié** : `docs/modules/ROOT/pages/developpement/tests.adoc`, pour dire que
  l'environnement de test met en cache.
- **Contrat public** : pas concerné. Aucun environnement servi aux visiteurs ne change.
- **Piège introduit** : dans un même fichier de test, deux demandes de la même adresse
  renvoient désormais la réponse mise en cache. Le bootstrap fonctionnel vide le répertoire
  de cache à chaque fichier, donc l'isolation entre fichiers reste acquise. Aucun test
  actuel n'en dépend — vérifié en lançant la suite.
