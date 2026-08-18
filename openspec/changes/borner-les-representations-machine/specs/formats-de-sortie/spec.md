## ADDED Requirements

### Requirement: Les représentations machine d'une liste sont bornées

Lorsqu'une liste de morceaux est demandée dans une représentation machine, le site SHALL
n'en servir qu'une portion par défaut, et non le catalogue entier.

Le demandeur SHALL pouvoir choisir combien de morceaux il reçoit et à partir de quel rang,
au moyen des mêmes paramètres que ceux déjà offerts par le flux de syndication — une seule
convention pour un seul besoin.

Une portion demandée au-delà de ce que la liste contient SHALL être servie comme une liste
vide, et non comme une erreur : demander la page suivante d'une liste épuisée est le
déroulement normal d'une pagination.

La représentation lisible par un humain n'est PAS concernée : elle SHALL continuer de servir
la liste entière.

#### Scénario : Liste servie bornée par défaut

- **QUAND** un consommateur demande une liste de morceaux dans une représentation machine
  sans préciser de portion
- **ALORS** il reçoit les premiers morceaux de cette liste, en nombre borné
- **ET** il ne reçoit pas le catalogue entier

#### Scénario : Portion choisie

- **QUAND** un consommateur demande un nombre de morceaux et un rang de départ
- **ALORS** la représentation servie porte exactement les morceaux de cette portion
- **ET** l'ordre de la liste est celui qu'elle a sans portion demandée

#### Scénario : Portion au-delà de la liste

- **QUAND** un consommateur demande une portion qui commence après le dernier morceau
- **ALORS** la représentation est servie avec un code de succès
- **ET** elle ne porte aucun morceau

#### Scénario : La représentation lisible reste entière

- **QUAND** un visiteur demande la liste dans sa représentation lisible
- **ALORS** tous les morceaux publiés de cette liste lui sont servis
- **ET** aucune portion n'est appliquée

#### Scénario : Le bornage respecte le filtre de la liste

- **QUAND** la liste demandée est restreinte à un contributeur ou à une recherche
- **ALORS** la portion servie est prise dans cette liste-là
- **ET** non dans le catalogue entier

### Requirement: Une liste bornée est navigable

Une représentation machine qui ne porte qu'une portion de sa liste SHALL dire combien de
morceaux la liste contient au total, et quelle portion elle porte.

Elle SHALL offrir de quoi atteindre les portions voisines sans que le demandeur ait à les
deviner. Une portion servie sans cela n'est pas une pagination : c'est une troncature, et
le seul moyen d'en sortir est de redemander jusqu'à obtenir une réponse vide.

Ces indications SHALL être disponibles **quelle que soit la représentation**, y compris
celles dont le corps ne peut rien porter de plus.

Une représentation dont le corps peut porter ces indications SHALL les porter aussi, plutôt
que d'obliger son lecteur à sortir du format.

#### Scénario : La liste dit sa taille

- **QUAND** un consommateur reçoit une portion d'une liste
- **ALORS** il apprend combien de morceaux cette liste contient au total
- **ET** quelle portion lui a été servie

#### Scénario : Atteindre la portion suivante

- **QUAND** la portion servie n'est pas la dernière
- **ALORS** le moyen d'obtenir la suivante est donné avec la réponse
- **ET** le demandeur n'a pas à le construire lui-même

#### Scénario : Aux extrémités de la liste

- **QUAND** la portion servie est la première
- **ALORS** aucun moyen d'atteindre une portion précédente n'est proposé
- **ET** il en va de même pour la suivante lorsque la portion servie est la dernière

#### Scénario : Une représentation au corps non extensible reste navigable

- **QUAND** la représentation demandée ne peut rien porter de plus dans son corps
- **ALORS** les indications de navigation lui sont fournies malgré tout
- **ET** son corps est servi inchangé

### Requirement: Un document de playlist dit ce qu'il contient

Lorsqu'une liste est servie sous forme de playlist et qu'elle ne porte qu'une portion des
morceaux, le titre du document SHALL le dire.

Un titre qui annonce l'ensemble alors que le document ne porte qu'une partie induit son
lecteur en erreur, et cette erreur est invisible : rien dans le fichier ne la signale.

#### Scénario : Playlist tronquée

- **QUAND** une playlist est servie sans porter tous les morceaux de la liste demandée
- **ALORS** son titre indique qu'elle n'en porte qu'une portion

#### Scénario : Playlist complète

- **QUAND** une playlist porte tous les morceaux de la liste demandée
- **ALORS** son titre décrit cette liste sans mention de portion
