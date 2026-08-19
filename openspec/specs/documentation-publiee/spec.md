# documentation-publiee Specification

## Purpose
TBD - created by archiving change reparer-la-navigation-de-la-documentation. Update Purpose after archive.

## Requirements

### Requirement: Toute page publiée est atteignable

Le site de documentation SHALL présenter une navigation qui mène à chacune de ses pages
publiées. Aucune page servie SHALL être atteignable seulement par son adresse directe.

La navigation SHALL être la source unique de cette liste. Une seconde liste tenue ailleurs
diverge de la première — c'est ce qui s'est produit.

Le projet SHALL détecter automatiquement toute page publiée absente de la navigation, et
SHALL nommer cette page plutôt que signaler un écart global.

#### Scénario : Une page nouvellement publiée entre dans la navigation

- **QUAND** une page est ajoutée à la documentation
- **ET** qu'elle n'est inscrite à aucune entrée de navigation
- **ALORS** la vérification automatisée échoue
- **ET** elle nomme la page absente

#### Scénario : Navigation complète

- **QUAND** un lecteur ouvre le site de documentation
- **ALORS** chaque page publiée est atteignable depuis la navigation
- **ET** aucune n'exige d'en connaître l'adresse à l'avance

#### Scénario : La page d'accueil ne redouble pas la navigation

- **QUAND** un lecteur ouvre la page d'accueil
- **ALORS** elle présente le projet et les façons d'y participer
- **ET** elle ne tient pas une seconde liste exhaustive des pages, que la navigation porte
  déjà

### Requirement: Un renvoi d'une page à une autre mène quelque part

Toute page du site de documentation SHALL ne renvoyer qu'à des cibles que le site sert.
Un renvoi vers une page absente MUST être signalé à la construction du site, en nommant le
fichier fautif et la cible manquante.

La documentation d'usage d'une surface publique livrée SHALL être servie par le site
lui-même. Un fichier laissé hors de l'arborescence publiée n'est pas de la documentation
publiée : c'est une note de dépôt, et aucune page ne SHALL y renvoyer comme si elle l'était.

#### Scénario : Brancher un client sur l'API Subsonic

- **QUAND** un lecteur veut brancher un client Subsonic sur le site
- **ALORS** il atteint la marche à suivre depuis la navigation du site de documentation
- **ET** il n'a pas à ouvrir le dépôt pour l'obtenir

#### Scénario : Le renvoi de la page de déploiement aboutit

- **QUAND** un lecteur suit, depuis la page de déploiement, le renvoi vers le détail de la
  migration Subsonic
- **ALORS** il arrive sur une page servie par le site
- **ET** il y retrouve l'ordre contraignant des opérations annoncé par le renvoi

#### Scénario : Une référence croisée sans cible est signalée

- **QUAND** le site est construit
- **ET** qu'une page renvoie à une cible que le site ne sert pas
- **ALORS** la construction émet une erreur
- **ET** cette erreur nomme le fichier fautif et la cible manquante

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
