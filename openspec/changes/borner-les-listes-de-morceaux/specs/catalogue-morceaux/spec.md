## MODIFIED Requirements

### Requirement: Liste et recherche plein texte

Le système SHALL exposer la liste des morceaux publiables, filtrable par contributeur ou
interrogeable par termes de recherche. Le point d'entrée de la recherche SHALL être
présent et utilisable sur toute page du site, quelle que soit la largeur d'affichage.

La liste servie SHALL être bornée, sans que le catalogue entier cesse d'être atteignable
par une demande explicite. Un consommateur SHALL pouvoir demander une tranche
— combien de morceaux, à partir de quel rang — et cette demande SHALL valoir pour toutes
les représentations de la liste. À défaut de demande, une tranche par défaut SHALL être
servie : le bornage protège d'abord celui qui ignore qu'il peut demander autre chose.

Le total des morceaux correspondant à la demande SHALL être exposé, afin qu'un consommateur
sache ce qu'il n'a pas reçu. Tout libellé qui annonce un nombre de morceaux SHALL annoncer
ce total, et non la taille de la tranche servie.

Un visiteur qui reçoit une tranche SHALL pouvoir constater qu'il n'a pas tout reçu, et de
quel ensemble sa tranche est extraite. Une troncature qu'il ne peut pas percevoir se
confond avec un catalogue complet : le bornage doit se voir, faute de quoi il se déguise.

#### Scénario : Liste complète

- **QUAND** un consommateur demande explicitement le catalogue entier, en réclamant une
  tranche assez large pour le contenir
- **ALORS** tous les morceaux publiables sont listés, du plus récent au plus ancien
- **ET** le bornage ne retire donc rien de ce qui était atteignable : il change ce que
  reçoit celui qui ne demande rien, non ce qu'on peut obtenir

#### Scénario : Liste par défaut

- **QUAND** un visiteur demande `/posts` sans préciser de tranche
- **ALORS** les cinquante morceaux publiables les plus récents sont listés, du plus récent
  au plus ancien
- **ET** le total des morceaux publiables est exposé

#### Scénario : Tranche demandée

- **QUAND** un consommateur demande un nombre de morceaux et un rang de départ
- **ALORS** la liste servie porte ce nombre de morceaux, à partir de ce rang
- **ET** l'ordre reste du plus récent au plus ancien

#### Scénario : Tranche demandée au-delà du total

- **QUAND** le rang de départ dépasse le nombre de morceaux disponibles
- **ALORS** la liste servie est vide
- **ET** le total exposé reste celui de l'ensemble correspondant à la demande
- **ET** la réponse aboutit normalement, sans erreur

#### Scénario : Demande de tranche inintelligible

- **QUAND** le nombre ou le rang demandé est négatif, non numérique ou absurde
- **ALORS** la tranche par défaut est servie
- **ET** la réponse aboutit normalement, sans erreur

#### Scénario : Bornage valable pour toutes les représentations

- **QUAND** une liste est demandée dans l'une quelconque de ses représentations
- **ALORS** la tranche servie est la même que celle qu'aurait reçue la représentation par
  défaut pour la même demande
- **ET** aucune représentation n'échappe au bornage

#### Scénario : Le visiteur sait qu'il ne voit qu'une partie

- **QUAND** un visiteur reçoit une page de liste qui ne porte pas tous les morceaux
  correspondant à sa demande
- **ALORS** la page annonce le total dont cette liste est extraite
- **ET** il peut donc distinguer une liste tronquée d'un catalogue qui aurait cette taille

#### Scénario : Liste d'un contributeur

- **QUAND** le paramètre `c` accompagne la demande
- **ALORS** seuls les morceaux de ce contributeur sont listés
- **ET** le titre de la page annonce la playlist de ce contributeur
- **ET** lorsque ce titre annonce un nombre de morceaux, ce nombre est le total du
  contributeur, non celui de la tranche servie

#### Scénario : Recherche par termes

- **QUAND** le paramètre `q` accompagne la demande
- **ALORS** seuls les morceaux publiables correspondant aux termes sont listés
- **ET** la liste des résultats est bornée comme l'est celle du catalogue
- **ET** le titre de la page annonce le nombre **total** de résultats et les termes
  recherchés, non le nombre de résultats servis

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
