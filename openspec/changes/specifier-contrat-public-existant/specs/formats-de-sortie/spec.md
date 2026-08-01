## Purpose

Décrit les représentations alternatives d'un morceau ou d'une liste de morceaux, et la
manière dont un consommateur les demande.

## ADDED Requirements

### Requirement: Sélection du format

Le système SHALL servir une représentation alternative lorsque le paramètre `format`
désigne un format connu, et SHALL déclarer le type de contenu correspondant.

#### Scenario: Formats reconnus

- **WHEN** un consommateur ajoute `format=json`, `format=xspf` ou `format=max` à une
  demande de morceau ou de liste
- **THEN** la réponse est servie sans gabarit d'habillage
- **AND** le type de contenu est respectivement `application/json`,
  `application/xspf+xml` ou `application/maxmsp+text`

#### Scenario: Format inconnu

- **WHEN** le paramètre `format` désigne une valeur non reconnue
- **THEN** la page est servie dans sa représentation HTML habituelle

#### Scenario: Formats annoncés au visiteur

- **WHEN** une page de morceau ou de liste est servie en HTML
- **THEN** les formats `json` et `xspf` sont annoncés au visiteur
- **AND** le format `max` ne l'est pas, bien qu'il reste accessible

### Requirement: Représentation JSON d'un morceau

La représentation JSON d'un morceau SHALL suivre la convention jsonapi.org fondée sur les
URL, et SHALL exposer le morceau, sa piste, son contributeur et ses liens de navigation.

#### Scenario: Identité et adresse

- **WHEN** un consommateur demande la représentation JSON d'un morceau
- **THEN** le champ `id` vaut l'identifiant d'URL du morceau
- **AND** le champ `href` vaut l'adresse absolue de sa représentation JSON

#### Scenario: Corps du morceau

- **THEN** le champ `body` est un objet portant `markdown`, le texte source, et `html`,
  son rendu

#### Scenario: Description de la piste

- **THEN** le champ `track` est un objet portant `href` (adresse absolue du fichier
  audio), `title`, `author` et `md5`
- **AND** les champs bruts correspondants n'apparaissent pas à la racine de l'objet

#### Scenario: Description du contributeur

- **THEN** le champ `contributor` est un objet portant `name`, `slug` et `href_website`

#### Scenario: Liens de navigation

- **THEN** le champ `links` porte `contributor_playlist`, adresse de la liste JSON des
  morceaux du contributeur
- **AND** `links` porte `avatar`, adresse de l'illustration associée au morceau
- **AND** `links` porte `post_previous` et `post_next` lorsque ces morceaux sont connus
  de l'appelant

#### Scenario: Champs jamais exposés

- **THEN** les champs internes de mise en ligne, de révision et l'objet utilisateur
  complet sont absents de la réponse

### Requirement: Représentation XSPF d'une liste

La représentation XSPF d'une liste SHALL être une playlist valide, encodée en UTF-8,
dont chaque piste porte son adresse de lecture directe.

#### Scenario: Structure de la playlist

- **WHEN** un consommateur demande une liste au format `xspf`
- **THEN** la réponse est un document XSPF déclarant l'encodage UTF-8
- **AND** chaque piste porte le créateur, le titre, le corps du post en annotation,
  l'adresse de la page du morceau en information, et l'adresse absolue du fichier audio
  en localisation

#### Scenario: Titre de la playlist selon le filtre

- **WHEN** la liste est filtrée par contributeur
- **THEN** le titre de la playlist mentionne le nom d'affichage de ce contributeur
- **AND** lorsque la liste résulte d'une recherche, le titre mentionne les termes
  recherchés
- **AND** en l'absence de filtre, le titre annonce l'ensemble des morceaux

### Requirement: Représentation Max/MSP d'une liste

La représentation `max` d'une liste SHALL être une suite de lignes textuelles, une par
morceau, dont les champs sont débarrassés des caractères qui casseraient l'analyse côté
Max/MSP.

#### Scenario: Ligne par morceau

- **WHEN** un consommateur demande une liste au format `max`
- **THEN** chaque morceau produit une ligne portant son rang, l'artiste, le titre,
  l'adresse du fichier audio, l'adresse de la page, le contributeur, le nombre total de
  morceaux et le corps du post
- **AND** les guillemets et retours à la ligne sont retirés des champs textuels

### Requirement: Représentation Max/MSP d'un morceau isolé

Le format `max` appliqué à un morceau isolé SHALL être considéré comme non implémenté.

#### Scenario: Demande du format max sur un morceau

- **WHEN** un consommateur demande `/post/:slug` au format `max`
- **THEN** la réponse ne contient aucune donnée exploitable

> Comportement constaté, non souhaitable : le gabarit correspondant ne contient que la
> mention `TODO`, alors que le format est annoncé comme disponible par la sélection de
> format. À traiter par un changement dédié.
