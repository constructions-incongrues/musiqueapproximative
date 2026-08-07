## ADDED Requirements

### Requirement: Invalidation du cache des ressources statiques

Les adresses des ressources statiques SHALL porter un marqueur de version, afin qu'une
publication remette à jour ce que les visiteurs de retour ont en cache. Ce marqueur SHALL
être la version du projet, lue dans `src/VERSION`.

Le marqueur ne change qu'à la publication. Une ressource modifiée entre deux publications
SHALL donc être servie sous une adresse inchangée, et l'invalidation n'a pas lieu — c'est
une limite du dispositif, non un défaut de son fonctionnement.

#### Scénario : Marqueur porté par les ressources

- **QUAND** une page est servie
- **ALORS** les adresses de ses feuilles de style, scripts et images portent un paramètre
  `v` valant la version du projet

#### Scénario : Version absente ou illisible

- **QUAND** le fichier de version est absent
- **ALORS** le marqueur vaut `dev`
- **ET** la page reste servie normalement

#### Scénario : Publication d'une version

- **QUAND** une version est publiée et mise en ligne
- **ALORS** le marqueur porté par les ressources devient celui de cette version
- **ET** les visiteurs de retour demandent des adresses qu'ils n'ont pas en cache

#### Scénario : Ressource modifiée hors publication

- **QUAND** une ressource statique est modifiée et mise en ligne sans qu'une version soit
  publiée
- **ALORS** son adresse reste inchangée
- **ET** un visiteur qui l'a déjà en cache continue de recevoir l'ancienne, jusqu'à
  expiration de son propre cache
- **ET** le dispositif ne protège donc pas contre ce cas
