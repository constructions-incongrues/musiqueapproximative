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
