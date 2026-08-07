## MODIFIED Requirements

### Requirement: Part d'aléatoire

Une règle SHALL pouvoir ne se déclencher qu'une fois sur plusieurs. Le tirage SHALL être
fait au moment où la page est produite, et son résultat SHALL valoir pour toutes les
consultations servies depuis la même représentation mise en cache.

Ce tirage porte sur les règles, donc sur les recettes appliquées. Il ne préjuge pas de ce
qu'une recette fait ensuite dans le navigateur : plusieurs tirent au sort à l'exécution, et
leur rendu SHALL pouvoir différer d'un visiteur à l'autre sur une même représentation.

#### Scénario : Règle probabiliste

- **QUAND** une règle déclare une probabilité inférieure à 1
- **ALORS** elle ne se déclenche qu'une partie du temps, pour un même contexte
- **ET** la proportion observée sur un grand nombre de productions de page tend vers la
  probabilité déclarée

#### Scénario : Règle certaine

- **QUAND** une règle déclare une probabilité de 1
- **ALORS** elle se déclenche à chaque fois que son expression est satisfaite

#### Scénario : Consultations successives d'une même adresse

- **QUAND** une même adresse est demandée plusieurs fois pendant la durée de vie du cache
- **ALORS** toutes les réponses portent le même résultat de tirage
- **ET** deux visiteurs différents reçoivent donc les mêmes recettes

#### Scénario : Aléatoire propre à une recette

- **QUAND** une recette tire au sort dans le navigateur — quelles lettres effacer, quels
  mots déplacer
- **ALORS** ce tirage est refait à chaque chargement, sans lien avec celui des règles
- **ET** deux consultations d'une même représentation mise en cache produisent le même
  désastre, joué différemment

#### Scénario : Observation d'une règle probabiliste

- **QUAND** on cherche à constater qu'une règle probabiliste se déclenche bien
- **ALORS** recharger la même adresse ne suffit pas
- **ET** il faut soit forcer la règle par son déclencheur, soit faire varier l'adresse pour
  provoquer autant de productions de page que de tirages souhaités

### Requirement: Granularité du tirage

Le hasard des désastres SHALL porter sur l'adresse demandée et sur le moment, et non sur le
visiteur.

#### Scénario : Deux adresses différentes

- **QUAND** deux adresses distinctes satisfont chacune une même règle probabiliste
- **ALORS** leurs tirages sont indépendants

#### Scénario : Une même adresse dans le temps

- **QUAND** la représentation en cache d'une adresse expire et que la page est produite à
  nouveau
- **ALORS** un nouveau tirage a lieu
- **ET** l'effet servi peut différer de celui de la période précédente

#### Scénario : Deux visiteurs sur la même adresse

- **QUAND** deux visiteurs demandent la même adresse pendant la même période de cache
- **ALORS** ils reçoivent les mêmes recettes, quel que soit leur navigateur ou leur session
- **ET** le rendu de ces recettes peut néanmoins différer, celles qui tirent au sort à
  l'exécution le refaisant à chaque chargement
