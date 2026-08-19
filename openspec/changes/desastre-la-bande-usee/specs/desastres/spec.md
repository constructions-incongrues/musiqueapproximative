## ADDED Requirements

### Requirement: Le désastre de la bande usée

Feature: la bande usée
Rule: le site s'appelle *Musique Approximative*. Ce désastre prend ce nom au mot plutôt
      que d'illustrer un mot du titre du morceau, comme le font douze des dix-neuf autres.

La hauteur du son SHALL flotter pendant la lecture : une ondulation lente et un tremblement
plus rapide, superposés, obtenus par une ligne à retard modulée.

L'effet SHALL être perceptible sans être identifiable au premier instant. Un désastre qui
s'annonce comme un effet n'en est plus un ; un désastre qu'on ne remarque jamais non plus.

L'intensité SHALL être fixe pour cette version. La lier à quoi que ce soit — l'âge du
morceau, le nombre d'écoutes — relève d'autres changements.

La probabilité de déclenchement SHALL être basse. Ce désastre dégrade la musique qu'un
contributeur a choisi de partager, ce qui n'est pas du même ordre que défigurer une page.

Le tirage, son invariance et l'en-tête qui le nomme NE SHALL PAS être modifiés : ce
désastre se déclare comme les autres.

#### Scenario: la règle retient la recette

- **GIVEN** une page dont la règle de la bande usée est satisfaite
- **WHEN** la page est produite
- **THEN** le désastre est appliqué
- **AND** l'en-tête de réponse le nomme comme n'importe quel autre

#### Scenario: le désastre est forcé pour essai

- **GIVEN** l'adresse d'un morceau portant le paramètre de déclenchement de ce désastre
- **WHEN** la page est servie
- **THEN** le désastre est appliqué quelle que soit sa probabilité
- **AND** le relevé distingue cette application forcée d'un tirage
