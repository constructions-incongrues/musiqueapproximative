# Spécification : catalogue-morceaux

## Purpose

Définit quels morceaux sont publiquement visibles, dans quel ordre ils se succèdent, et
comment un visiteur ou un consommateur d'API navigue de l'un à l'autre.

## Requirements

### Requirement: Définition d'un morceau publiable

Le système SHALL ne rendre publiquement accessible qu'un morceau marqué en ligne et dont
la date de publication est atteinte. La date de publication est considérée atteinte
jusqu'à deux heures dans le futur.

#### Scénario : Morceau hors ligne

- **QUAND** un morceau a son indicateur de mise en ligne à faux
- **ALORS** il n'apparaît dans aucune liste, aucun flux et aucune navigation
- **ET** son accès direct par identifiant renvoie une erreur 404

#### Scénario : Morceau à publication future

- **QUAND** la date de publication d'un morceau est postérieure à l'instant courant de
  plus de deux heures
- **ALORS** le morceau n'est pas publiquement accessible

#### Scénario : Morceau publiable sous peu

- **QUAND** la date de publication d'un morceau se situe dans les deux heures à venir
- **ALORS** le morceau est déjà publiquement accessible

#### Scénario : Morceau sans identifiant d'URL

- **QUAND** un morceau a un identifiant d'URL vide ou absent
- **ALORS** il est exclu des listes, afin de ne pas provoquer d'erreur de routage

### Requirement: Ordre du catalogue

Le système SHALL présenter les morceaux du plus récemment publié au plus ancien.

#### Scénario : Ordre d'une liste

- **QUAND** un consommateur demande la liste des morceaux
- **ALORS** les morceaux sont ordonnés par date de publication décroissante

### Requirement: Accès au dernier morceau publié

La racine du site SHALL rediriger vers le morceau publiable le plus récent.

#### Scénario : Redirection depuis la racine

- **QUAND** un visiteur demande `/`
- **ALORS** il est redirigé vers la page du morceau publiable le plus récent

#### Scénario : Redirection filtrée par contributeur

- **QUAND** un visiteur demande `/` avec le paramètre `c` valant le nom d'un contributeur
- **ALORS** il est redirigé vers le morceau publiable le plus récent de ce contributeur
- **ET** le paramètre `c` est conservé dans l'URL de destination

#### Scénario : Aucun morceau publiable

- **QUAND** aucun morceau publiable n'existe pour les critères demandés
- **ALORS** la réponse est une erreur 404

### Requirement: Consultation d'un morceau

Le système SHALL exposer chaque morceau publiable à une URL stable construite sur son
identifiant d'URL.

#### Scénario : Morceau existant

- **QUAND** un visiteur demande `/post/:slug` pour un morceau publiable
- **ALORS** la page du morceau est servie

#### Scénario : Identifiant inconnu

- **QUAND** l'identifiant d'URL ne correspond à aucun morceau publiable
- **ALORS** la réponse est une erreur 404

### Requirement: Navigation séquentielle

Le système SHALL permettre de passer d'un morceau au suivant ou au précédent selon
l'ordre de publication, en respectant les filtres de navigation en cours.

#### Scénario : Morceau suivant

- **QUAND** un consommateur demande `/posts/next` avec le paramètre `current` valant
  l'identifiant numérique d'un morceau
- **ALORS** la réponse décrit le morceau publiable immédiatement plus récent

#### Scénario : Morceau précédent

- **QUAND** un consommateur demande `/posts/prev` avec le paramètre `current` valant
  l'identifiant numérique d'un morceau
- **ALORS** la réponse décrit le morceau publiable immédiatement plus ancien

#### Scénario : Navigation restreinte à un contributeur

- **QUAND** le paramètre `c` accompagne la demande de navigation
- **ALORS** seuls les morceaux de ce contributeur sont considérés

### Requirement: Tirage aléatoire

Le système SHALL exposer un morceau publiable tiré au hasard.

#### Scénario : Morceau aléatoire

- **QUAND** un consommateur demande `/posts/random`
- **ALORS** la réponse décrit un morceau publiable choisi aléatoirement

#### Scénario : Tirage restreint à un contributeur

- **QUAND** le paramètre `c` accompagne la demande
- **ALORS** le tirage ne porte que sur les morceaux de ce contributeur

### Requirement: Réponse de navigation

