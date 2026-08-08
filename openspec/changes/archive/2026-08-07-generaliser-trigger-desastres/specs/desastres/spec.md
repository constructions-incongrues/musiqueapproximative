## ADDED Requirements

### Requirement: Forçage d'une règle

Toute règle de désastre SHALL être déclenchable depuis l'URL par un paramètre qui lui est
propre, et ce forçage SHALL ignorer aussi bien la condition de la règle que sa
probabilité.

#### Scénario : Règle forcée par son paramètre

- **QUAND** une demande de page porte le paramètre de déclenchement d'une règle
- **ALORS** les recettes de cette règle sont appliquées à la réponse
- **ET** ce, que la condition de la règle soit satisfaite ou non
- **ET** quelle que soit la probabilité qu'elle déclare

#### Scénario : Paramètre présent sans valeur

- **QUAND** le paramètre de déclenchement figure dans l'URL sans valeur, ou avec une
  valeur quelconque
- **ALORS** la règle est déclenchée dans les deux cas, la seule présence du paramètre
  valant déclenchement

#### Scénario : Absence du paramètre

- **QUAND** une demande de page ne porte aucun paramètre de déclenchement
- **ALORS** chaque règle est évaluée par sa condition puis par sa probabilité, comme si
  le mécanisme de forçage n'existait pas

#### Scénario : Forçage de plusieurs règles

- **QUAND** une demande porte les paramètres de déclenchement de plusieurs règles
- **ALORS** les recettes de chacune sont appliquées à la réponse

#### Scénario : Recette désactivée malgré le forçage

- **QUAND** une règle est forcée mais que l'une de ses recettes est marquée désactivée en
  configuration
- **ALORS** cette recette n'est pas appliquée
- **ET** le forçage porte sur la sélection de la règle, jamais sur l'activation d'une
  recette

### Requirement: Couverture des déclencheurs

Chaque règle déclarée dans la configuration SHALL porter un paramètre de déclenchement, de
sorte qu'aucun désastre ne soit observable seulement par tirage.

#### Scénario : Règle sans déclencheur

- **QUAND** la configuration comporte une règle qui ne déclare aucun paramètre de
  déclenchement
- **ALORS** cette règle est signalée comme non conforme à la configuration attendue

#### Scénario : Unicité des déclencheurs

- **QUAND** deux règles déclarent le même paramètre de déclenchement
- **ALORS** ce paramètre les force toutes les deux, et cette ambiguïté est signalée
