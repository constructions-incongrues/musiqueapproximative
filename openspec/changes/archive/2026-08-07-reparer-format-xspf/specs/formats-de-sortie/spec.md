## MODIFIED Requirements

### Requirement: Sélection du format

Le système SHALL servir une représentation alternative lorsque le paramètre `format`
désigne un format connu, et SHALL déclarer le type de contenu correspondant. Un format
déclaré SHALL aboutir : il ne peut ni échouer, ni servir un corps vide.

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