Les points d'entrée de navigation SHALL répondre en JSON avec l'adresse et le libellé du
morceau désigné.

#### Scénario : Structure de la réponse

- **QUAND** un consommateur interroge `/posts/next`, `/posts/prev` ou `/posts/random`
- **ALORS** le type de contenu est `application/json`
- **ET** le corps contient un champ `url` valant l'adresse de la page du morceau
- **ET** le corps contient un champ `title` valant « artiste - titre »

#### Scénario : Aucun morceau voisin

- **QUAND** un consommateur demande le morceau suivant du plus récent, ou le précédent du
  plus ancien
- **ALORS** la réponse signale une ressource absente
- **ET** elle ne prétend pas servir un morceau dont l'adresse et l'intitulé seraient vides

#### Scénario : Morceau courant non désigné ou inconnu

- **QUAND** une demande de navigation omet le morceau courant, ou en désigne un qui n'existe
  pas
- **ALORS** la réponse le signale
- **ET** aucune erreur d'exécution n'est produite

### Requirement: Recherche par empreinte du fichier

Le système SHALL permettre de retrouver un morceau publiable à partir de l'empreinte MD5
de son fichier audio.

#### Scénario : Empreinte connue

- **QUAND** un consommateur demande `/post/md5/:md5sum` pour une empreinte correspondant à
  un morceau publiable
- **ALORS** le type de contenu est `application/json`
- **ET** le corps est la représentation JSON complète du morceau, servie sous la même
  enveloppe que les autres routes qui rendent un morceau en JSON

#### Scénario : Forme identique quelle que soit la désignation

- **QUAND** un consommateur récupère un même morceau par son identifiant d'URL puis par
  l'empreinte de sa piste
- **ALORS** les deux réponses ont la même forme
- **ET** un seul analyseur suffit à les lire toutes deux

#### Scénario : Empreinte inconnue

- **QUAND** un consommateur demande un morceau par une empreinte qui ne correspond à aucun
  morceau publiable
- **ALORS** la réponse signale une ressource absente
- **ET** son corps est analysable dans le format demandé
- **ET** aucune erreur d'exécution n'est produite

### Requirement: Liste et recherche plein texte

Le système SHALL exposer la liste des morceaux publiables, filtrable par contributeur ou
interrogeable par termes de recherche. Le point d'entrée de la recherche SHALL être
présent et utilisable sur toute page du site, quelle que soit la largeur d'affichage.

#### Scénario : Liste complète

- **QUAND** un visiteur demande `/posts`
- **ALORS** tous les morceaux publiables sont listés, du plus récent au plus ancien

#### Scénario : Liste d'un contributeur

- **QUAND** le paramètre `c` accompagne la demande
- **ALORS** seuls les morceaux de ce contributeur sont listés
- **ET** le titre de la page annonce la playlist de ce contributeur

#### Scénario : Recherche par termes

- **QUAND** le paramètre `q` accompagne la demande
- **ALORS** seuls les morceaux publiables correspondant aux termes sont listés
- **ET** le titre de la page annonce le nombre de résultats et les termes recherchés

#### Scénario : Résultats de recherche non publiables

- **QUAND** la recherche remonte un morceau qui n'est pas publiable
- **ALORS** ce morceau est écarté des résultats

#### Scénario : Point d'entrée de la recherche sur écran étroit

- **QUAND** un visiteur affiche n'importe quelle page du site sur une largeur de 360 px
- **ALORS** le champ de recherche et sa commande d'envoi sont visibles
- **ET** ils tiennent dans la largeur disponible, sans débordement horizontal de la page
- **ET** l'envoi du formulaire conduit aux résultats correspondant aux termes saisis

#### Scénario : Point d'entrée de la recherche sur écran large

- **QUAND** un visiteur affiche n'importe quelle page du site sur une largeur de 1280 px
- **ALORS** le champ de recherche et sa commande d'envoi sont visibles et utilisables

#### Scénario : Saisie tactile dans le champ de recherche

- **QUAND** un visiteur sur terminal tactile met le champ de recherche au point
- **ALORS** la page ne se met pas à l'échelle automatiquement
- **ET** le champ comme sa commande d'envoi offrent une cible d'au moins 44 px de haut

#### Scénario : Report du terme recherché dans le champ

- **QUAND** la page affichée résulte d'une recherche par le paramètre `q`
- **ALORS** le champ de recherche contient les termes de cette recherche
