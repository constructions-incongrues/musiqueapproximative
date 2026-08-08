## MODIFIED Requirements

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
