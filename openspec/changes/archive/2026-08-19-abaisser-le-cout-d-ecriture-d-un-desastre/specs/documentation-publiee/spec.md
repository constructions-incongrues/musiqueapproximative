## ADDED Requirements

### Requirement: Une seule description de la façon d'écrire un désastre

Le dépôt NE SHALL PAS porter deux descriptions concurrentes de la structure d'un désastre.
Une seule SHALL faire foi, et les autres emplacements SHALL y renvoyer plutôt que de la
recopier.

La règle n'est pas esthétique. Deux descriptions divergent : celle du plugin décrit un
fichier `desastres.yml` unique, neuf mois après que la configuration de ce projet eut été
scindée en `recettes/`, `regles/` et imports.

Elle n'est pas fausse — la forme monolithique fonctionne toujours, le plugin acceptant les
deux et les fusionnant. Elle est **incomplète pour ce projet**, ce qui est plus insidieux :
qui la suit écrit une configuration valide, mais pas celle qui est en place, et ne trouve
pas les recettes existantes là où elle dit qu'elles sont.

Une description partielle présentée comme complète coûte plus cher qu'une absence :
l'absence fait chercher, la demi-description fait chercher au mauvais endroit avec
confiance.

#### Scenario: chercher comment écrire un désastre depuis le code

- **GIVEN** quelqu'un qui ouvre `src/plugins/sfDesastrePlugin/`
- **WHEN** il y cherche comment ajouter un désastre
- **THEN** il est renvoyé vers la description qui fait foi
- **AND** il ne trouve sur place aucune description concurrente de la structure

#### Scenario: la description qui fait foi décrit ce qui existe

- **GIVEN** la description de référence
- **WHEN** on la compare à l'arborescence réelle de la configuration
- **THEN** elle nomme `recettes/`, `regles/` et la déclaration des imports

### Requirement: Le coût d'écriture d'un désastre est écrit, et la décision de ne pas l'outiller aussi

La documentation SHALL porter le coût mesuré d'un désastre complet — combien de fichiers,
dans combien de répertoires, quels schémas — plutôt que de laisser chacun le redécouvrir.

Elle SHALL porter la décision de ne pas outiller cette tâche, avec la mesure qui la
fonde : dix-neuf recettes écrites en deux jours de novembre 2025, aucune depuis.

Sans cette trace, la question se repose sans les chiffres, et se tranche alors au jugé —
ce qui est exactement ce que le relevé existe pour éviter.

#### Scenario: se demander s'il faut un générateur

- **GIVEN** quelqu'un qui trouve coûteux d'écrire un désastre
- **WHEN** il cherche si la question a déjà été instruite
- **THEN** il trouve le coût mesuré, la fréquence mesurée, et la décision qui en découle
- **AND** il peut la contester sur des chiffres plutôt que sur une impression
