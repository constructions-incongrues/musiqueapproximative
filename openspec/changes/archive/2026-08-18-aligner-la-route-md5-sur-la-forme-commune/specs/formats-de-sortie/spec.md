## MODIFIED Requirements

### Requirement: Représentation JSON d'un morceau

La représentation JSON d'un morceau SHALL suivre la convention jsonapi.org fondée sur les
URL, et SHALL exposer le morceau, sa piste, son contributeur et ses liens de navigation.

Elle SHALL être servie enveloppée dans une collection, y compris pour un morceau isolé et
quelle que soit la façon dont ce morceau est désigné. Un consommateur SHALL pouvoir lire
toute réponse portant un morceau avec un seul analyseur.

Les routes de navigation du lecteur du site font exception : elles servent une forme
minimale, qui SHALL être documentée comme délibérée. C'est le contrat interne du lecteur,
et l'aligner casserait la navigation du site.

#### Scénario : Identité et adresse

- **QUAND** un consommateur demande la représentation JSON d'un morceau
- **ALORS** le champ `id` vaut l'identifiant d'URL du morceau
- **ET** le champ `href` vaut l'adresse absolue de sa représentation JSON

#### Scénario : Corps du morceau

- **QUAND** un consommateur demande la représentation JSON d'un morceau
- **ALORS** le champ `body` est un objet portant `markdown`, le texte source, et `html`,
  son rendu

#### Scénario : Description de la piste

- **QUAND** un consommateur demande la représentation JSON d'un morceau
- **ALORS** le champ `track` est un objet portant `href` (adresse absolue du fichier
  audio), `title`, `author` et `md5`
- **ET** les champs bruts correspondants n'apparaissent pas à la racine de l'objet

#### Scénario : Description du contributeur

- **QUAND** un consommateur demande la représentation JSON d'un morceau
- **ALORS** le champ `contributor` est un objet portant `name`, `slug` et `href_website`

#### Scénario : Liens de navigation

- **QUAND** un consommateur demande la représentation JSON d'un morceau
- **ALORS** le champ `links` porte `contributor_playlist`, adresse de la liste JSON des
  morceaux du contributeur
- **ET** `links` porte `avatar`, adresse de l'illustration associée au morceau
- **ET** `links` porte `post_previous` et `post_next` lorsque ces morceaux sont connus
  de l'appelant

#### Scénario : Champs jamais exposés

- **QUAND** un consommateur demande la représentation JSON d'un morceau
- **ALORS** les champs internes de mise en ligne, de révision et l'objet utilisateur
  complet sont absents de la réponse

#### Scénario : Enveloppe d'un morceau isolé

- **QUAND** un consommateur demande la représentation JSON d'un seul morceau, par son
  identifiant d'URL ou par l'empreinte de sa piste
- **ALORS** le morceau est servi dans une collection, comme le serait une liste d'un seul
  élément

#### Scénario : Forme minimale des routes de navigation du lecteur

- **QUAND** un consommateur demande le morceau suivant, précédent ou un morceau au hasard
- **ALORS** la réponse porte l'adresse de la page du morceau et son intitulé, et rien de plus
- **ET** cette forme n'est pas une incohérence mais le contrat interne du lecteur du site
- **ET** elle est documentée comme délibérée là où les représentations sont décrites
