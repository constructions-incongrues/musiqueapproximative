# Spécification : desastres

## Purpose

Décrit l'altération volontaire et imprévisible des pages du site par des « désastres » :
des recettes visuelles ou sonores déclenchées par des règles portant sur le contenu
consulté. C'est un parti pris éditorial du site, pas un défaut.

## Requirements

### Requirement: Déclenchement conditionnel

Le système SHALL évaluer, à chaque production d'une page de morceau ou de liste, un jeu de
règles déclarées en configuration, et SHALL appliquer les recettes des règles retenues. Une
page servie depuis le cache SHALL porter les recettes retenues lors de sa production, sans
nouvelle évaluation.

#### Scénario : Évaluation sur une page de morceau

- **QUAND** une page de morceau est produite
- **ALORS** les règles sont évaluées avec, en contexte, l'artiste, le titre du morceau et
  le contributeur

#### Scénario : Évaluation sur une page de liste

- **QUAND** une page de liste est produite
- **ALORS** les règles sont évaluées avec, en contexte, les termes de recherche et le
  contributeur demandés

#### Scénario : Consultation servie depuis le cache

- **QUAND** une page est servie depuis le cache
- **ALORS** aucune règle n'est évaluée
- **ET** la page porte les recettes retenues lors de sa production

#### Scénario : Paramètres de la requête pris en compte

- **QUAND** les règles sont évaluées pour une production de page
- **ALORS** les paramètres de la requête sont joints au contexte d'évaluation, ce qui
  permet de déclencher un désastre par l'URL
- **ET** le déclenchement vaut pour toutes les consultations de cette URL, ses paramètres
  faisant partie de ce qui distingue une entrée de cache d'une autre

#### Scénario : Aucune règle satisfaite

- **QUAND** aucune règle ne correspond
- **ALORS** la page est servie sans altération
- **ET** elle le reste pour toutes les consultations servies depuis cette entrée de cache

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

### Requirement: Application d'une recette

L'application d'une recette SHALL enrichir la réponse des ressources du désastre
correspondant, sans modifier le contenu servi. L'enrichissement SHALL faire partie de la
représentation mise en cache, de sorte qu'une réponse servie depuis le cache soit aussi
complète que celle qui l'a produite.

#### Scénario : Options transmises au désastre

- **QUAND** la recette déclare des options
- **ALORS** ces options sont mises à disposition du désastre côté client
- **ET** elles le sont pour toute réponse portant les ressources du désastre, qu'elle soit
  produite ou servie depuis le cache

#### Scénario : Consultations successives d'une adresse enrichie

- **QUAND** une adresse dont la réponse porte un désastre est demandée plusieurs fois
  pendant la durée de vie du cache
- **ALORS** chaque réponse porte les ressources du désastre **et** ses options
- **ET** aucune consultation ne sert de ressources privées de leurs options

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
