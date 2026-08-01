# Spécification : desastres

## Purpose

Décrit l'altération volontaire et imprévisible des pages du site par des « désastres » :
des recettes visuelles ou sonores déclenchées par des règles portant sur le contenu
consulté. C'est un parti pris éditorial du site, pas un défaut.

## Requirements

### Requirement: Déclenchement conditionnel

Le système SHALL évaluer, à chaque affichage d'une page de morceau ou de liste, un jeu de
règles déclarées en configuration, et SHALL appliquer les recettes des règles retenues.

#### Scénario : Évaluation sur une page de morceau

- **QUAND** un visiteur consulte une page de morceau
- **ALORS** les règles sont évaluées avec, en contexte, l'artiste, le titre du morceau et
  le contributeur

#### Scénario : Évaluation sur une page de liste

- **QUAND** un visiteur consulte une liste de morceaux
- **ALORS** les règles sont évaluées avec, en contexte, les termes de recherche et le
  contributeur demandés

#### Scénario : Paramètres de la requête pris en compte

- **QUAND** les règles sont évaluées pour un affichage
- **ALORS** les paramètres de la requête sont joints au contexte d'évaluation, ce qui
  permet de déclencher un désastre par l'URL

#### Scénario : Aucune règle satisfaite

- **QUAND** aucune règle ne correspond
- **ALORS** la page est servie sans altération

### Requirement: Part d'aléatoire

Une règle SHALL pouvoir ne se déclencher qu'une fois sur plusieurs, afin que deux
consultations de la même page ne produisent pas nécessairement le même effet.

#### Scénario : Règle probabiliste

- **QUAND** une règle déclare une probabilité inférieure à 1
- **ALORS** elle ne se déclenche qu'une partie du temps, pour un même contexte

#### Scénario : Règle certaine

- **QUAND** une règle déclare une probabilité de 1
- **ALORS** elle se déclenche à chaque fois que son expression est satisfaite

### Requirement: Application d'une recette

L'application d'une recette SHALL enrichir la réponse des ressources du désastre
correspondant, sans modifier le contenu servi.

#### Scénario : Ressources du désastre

- **QUAND** une recette est appliquée
- **ALORS** les feuilles de style du désastre correspondant sont ajoutées à la réponse
- **ET** ses scripts sont ajoutés à la réponse

#### Scénario : Scripts externes déclarés

- **QUAND** la recette déclare des scripts externes
- **ALORS** ces scripts sont ajoutés à la réponse en plus des ressources du désastre

#### Scénario : Options transmises au désastre

- **QUAND** la recette déclare des options
- **ALORS** ces options sont mises à disposition du désastre côté client

#### Scénario : Recette désactivée

- **QUAND** une recette est marquée comme désactivée en configuration
- **ALORS** elle n'est jamais appliquée

### Requirement: Cumul des désastres

Plusieurs désastres SHALL pouvoir s'appliquer à une même page.

#### Scénario : Règles multiples satisfaites

- **QUAND** plusieurs règles se déclenchent pour un même affichage
- **ALORS** les recettes de chacune sont appliquées à la réponse

### Requirement: Absence de configuration

Le système SHALL servir les pages normalement lorsque la configuration des désastres est
absente.

#### Scénario : Fichier de configuration manquant

- **QUAND** le fichier de configuration des désastres n'existe pas
- **ALORS** aucune altération n'est appliquée et la page est servie normalement
