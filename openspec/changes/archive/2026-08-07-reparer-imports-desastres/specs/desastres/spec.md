## ADDED Requirements

### Requirement: Résolution des imports

Le système SHALL charger l'intégralité des fichiers de règles et de recettes déclarés en
import, et SHALL rendre constatable tout import déclaré qui ne se résout pas, sans pour
autant cesser de servir la page.

#### Scénario : Tous les imports se résolvent

- **QUAND** chaque chemin déclaré sous `imports` désigne un fichier existant
- **ALORS** les règles et les recettes de tous ces fichiers participent à l'évaluation
- **ET** l'ordre de déclaration détermine l'ordre d'évaluation des règles

#### Scénario : Un import ne se résout pas

- **QUAND** un chemin déclaré sous `imports` ne désigne aucun fichier
- **ALORS** la page est servie normalement, avec les règles et recettes des imports valides
- **ET** l'import non résolu est consigné dans les journaux du serveur
- **ET** un avertissement nommant le chemin fautif est émis dans la console du navigateur,
  de sorte que la panne soit constatable sans accès au serveur

#### Scénario : Tous les imports valides

- **QUAND** chaque chemin déclaré sous `imports` se résout
- **ALORS** aucun avertissement n'est émis, ni dans les journaux, ni dans la console

#### Scénario : Configuration partiellement invalide

- **QUAND** la configuration principale existe mais qu'une partie de ses imports est
  introuvable
- **ALORS** le système ne se comporte pas comme si les règles manquantes n'avaient jamais
  été déclarées
- **ET** l'écart entre ce qui est déclaré et ce qui est chargé est constatable

### Requirement: Unicité des règles

Le système SHALL évaluer chaque règle déclarée une fois et une seule, la probabilité
annoncée par une règle valant pour l'ensemble de la configuration et non par fichier.

#### Scénario : Règle déclarée dans deux fichiers importés

- **QUAND** une même règle — condition, probabilité et recettes identiques — est déclarée
  dans deux fichiers importés
- **ALORS** sa probabilité effective de déclenchement est celle qu'elle annonce
- **ET** non le résultat cumulé de plusieurs tirages indépendants

#### Scénario : Recette sélectionnée plusieurs fois

- **QUAND** plusieurs règles satisfaites désignent la même recette
- **ALORS** ses ressources ne sont injectées qu'une fois dans la réponse
- **ET** ses options ne sont transmises qu'une fois au désastre correspondant
