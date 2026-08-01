# Spécification : formats-de-sortie

## Purpose

Décrit les représentations alternatives d'un morceau ou d'une liste de morceaux, et la
manière dont un consommateur les demande.

## Requirements

### Requirement: Sélection du format

Le système SHALL servir une représentation alternative lorsque le paramètre `format`
désigne un format connu, et SHALL déclarer le type de contenu correspondant.

#### Scénario : Formats reconnus

- **QUAND** un consommateur ajoute `format=json`, `format=xspf` ou `format=max` à une
  demande de morceau ou de liste
- **ALORS** la réponse est servie sans gabarit d'habillage
- **ET** le type de contenu est respectivement `application/json`,
  `application/xspf+xml` ou `application/maxmsp+text`

#### Scénario : Format inconnu

- **QUAND** le paramètre `format` désigne une valeur non reconnue
- **ALORS** la page est servie dans sa représentation HTML habituelle

#### Scénario : Formats annoncés au visiteur

- **QUAND** une page de morceau ou de liste est servie en HTML
- **ALORS** les formats `json` et `xspf` sont annoncés au visiteur
- **ET** le format `max` ne l'est pas, bien qu'il reste accessible

### Requirement: Représentation JSON d'un morceau

La représentation JSON d'un morceau SHALL suivre la convention jsonapi.org fondée sur les
URL, et SHALL exposer le morceau, sa piste, son contributeur et ses liens de navigation.

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

### Requirement: Représentation XSPF d'une liste

La représentation XSPF d'une liste SHALL être une playlist valide, encodée en UTF-8,
dont chaque piste porte son adresse de lecture directe.

#### Scénario : Structure de la playlist

- **QUAND** un consommateur demande une liste au format `xspf`
- **ALORS** la réponse est un document XSPF déclarant l'encodage UTF-8
- **ET** chaque piste porte le créateur, le titre, le corps du post en annotation,
  l'adresse de la page du morceau en information, et l'adresse absolue du fichier audio
  en localisation

#### Scénario : Titre de la playlist selon le filtre

- **QUAND** la liste est filtrée par contributeur
- **ALORS** le titre de la playlist mentionne le nom d'affichage de ce contributeur
- **ET** lorsque la liste résulte d'une recherche, le titre mentionne les termes
  recherchés
- **ET** en l'absence de filtre, le titre annonce l'ensemble des morceaux

### Requirement: Représentation Max/MSP d'une liste

La représentation `max` d'une liste SHALL être une suite de lignes textuelles, une par
morceau, dont les champs sont débarrassés des caractères qui casseraient l'analyse côté
Max/MSP.

#### Scénario : Ligne par morceau

- **QUAND** un consommateur demande une liste au format `max`
- **ALORS** chaque morceau produit une ligne portant son rang, l'artiste, le titre,
  l'adresse du fichier audio, l'adresse de la page, le contributeur, le nombre total de
  morceaux et le corps du post
- **ET** les guillemets et retours à la ligne sont retirés des champs textuels

### Requirement: Représentation Max/MSP d'un morceau isolé

Le format `max` appliqué à un morceau isolé SHALL produire une ligne unique, de même
structure que celle qu'une liste produit pour ce morceau, un morceau isolé étant traité
comme une liste d'un seul élément.

#### Scénario : Demande du format max sur un morceau

- **QUAND** un consommateur demande `/post/:slug` au format `max`
- **ALORS** la réponse est une ligne portant le rang, l'artiste, le titre, l'adresse du
  fichier audio, l'adresse de la page, le contributeur, le nombre total de morceaux et le
  corps du post
- **ET** le rang vaut `0` et le nombre total de morceaux vaut `1`
- **ET** le type de contenu est `application/maxmsp+text`

#### Scénario : Champs textuels assainis

- **QUAND** l'artiste, le titre ou le corps du post contiennent des guillemets ou des
  retours à la ligne
- **ALORS** ces caractères sont retirés de la ligne produite, comme dans la
  représentation d'une liste

#### Scénario : Structure identique à celle d'une liste

- **QUAND** un consommateur compare la ligne obtenue pour un morceau isolé et celle que
  produit pour ce même morceau une liste au format `max`
- **ALORS** les deux lignes portent les mêmes champs, dans le même ordre et avec le même
  échappement
- **ET** seuls le rang et le nombre total de morceaux peuvent différer
