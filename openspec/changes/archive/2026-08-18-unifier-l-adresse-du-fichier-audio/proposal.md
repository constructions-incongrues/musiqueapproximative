## Why

Le même fichier audio est servi sous deux adresses différentes selon la représentation
demandée. Trouvé en écrivant la couverture de `formats-de-sortie`, dont un scénario
l'interdit déjà : « les deux lignes portent les mêmes champs […] seuls le rang et le nombre
total de morceaux peuvent différer ».

```
/posts?format=max            http://localhost:8080/tracks/solo.mp3
/post/{slug}?format=max      http://localhost/tracks/un titre.mp3
```

Deux défauts, dont un sérieux :

1. **L'espace du nom de fichier ressort brut.** `showSuccess.max.php` construit l'adresse à
   la main sans encoder. L'URL est cassée. Le dépôt a pourtant déjà établi cette exigence
   ailleurs : `postActionsTest` vérifie que les pièces jointes du flux encodent l'espace
   en `%20`.
2. **`app_urls_tracks` est ignoré par trois gabarits** — le `max` d'un morceau isolé et les
   deux gabarits XSPF — qui prennent l'hôte de la requête. En production les deux
   coïncident, ce qui explique que personne ne l'ait vu ; ils divergent dès que les fichiers
   sont servis ailleurs que le site, ce qui est la raison d'être de ce réglage.

## What Changes

- Les trois gabarits construisent désormais l'adresse comme les autres, par
  `Post::getTrackUrl()` : domaine configuré, nom de fichier encodé.
- L'adresse servie par le `max` d'un morceau isolé cesse de différer de celle servie par la
  liste pour ce même morceau.
- `app_urls_tracks` est honoré partout, donc réellement configurable.

Le contrat public est concerné : **l'adresse du fichier audio change dans les
représentations `max` et `xspf`** — mais uniquement lorsque le domaine configuré diffère de
l'hôte de la requête, ce qui n'est pas le cas en production aujourd'hui. Ce qui change en
production, c'est l'encodage du `max` isolé, qui passe de cassé à correct.

## Capabilities

### New Capabilities

Aucune.

### Modified Capabilities

- `formats-de-sortie` : les représentations décrivent chacune « l'adresse du fichier audio »
  sans dire comment elle est construite. L'exigence gagne le fait qu'elle l'est **d'une
  seule façon**, quelle que soit la représentation et quelle que soit la route — ce qui est
  précisément ce que le silence de la spécification a permis de violer.

## Hors périmètre

- **Le flux de syndication et oEmbed**, qui construisent déjà l'adresse par le modèle.
- **La valeur de `app_urls_tracks`** dans les profils, qui ne change pas.
- **Le reste des divergences entre représentations.** Ce changement traite l'adresse du
  fichier ; la couverture en cours dira s'il y en a d'autres.

## Impact

- **Modifié** : `src/apps/frontend/modules/post/templates/showSuccess.max.php`,
  `listSuccess.xspf.php` et `showSuccess.xspf.php` — la variable `baseUrl` qu'ils passent
  au partiel devient inutile pour l'adresse du fichier.
- **Possiblement modifié** : `_xspfPlaylist.xspf.php`, si l'adresse cesse d'être construite
  depuis `$baseUrl`.
- **Non modifié** : `Post::getTrackUrl()` et `buildTrackUrl()`, qui font déjà ce qu'il faut
  et sont la référence.
- **Dépendances** : aucune.
