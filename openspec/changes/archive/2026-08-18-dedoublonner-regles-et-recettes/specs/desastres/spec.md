## MODIFIED Requirements

### Requirement: Unicité des règles

Le système SHALL évaluer chaque règle déclarée une fois et une seule, la probabilité
annoncée par une règle valant pour l'ensemble de la configuration et non par fichier.

#### Scénario : Règle déclarée dans deux fichiers importés

- **QUAND** une même règle — condition, probabilité, recettes et déclencheur identiques —
  est déclarée dans deux fichiers importés
- **ALORS** sa probabilité effective de déclenchement est celle qu'elle annonce
- **ET** non le résultat cumulé de plusieurs tirages indépendants

#### Scénario : Règles distinguées par leur déclencheur

- **QUAND** deux règles ne diffèrent que par leur paramètre de déclenchement
- **ALORS** elles restent deux règles
- **ET** chacune conserve son déclencheur, aucun désastre ne devenant observable seulement
  par tirage du fait de ce rapprochement

#### Scénario : Recette sélectionnée plusieurs fois

- **QUAND** plusieurs règles satisfaites désignent la même recette
- **ALORS** ses ressources ne sont injectées qu'une fois dans la réponse
- **ET** ses options ne sont transmises qu'une fois au désastre correspondant

#### Scénario : Rang de la recette retenue

- **QUAND** une recette est désignée par plusieurs règles satisfaites
- **ALORS** elle occupe le rang de la première règle qui la désigne
- **ET** l'ordre des autres recettes est inchangé
