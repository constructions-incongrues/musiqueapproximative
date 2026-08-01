## Why

Le point d'entrée `/oembed` gère `json` et `xml`, et rien d'autre. Toute autre valeur du
paramètre `format` tombe dans un angle mort : aucune donnée n'est encodée, la réponse
part vide, avec un code 200 et un type de contenu hérité. Le consommateur ne peut ni
exploiter la réponse ni comprendre pourquoi.

La spécification oEmbed prévoit ce cas : un fournisseur qui ne sait pas produire le
format demandé répond `501 Not Implemented`. Elle prévoit aussi un type de contenu dédié,
`application/json+oembed`, que le code déclarait avant qu'il ne soit mis en commentaire.

Le défaut est consigné dans `openspec/specs/embarquement-oembed/spec.md`, et ce changement
le corrige.

## What Changes

- Un `format` inconnu produit une réponse `501 Not Implemented` au lieu d'une réponse
  vide en 200, conformément à la spécification oEmbed.
- La réponse JSON est servie en `application/json+oembed`, le type que la spécification
  associe à ce format.
- La comparaison du paramètre `format` devient insensible à la casse et tolère les
  espaces de bordure, `JSON` et `json` désignant le même format.
- **BREAKING** pour un consommateur qui filtrerait sur le type de contenu exact
  `application/json` de la réponse oEmbed. Le risque est faible — les consommateurs
  oEmbed lisent le corps — mais il est réel, et c'est le seul point de ce changement qui
  mérite un avis avant fusion.

### Hors périmètre

- Le lien de découverte `<link rel="alternate" type="application/json+oembed">` dans le
  gabarit de page, absent aujourd'hui. C'est un ajout de fonctionnalité, pas une remise
  en cohérence.
- Les paramètres `maxwidth` et `maxheight` de la spécification oEmbed, non gérés
  aujourd'hui et hors sujet ici.
- Le contenu de la réponse oEmbed — champs, dimensions, fragment embarquable — inchangé.
- Le type de contenu `text/xml+oembed` de la réponse XML, déjà conforme.
- Les deux autres défauts consignés lors de la spécification du contrat public : le
  gabarit `max` d'un morceau isolé et la lecture intégrale des fichiers audio par le
  flux. Chacun a son propre changement.

## Capacités

### Nouvelles capacités

Aucune.

### Capacités modifiées

- `embarquement-oembed` : l'exigence « Négociation du format oEmbed » cesse de décrire
  une réponse vide sur format inconnu et décrit un refus explicite, et le type de contenu
  de la réponse JSON change.

## Impact

- `src/apps/frontend/modules/post/actions/actions.class.php`, méthode `executeOembed()`,
  au niveau de la sélection de format et de la déclaration du type de contenu.
- Contrat public **touché** : code de statut sur format inconnu, et type de contenu de la
  réponse JSON.
- Les routes, le contenu de la réponse et le gabarit d'embarquement sont inchangés.
- Aucune dépendance ajoutée, aucune migration, aucun changement de configuration.
