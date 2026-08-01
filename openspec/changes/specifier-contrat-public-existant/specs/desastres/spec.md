## Purpose

Décrit l'altération volontaire et imprévisible des pages du site par des « désastres » :
des recettes visuelles ou sonores déclenchées par des règles portant sur le contenu
consulté. C'est un parti pris éditorial du site, pas un défaut.

## ADDED Requirements

### Requirement: Déclenchement conditionnel

Le système SHALL évaluer, à chaque affichage d'une page de morceau ou de liste, un jeu de
règles déclarées en configuration, et SHALL appliquer les recettes des règles retenues.

#### Scenario: Évaluation sur une page de morceau

- **WHEN** un visiteur consulte une page de morceau
- **THEN** les règles sont évaluées avec, en contexte, l'artiste, le titre du morceau et
  le contributeur

#### Scenario: Évaluation sur une page de liste

- **WHEN** un visiteur consulte une liste de morceaux
- **THEN** les règles sont évaluées avec, en contexte, les termes de recherche et le
  contributeur demandés

#### Scenario: Paramètres de la requête pris en compte

- **THEN** les paramètres de la requête sont joints au contexte d'évaluation, ce qui
  permet de déclencher un désastre par l'URL

#### Scenario: Aucune règle satisfaite

- **WHEN** aucune règle ne correspond
- **THEN** la page est servie sans altération

### Requirement: Part d'aléatoire

Une règle SHALL pouvoir ne se déclencher qu'une fois sur plusieurs, afin que deux
consultations de la même page ne produisent pas nécessairement le même effet.

#### Scenario: Règle probabiliste

- **WHEN** une règle déclare une probabilité inférieure à 1
- **THEN** elle ne se déclenche qu'une partie du temps, pour un même contexte

#### Scenario: Règle certaine

- **WHEN** une règle déclare une probabilité de 1
- **THEN** elle se déclenche à chaque fois que son expression est satisfaite

### Requirement: Application d'une recette

L'application d'une recette SHALL enrichir la réponse des ressources du désastre
correspondant, sans modifier le contenu servi.

#### Scenario: Ressources du désastre

- **WHEN** une recette est appliquée
- **THEN** les feuilles de style du désastre correspondant sont ajoutées à la réponse
- **AND** ses scripts sont ajoutés à la réponse

#### Scenario: Scripts externes déclarés

- **WHEN** la recette déclare des scripts externes
- **THEN** ces scripts sont ajoutés à la réponse en plus des ressources du désastre

#### Scenario: Options transmises au désastre

- **WHEN** la recette déclare des options
- **THEN** ces options sont mises à disposition du désastre côté client

#### Scenario: Recette désactivée

- **WHEN** une recette est marquée comme désactivée en configuration
- **THEN** elle n'est jamais appliquée

### Requirement: Cumul des désastres

Plusieurs désastres SHALL pouvoir s'appliquer à une même page.

#### Scenario: Règles multiples satisfaites

- **WHEN** plusieurs règles se déclenchent pour un même affichage
- **THEN** les recettes de chacune sont appliquées à la réponse

### Requirement: Absence de configuration

Le système SHALL servir les pages normalement lorsque la configuration des désastres est
absente.

#### Scenario: Fichier de configuration manquant

- **WHEN** le fichier de configuration des désastres n'existe pas
- **THEN** aucune altération n'est appliquée et la page est servie normalement
