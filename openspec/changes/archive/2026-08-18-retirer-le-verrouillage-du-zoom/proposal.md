## Why

`layout.php` déclare `minimum-scale=1, maximum-scale=1` dans sa balise `viewport`. Sur
toutes les pages du site, le visiteur ne peut pas agrandir la page — au premier chef celui
qui en a besoin pour lire.

Cette règle n'a jamais été décidée comme une règle. Elle tient en deux déclarations, dans
une balise, et personne n'a eu à défendre l'idée que les visiteurs de ce site n'ont pas le
droit d'agrandir le texte.

Elle ne protège plus rien. Ce que `maximum-scale` évitait — la mise à l'échelle
automatique d'iOS à la mise au point d'un champ de saisie — est désormais garanti
autrement : le frontend n'a **qu'un seul champ**, celui de la recherche, et
`2026-08-18-fix-recherche-mobile` l'a porté à 16 px précisément pour cette raison.
`input.mailing`, l'autre champ que la règle couvrait, n'existe dans aucun gabarit — il n'en
reste que du CSS.

## What Changes

- Retrait de `minimum-scale=1, maximum-scale=1` de la balise `viewport`. Le reste de la
  balise — `width=device-width, initial-scale=1` — ne bouge pas.
- Le visiteur peut agrandir la page sur tous les terminaux, y compris ceux dont le
  navigateur appliquait encore la règle.
- Aucun autre effet. La mise en page ne change pas, la mise à l'échelle automatique reste
  évitée par la taille de police du champ.

Le contrat public n'est pas concerné : aucune route, aucun format, aucun en-tête. Le
changement porte sur le rendu d'une page HTML.

## Capabilities

### New Capabilities

- `acces-au-site` : ce que le site garantit à quiconque l'ouvre, indépendamment de ce
  qu'il vient y chercher — au premier rang, pouvoir lire ce qui y est écrit. Aucune
  capacité existante ne couvre ce terrain : `catalogue-morceaux` décrit ce qu'on trouve,
  `formats-de-sortie` comment on le récupère, `metadonnees-partage` comment on le partage.
  Aucune ne dit ce que le site doit à son visiteur avant tout contenu.

### Modified Capabilities

Aucune.

## Hors périmètre

- **Le CSS mort d'`input.mailing`.** Il n'est plus référencé par aucun gabarit. Le retirer
  est un nettoyage, sans rapport avec ce que cette règle fait aux visiteurs. Un change ne
  touche que ce qu'il annonce.
- **Le reste de la balise `viewport`** — `width=device-width, initial-scale=1` — qui est
  correct et le reste.
- **Toute autre question d'accessibilité** : contrastes, ordre de tabulation, textes de
  remplacement, structure des titres. Ce changement ne prétend pas rendre le site
  accessible ; il retire une règle qui empêchait un geste.
- **La mise en page mobile**, réglée par `2026-08-18-fix-recherche-mobile`.

## Impact

- **Modifié** : `src/apps/frontend/templates/layout.php`, ligne 7. Deux déclarations
  retirées d'un attribut.
- **Non modifié** : tout le reste. Aucun CSS, aucune action, aucun gabarit de module.
- **Dépendances** : aucune.
