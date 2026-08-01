## Why

Pour déclarer la taille de la pièce jointe de chaque item du flux, le code charge
l'intégralité du fichier audio en mémoire et mesure la longueur de la chaîne obtenue.
L'opération est répétée pour chaque item et à chaque demande du flux : avec le volume par
défaut de cinquante morceaux, une seule requête sur `/posts/feed` lit cinquante fichiers
audio complets pour n'en retenir qu'un nombre d'octets que le système de fichiers connaît
déjà.

Le flux est consommé par des agrégateurs qui le sollicitent régulièrement. Le défaut est
consigné dans `openspec/specs/flux-syndication/spec.md`, et ce changement le corrige.

## What Changes

- La taille déclarée de la pièce jointe est obtenue auprès du système de fichiers plutôt
  qu'en lisant le fichier.
- Le repli à zéro pour un fichier illisible est conservé, ainsi que la vérification de
  lisibilité qui le précède.
- **Aucun changement de comportement observable** : la valeur déclarée est identique, le
  flux produit est identique octet pour octet.

### Hors périmètre

- La mise en cache du flux, qui réduirait le coût d'une autre manière mais change le
  comportement observable — fraîcheur des items, en-têtes de cache.
- Le calcul de l'illustration glitchée, réévalué lui aussi à chaque item.
- Le volume par défaut de cinquante items.
- Les deux autres défauts consignés lors de la spécification du contrat public : le
  gabarit `max` d'un morceau isolé et la négociation de format d'`/oembed`. Chacun a son
  propre changement.

## Capacités

### Nouvelles capacités

Aucune.

### Capacités modifiées

Aucune. Le comportement observable est strictement inchangé : c'est une correction de
performance, et une spec décrit un comportement, pas son coût. Le changement pose donc
`skip_specs: true` dans son `.openspec.yaml`.

La seule retouche au corpus est documentaire : la note de défaut qui accompagne
l'exigence « Contenu d'un item » dans `openspec/specs/flux-syndication/spec.md` n'aura
plus lieu d'être et doit être retirée. C'est une tâche, pas une exigence nouvelle.

## Impact

- `src/apps/frontend/modules/post/actions/actions.class.php`, méthode `executeFeed()`,
  au niveau du calcul de la taille du fichier joint.
- `openspec/specs/flux-syndication/spec.md`, pour le retrait de la note de défaut.
- Contrat public **inchangé** : mêmes items, mêmes tailles déclarées, même type de pièce
  jointe.
- Aucune dépendance ajoutée, aucune migration, aucun changement de configuration.
