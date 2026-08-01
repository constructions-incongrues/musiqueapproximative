## Why

<!-- Une ou deux phrases sur le problème. Qu'est-ce qui ne va pas aujourd'hui,
     et pourquoi le traiter maintenant ? Le titre reste en anglais : le parseur
     cherche cette section par son nom. Le contenu, lui, est en français. -->

## What Changes

<!-- Ce qui change, en liste. Sois précis sur les comportements ajoutés,
     modifiés ou retirés. Marque les ruptures de compatibilité par **BREAKING**.
     Titre en anglais pour la même raison que ci-dessus. -->

### Hors périmètre

<!-- Ce que ce changement ne fait délibérément pas, et pourquoi. Section
     obligatoire : sur du code legacy, la tentation de corriger « tant qu'on y
     est » est le premier facteur de dérive. -->

## Capacités

### Nouvelles capacités

<!-- Capacités introduites. Chacune donnera un specs/<nom>/spec.md.
     Nomme-les en kebab-case et en français. -->

- `<nom>` : <ce que cette capacité recouvre>

### Capacités modifiées

<!-- Capacités existantes dont les EXIGENCES changent — pas simplement
     l'implémentation. Reprends les noms présents dans openspec/specs/.
     Laisse vide si aucune exigence ne bouge. Un changement sans aucune capacité
     doit poser `skip_specs: true` dans son .openspec.yaml, sinon la validation
     le rejette. N'invente jamais une exigence pour satisfaire la validation. -->

- `<nom-existant>` : <quelle exigence change>

## Impact

<!-- Fichiers, routes, formats et systèmes touchés. Dis explicitement si le
     contrat public est concerné : routes, formats de sortie, flux, oEmbed,
     métadonnées. -->
