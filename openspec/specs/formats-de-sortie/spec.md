# Spécification : formats-de-sortie

## Purpose

Décrit les représentations alternatives d'un morceau ou d'une liste de morceaux, et la
manière dont un consommateur les demande.

## Requirements

### Requirement: Sélection du format

Le système SHALL servir une représentation alternative lorsque le paramètre `format`
désigne un format connu, et SHALL déclarer le type de contenu correspondant. Un format
déclaré SHALL aboutir : il ne peut ni échouer, ni servir un corps vide.

Le type de contenu déclaré SHALL être celui de la représentation servie, sans réécriture
en aval. Il SHALL être identique qu'une réponse soit calculée ou servie depuis le cache.

Les surfaces qui servent du JSON sous une spécification propre — l'embarquement oEmbed, le
protocole d'écoute tierce — SHALL conserver le type que cette spécification leur impose.

#### Scénario : Formats reconnus

- **QUAND** un consommateur ajoute `format=json`, `format=xspf` ou `format=max` à une
  demande de morceau ou de liste
- **ALORS** la réponse est servie sans gabarit d'habillage
- **ET** le type de contenu est respectivement `application/json`,
  `application/xspf+xml` ou `application/maxmsp+text`
- **ET** le corps n'est pas vide

#### Scénario : Un format déclaré aboutit toujours

- **QUAND** un format figure dans la liste des formats connus du système
- **ALORS** il est servi avec un code `200` sur `/posts` comme sur `/post/:slug`
- **ET** aucune dépendance absente de l'environnement d'exécution ne peut le faire échouer
  silencieusement

#### Scénario : Format inconnu

- **QUAND** le paramètre `format` désigne une valeur non reconnue
- **ALORS** la page est servie dans sa représentation HTML habituelle

#### Scénario : Formats annoncés sur une page de liste

- **QUAND** une page de liste est servie en HTML
- **ALORS** `json`, `xspf` et `max` sont déclarés en `<link rel="alternate">`, chacun avec
  son type de contenu
- **ET** seuls `json` et `xspf` figurent parmi les liens visibles proposés au visiteur

#### Scénario : Formats annoncés sur une page de morceau

- **QUAND** une page de morceau est servie en HTML
- **ALORS** `json` est le seul format annoncé, en `<link rel="alternate">` comme parmi les
  liens visibles
- **ET** `xspf` et `max` restent accessibles par le paramètre `format`, sans être annoncés

#### Scénario : Le type survit au cache

- **QUAND** une réponse au format `json` est demandée une première fois, puis redemandée
  et servie depuis le cache
- **ALORS** les deux réponses portent le même type de contenu
- **ET** ce type est `application/json`

#### Scénario : Type des routes de navigation du lecteur

- **QUAND** un consommateur demande le morceau suivant, précédent ou un morceau au hasard
- **ALORS** le type de contenu est `application/json`

#### Scénario : Type d'un morceau désigné par empreinte

- **QUAND** un consommateur demande un morceau par l'empreinte de sa piste
- **ALORS** le type de contenu est `application/json`

#### Scénario : Surfaces à spécification propre préservées

- **QUAND** un consommateur demande l'embarquement oEmbed d'une page de morceau
- **ALORS** le type de contenu est celui qu'impose la spécification oEmbed, et non
  `application/json` nu
- **ET** le protocole d'écoute tierce conserve de même le type que son propre protocole lui
  impose

#### Scénario : Représentation HTML non affectée

- **QUAND** une page est servie dans sa représentation HTML
- **ALORS** son type de contenu reste celui d'un document HTML

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

La représentation XSPF SHALL être un document de playlist valide, portant un titre décrivant
la sélection demandée et un élément par morceau.

#### Scénario : Document servi

- **QUAND** un consommateur demande une liste au format `xspf`
- **ALORS** la racine du document est une playlist XSPF
- **ET** le document déclare l'encodage `utf-8`
- **ET** le type de contenu est `application/xspf+xml`

#### Scénario : Titre de la playlist

- **QUAND** la liste est filtrée par contributeur
- **ALORS** le titre de la playlist nomme ce contributeur
- **ET** lorsque la liste est filtrée par recherche, le titre reprend le terme cherché
- **ET** sans filtre, le titre désigne l'ensemble des morceaux

#### Scénario : Description d'un morceau

- **QUAND** un morceau figure dans la playlist
- **ALORS** son élément porte l'adresse absolue du fichier audio, l'artiste, le titre, le
  corps du post et l'adresse de sa page

#### Scénario : Morceau isolé

- **QUAND** un consommateur demande `/post/:slug` au format `xspf`
- **ALORS** une playlist d'un seul élément est servie, à l'image de ce que font les formats
  `json` et `max`

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
