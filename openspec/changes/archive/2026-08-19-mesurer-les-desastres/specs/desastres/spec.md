## ADDED Requirements

### Requirement: La réponse nomme le désastre appliqué

La réponse SHALL porter un en-tête nommant la recette appliquée, et l'absence de désastre
SHALL être distinguable d'un désastre appliqué.

Aujourd'hui la recette n'est lisible qu'en cherchant son nom dans le corps HTML — mesuré
sur une page où une règle certaine se déclenche, le nom y apparaît cinq fois, au milieu de
la charge utile injectée. C'est un indice, pas une déclaration : il dépend du contenu de la
recette, il disparaît si la recette n'injecte rien, et il oblige à télécharger et analyser
le document pour savoir ce qui s'est passé.

L'en-tête SHALL être servi avec la représentation mise en cache, comme le reste de la
réponse : il décrit la page produite, et deux consultations de la même représentation
portent donc le même en-tête. C'est la même propriété que l'invariance du désastre
lui-même, et non une exception.

L'en-tête NE SHALL PAS modifier le tirage, ni les règles, ni la durée de vie du cache.

#### Scenario: une page portant un désastre

- **GIVEN** une page dont une règle a retenu une recette
- **WHEN** elle est servie
- **THEN** la réponse porte un en-tête nommant cette recette

#### Scenario: une page sans désastre

- **GIVEN** une page dont aucune règle n'a retenu de recette
- **WHEN** elle est servie
- **THEN** la réponse le déclare, plutôt que d'omettre l'en-tête sans distinction

#### Scenario: l'invariance est préservée

- **GIVEN** une page dont le désastre a été tiré et mise en cache
- **WHEN** elle est servie plusieurs fois
- **THEN** l'en-tête porte la même recette à chaque fois
- **AND** le corps du document reste identique
