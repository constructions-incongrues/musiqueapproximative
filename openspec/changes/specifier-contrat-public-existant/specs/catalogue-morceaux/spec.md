## Purpose

Définit quels morceaux sont publiquement visibles, dans quel ordre ils se succèdent, et
comment un visiteur ou un consommateur d'API navigue de l'un à l'autre.

## ADDED Requirements

### Requirement: Définition d'un morceau publiable

Le système SHALL ne rendre publiquement accessible qu'un morceau marqué en ligne et dont
la date de publication est atteinte. La date de publication est considérée atteinte
jusqu'à deux heures dans le futur.

#### Scenario: Morceau hors ligne

- **WHEN** un morceau a son indicateur de mise en ligne à faux
- **THEN** il n'apparaît dans aucune liste, aucun flux et aucune navigation
- **AND** son accès direct par identifiant renvoie une erreur 404

#### Scenario: Morceau à publication future

- **WHEN** la date de publication d'un morceau est postérieure à l'instant courant de
  plus de deux heures
- **THEN** le morceau n'est pas publiquement accessible

#### Scenario: Morceau publiable sous peu

- **WHEN** la date de publication d'un morceau se situe dans les deux heures à venir
- **THEN** le morceau est déjà publiquement accessible

#### Scenario: Morceau sans identifiant d'URL

- **WHEN** un morceau a un identifiant d'URL vide ou absent
- **THEN** il est exclu des listes, afin de ne pas provoquer d'erreur de routage

### Requirement: Ordre du catalogue

Le système SHALL présenter les morceaux du plus récemment publié au plus ancien.

#### Scenario: Ordre d'une liste

- **WHEN** un consommateur demande la liste des morceaux
- **THEN** les morceaux sont ordonnés par date de publication décroissante

### Requirement: Accès au dernier morceau publié

La racine du site SHALL rediriger vers le morceau publiable le plus récent.

#### Scenario: Redirection depuis la racine

- **WHEN** un visiteur demande `/`
- **THEN** il est redirigé vers la page du morceau publiable le plus récent

#### Scenario: Redirection filtrée par contributeur

- **WHEN** un visiteur demande `/` avec le paramètre `c` valant le nom d'un contributeur
- **THEN** il est redirigé vers le morceau publiable le plus récent de ce contributeur
- **AND** le paramètre `c` est conservé dans l'URL de destination

#### Scenario: Aucun morceau publiable

- **WHEN** aucun morceau publiable n'existe pour les critères demandés
- **THEN** la réponse est une erreur 404

### Requirement: Consultation d'un morceau

Le système SHALL exposer chaque morceau publiable à une URL stable construite sur son
identifiant d'URL.

#### Scenario: Morceau existant

- **WHEN** un visiteur demande `/post/:slug` pour un morceau publiable
- **THEN** la page du morceau est servie

#### Scenario: Identifiant inconnu

- **WHEN** l'identifiant d'URL ne correspond à aucun morceau publiable
- **THEN** la réponse est une erreur 404

### Requirement: Navigation séquentielle

Le système SHALL permettre de passer d'un morceau au suivant ou au précédent selon
l'ordre de publication, en respectant les filtres de navigation en cours.

#### Scenario: Morceau suivant

- **WHEN** un consommateur demande `/posts/next` avec le paramètre `current` valant
  l'identifiant numérique d'un morceau
- **THEN** la réponse décrit le morceau publiable immédiatement plus récent

#### Scenario: Morceau précédent

- **WHEN** un consommateur demande `/posts/prev` avec le paramètre `current` valant
  l'identifiant numérique d'un morceau
- **THEN** la réponse décrit le morceau publiable immédiatement plus ancien

#### Scenario: Navigation restreinte à un contributeur

- **WHEN** le paramètre `c` accompagne la demande de navigation
- **THEN** seuls les morceaux de ce contributeur sont considérés

### Requirement: Tirage aléatoire

Le système SHALL exposer un morceau publiable tiré au hasard.

#### Scenario: Morceau aléatoire

- **WHEN** un consommateur demande `/posts/random`
- **THEN** la réponse décrit un morceau publiable choisi aléatoirement

#### Scenario: Tirage restreint à un contributeur

- **WHEN** le paramètre `c` accompagne la demande
- **THEN** le tirage ne porte que sur les morceaux de ce contributeur

### Requirement: Réponse de navigation

Les points d'entrée de navigation SHALL répondre en JSON avec l'adresse et le libellé du
morceau désigné.

#### Scenario: Structure de la réponse

- **WHEN** un consommateur interroge `/posts/next`, `/posts/prev` ou `/posts/random`
- **THEN** le type de contenu est `application/json`
- **AND** le corps contient un champ `url` valant l'adresse de la page du morceau
- **AND** le corps contient un champ `title` valant « artiste - titre »

### Requirement: Recherche par empreinte du fichier

Le système SHALL permettre de retrouver un morceau publiable à partir de l'empreinte MD5
de son fichier audio.

#### Scenario: Empreinte connue

- **WHEN** un consommateur demande `/post/md5/:md5sum` pour une empreinte correspondant à
  un morceau publiable
- **THEN** le type de contenu est `application/json`
- **AND** le corps est la représentation JSON complète du morceau

### Requirement: Liste et recherche plein texte

Le système SHALL exposer la liste des morceaux publiables, filtrable par contributeur ou
interrogeable par termes de recherche.

#### Scenario: Liste complète

- **WHEN** un visiteur demande `/posts`
- **THEN** tous les morceaux publiables sont listés, du plus récent au plus ancien

#### Scenario: Liste d'un contributeur

- **WHEN** le paramètre `c` accompagne la demande
- **THEN** seuls les morceaux de ce contributeur sont listés
- **AND** le titre de la page annonce la playlist de ce contributeur

#### Scenario: Recherche par termes

- **WHEN** le paramètre `q` accompagne la demande
- **THEN** seuls les morceaux publiables correspondant aux termes sont listés
- **AND** le titre de la page annonce le nombre de résultats et les termes recherchés

#### Scenario: Résultats de recherche non publiables

- **WHEN** la recherche remonte un morceau qui n'est pas publiable
- **THEN** ce morceau est écarté des résultats
