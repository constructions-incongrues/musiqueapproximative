## Why

Le format `max` est annoncé comme disponible par la sélection de format, mais le gabarit
qui devrait le produire pour un morceau isolé ne contient que le mot `TODO`. Un
consommateur Max/MSP qui demande `/post/:slug?format=max` reçoit ce mot, sans erreur ni
indication. Le défaut est consigné dans `openspec/specs/formats-de-sortie/spec.md`, et ce
changement le corrige.

## What Changes

- Le gabarit `max` d'un morceau isolé produit désormais la même ligne que celle qu'une
  liste produit pour ce morceau : rang, artiste, titre, adresse du fichier audio, adresse
  de la page, contributeur, nombre total de morceaux, corps du post.
- Le rang vaut `0` et le nombre total vaut `1`, un morceau isolé étant traité comme une
  liste d'un seul élément — c'est déjà la convention retenue par le format `json`, dont
  le gabarit enveloppe une ressource unique dans une liste.
- Les guillemets et retours à la ligne sont retirés des champs textuels, comme dans la
  représentation d'une liste : ce sont les caractères qui casseraient l'analyse côté
  Max/MSP.
- Aucun changement pour la représentation `max` d'une liste, ni pour aucun autre format.

### Hors périmètre

- La représentation `max` d'une liste, qui fonctionne et n'est pas touchée.
- L'absence d'annonce du format `max` au visiteur : c'est un choix délibéré de la
  sélection de format, pas un défaut.
- Les deux autres défauts consignés lors de la spécification du contrat public : la
  lecture intégrale des fichiers audio par le flux, et la négociation de format
  d'`/oembed`. Chacun a son propre changement.
- La duplication de la construction de ligne entre le gabarit de liste et celui d'un
  morceau isolé. La factoriser toucherait la représentation d'une liste, hors périmètre.

## Capacités

### Nouvelles capacités

Aucune.

### Capacités modifiées

- `formats-de-sortie` : l'exigence « Représentation Max/MSP d'un morceau isolé » cesse de
  décrire un format non implémenté et décrit la ligne effectivement produite.

## Impact

- `src/apps/frontend/modules/post/templates/showSuccess.max.php`, dont c'est le seul
  contenu.
- Contrat public **touché** : la réponse de `/post/:slug?format=max` passe du mot `TODO`
  à une ligne exploitable. Aucun consommateur ne peut dépendre du comportement actuel.
- Le type de contenu `application/maxmsp+text` et la sélection de format sont inchangés.
- Aucune dépendance ajoutée, aucune migration, aucun changement de configuration.
