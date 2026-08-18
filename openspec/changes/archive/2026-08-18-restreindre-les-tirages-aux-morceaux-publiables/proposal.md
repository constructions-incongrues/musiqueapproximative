## Why

`PostTable::WHERE_ONLINE` définit un morceau publiable :
`is_online = 1 AND publish_on <= … AND slug IS NOT NULL AND slug != ''`.

Six méthodes réécrivent cette condition **à la main**, en omettant la clause de slug.
`/posts/random` peut donc servir `/post/` — une page morte, sur le chemin du bouton
aléatoire et du raccourci `r`.

Le scénario « Morceau aléatoire : le morceau tiré est publiable » est aujourd'hui marqué
**non vérifié** dans `catalogueEtNavigationTest.php`. Il ne l'est pas parce que le test
manque : il l'est parce que le site ne tient pas la promesse.

## Ce que le relevé montre, et qui vaut d'être dit

Un seul morceau est atteignable à tort sur les 8 098 publiables — et c'est **`????? — ??????`**,
publié le 28 septembre 2009. Un titre cyrillique détruit par l'encodage `latin1`.

Son slug est vide parce que `Sluggable` n'a rien pu construire à partir de points
d'interrogation. Les deux défauts s'enchaînent : l'encodage détruit le titre, l'absence de
titre empêche le slug, l'absence de slug fait servir une page morte. La migration Unicode
tarit la source ; elle ne répare pas cette entrée.

## What Changes

- Les six méthodes concernées utilisent `WHERE_ONLINE` au lieu de réécrire :
  `getLastPost`, `getOnlinePostById`, `getNextPost`, `getPreviousPost`, `getRandomPost`,
  `getByMd5Sum`.
- Le scénario passe de **non vérifié** à vérifié dans le test.

`getOnlinePostBySlug` n'est pas concernée : elle compare le slug à une valeur donnée, donc
un slug vide ne peut pas correspondre.

Le contrat public est concerné : **un morceau sans identifiant d'URL cesse d'être
atteignable** par ces routes. C'est ce que la spécification exige déjà.

## Capabilities

### New Capabilities

Aucune.

### Modified Capabilities

- `catalogue-morceaux` : l'exigence « Définition d'un morceau publiable » décrit ce qui rend
  un morceau publiable et se tait sur le fait que cette définition vaut pour **toutes** les
  façons de l'atteindre. C'est ce silence qui a permis à six requêtes d'en avoir une autre.

## Hors périmètre

- **Le morceau `523` lui-même**, dont le titre est détruit. Lui rendre un slug demanderait
  d'inventer un titre. Il relève de la story 20.
- **Les requêtes qui interpolent déjà `WHERE_ONLINE`**, correctes.
- **`buildOnlinePostsQuery`**, qui l'emploie déjà.

## Impact

- **Modifié** : `src/lib/model/doctrine/PostTable.class.php` — six conditions,
  `src/test/functional/frontend/catalogueEtNavigationTest.php` — un `skip` qui devient une
  assertion.
- **Dépendances** : aucune.
