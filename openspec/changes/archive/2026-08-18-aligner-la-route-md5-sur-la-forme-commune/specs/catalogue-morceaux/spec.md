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
