## ADDED Requirements

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
