## ADDED Requirements

### Requirement: Les recettes servent leurs bibliothèques depuis le site

Une recette de désastre SHALL charger ses bibliothèques depuis le site, et non depuis un
CDN, aux mêmes conditions que le reste des ressources statiques du projet.

Le comportement du désastre NE SHALL PAS changer : la bascule porte sur la provenance du
fichier, pas sur ce qu'il fait. Une recette qui animait une page continue de l'animer, à
l'identique.

Le tirage, les règles, les probabilités et la mise en cache NE SHALL PAS être modifiés par
cette bascule.

#### Scenario: une recette qui charge une bibliothèque

- **GIVEN** une recette déclarant une bibliothèque JavaScript
- **WHEN** un désastre l'applique à une page
- **THEN** la bibliothèque est servie depuis le site
- **AND** l'effet visuel obtenu est celui qu'elle produisait auparavant

#### Scenario: l'invariance et le tirage sont préservés

- **GIVEN** une page dont une recette a été tirée
- **WHEN** elle est servie plusieurs fois depuis la même représentation
- **THEN** le désastre appliqué reste le même
- **AND** l'en-tête qui le nomme reste le même
