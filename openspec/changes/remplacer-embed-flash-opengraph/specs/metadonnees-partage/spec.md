## Purpose

Décrit les métadonnées OpenGraph qu'une page de morceau expose aux plateformes
tierces, afin qu'un lien partagé restitue le titre, l'illustration et un lecteur
audio réellement jouable par le destinataire.

## ADDED Requirements

### Requirement: Identification du morceau partagé

La page d'un morceau SHALL exposer les métadonnées OpenGraph permettant d'identifier
le contenu : titre, description et URL canonique.

#### Scénario : Métadonnées d'identification présentes

- **QUAND** un consommateur récupère le HTML de `/post/:slug`
- **ALORS** la page contient `og:title` valant « `artiste` - `titre` | `titre du site` »
- **ET** la page contient `og:description` contenant le corps du post débarrassé de
  son balisage Markdown et HTML
- **ET** la page contient `og:url` valant l'URL absolue canonique du morceau

#### Scénario : Titre enrichi du contributeur

- **QUAND** la page est demandée avec le paramètre de contributeur `c`
- **ALORS** `og:title` mentionne en plus « Playlist de `contributeur` »

### Requirement: Illustration du partage

La page d'un morceau SHALL exposer une illustration carrée de 476×476 pixels au
format PNG.

#### Scénario : Illustration déclarée

- **QUAND** un consommateur récupère le HTML de `/post/:slug`
- **ALORS** la page contient `og:image` pointant vers une image PNG accessible
  publiquement
- **ET** `og:image:type` vaut `image/png`
- **ET** `og:image:width` et `og:image:height` valent tous deux `476`

#### Scénario : Illustration glitchée

- **QUAND** l'effet de glitch du logo est actif pour cet affichage
- **ALORS** `og:image` pointe vers le service de glitch avec l'identifiant du morceau
  comme graine, ce qui rend l'illustration reproductible pour un morceau donné

### Requirement: Lecteur embarquable jouable

La page d'un morceau SHALL déclarer un lecteur embarquable sous forme de document
HTML, et SHALL NOT déclarer de ressource nécessitant un greffon de navigateur
obsolète.

#### Scénario : Lecteur déclaré en HTML

- **QUAND** un consommateur récupère le HTML de `/post/:slug`
- **ALORS** `og:video` et `og:video:secure_url` pointent vers l'URL d'embed HTML du
  morceau, servie en HTTPS
- **ET** `og:video:type` vaut `text/html`
- **ET** `og:video:width` et `og:video:height` correspondent aux dimensions du
  document d'embed effectivement servi

#### Scénario : Aucune ressource Flash déclarée

- **QUAND** un consommateur inspecte l'ensemble des métadonnées de `/post/:slug`
- **ALORS** aucune métadonnée ne référence une ressource `.swf`
- **ET** aucune métadonnée ne déclare le type `application/x-shockwave-flash`

#### Scénario : Cohérence avec la réponse oEmbed

- **QUAND** un consommateur compare les métadonnées de la page et la réponse du point
  d'entrée `/oembed` pour le même morceau
- **ALORS** l'URL d'embed déclarée par `og:video` et celle contenue dans le champ
  `html` de la réponse oEmbed désignent la même ressource
- **ET** les dimensions déclarées de part et d'autre sont identiques

### Requirement: Accès direct au fichier audio

La page d'un morceau SHALL exposer l'URL du fichier audio, afin que les plateformes
qui n'affichent pas d'iframe tierce puissent tout de même proposer une écoute.

#### Scénario : Fichier audio déclaré

- **QUAND** un consommateur récupère le HTML de `/post/:slug`
- **ALORS** `og:audio` et `og:audio:secure_url` pointent vers le fichier audio du
  morceau, servi en HTTPS
- **ET** `og:audio:type` déclare le type MIME du fichier servi

### Requirement: Typage du contenu partagé

La page d'un morceau SHALL se déclarer comme un morceau de musique.

#### Scénario : Type OpenGraph

- **QUAND** un consommateur récupère le HTML de `/post/:slug`
- **ALORS** `og:type` vaut `music.song`
