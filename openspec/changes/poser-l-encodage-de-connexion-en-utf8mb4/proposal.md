## Why

La conversion de la base en `utf8mb4` a eu lieu et sa vérification est passée : les tables
portent le bon jeu de caractères, les titres accentués sont identiques et leurs octets ont
changé comme attendu.

La connexion, elle, déclare toujours `UTF8` — non pas parce qu'on l'a demandé, mais parce
que `sfDoctrineDatabase` applique ce défaut quand rien n'est déclaré. `utf8` de MySQL tient
sur trois octets : il couvre tout le plan multilingue de base, mais **rejette les emoji avec
une erreur 1366**.

C'est la seconde moitié de la story 19, et la dernière chose qui sépare le site d'un support
complet.

## What Changes

- `encoding: utf8mb4` sur le bloc `all` de `databases.yml-dist`, comme sur le bloc `test`
  depuis la story 18.
- Rien d'autre.

## Pourquoi maintenant et pas avant

L'ordre était une contrainte, pas une préférence. Poser cet encodage avant la conversion
aurait envoyé de l'utf8mb4 vers des colonnes `latin1` — le mécanisme qui détruisait, en
pire, puisque la conversion aurait porté sur quatre octets au lieu de trois.

La conversion étant faite et vérifiée, la contrainte est levée.

## Capabilities

Aucune. Le comportement attendu est spécifié depuis la story 18 — `catalogue-morceaux`,
« Le morceau est restitué tel qu'il a été saisi ». `skip_specs` est déclaré en conséquence.

## Hors périmètre

- **Les 82 morceaux déjà détruits.** Aucune conversion ne les rend. C'est la story 20.
- **Le garde-fou à la saisie**, qui perd son objet une fois ce change livré.
- **La base de développement locale**, déjà en `utf8mb4` depuis la story 18.

## Impact

- **Modifié** : `src/config/databases.yml-dist`, bloc `all` — une ligne, plus son commentaire.
- **Non modifié** : tout le reste.
- **Effet immédiat au déploiement** : la fusion sur `main` met en ligne. À la différence de
  la livraison précédente, celle-ci agit sans geste manuel.
