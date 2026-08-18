## MODIFIED Requirements

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

