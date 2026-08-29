## MODIFIED Requirements

### Requirement: Liste et recherche plein texte

Le système SHALL exposer la liste des morceaux publiables, filtrable par contributeur ou
interrogeable par termes de recherche. Le point d'entrée de la recherche SHALL être
présent et utilisable sur toute page du site, quelle que soit la largeur d'affichage.

La recherche par termes SHALL porter sur l'artiste, le titre **et le message écrit sous le
morceau**. Ce message est la seule donnée que le schéma rend obligatoire avec la date de
publication et le contributeur : il porte la raison pour laquelle un morceau a été posté.
Ne pas le chercher, c'est laisser écrire pourquoi un morceau compte sans permettre de le
retrouver par ce qu'on en a dit.

La recherche par termes NE SHALL PAS porter sur l'identité du contributeur. Le paramètre
`c` sert déjà cette demande, qui est d'une autre nature : un nom de contributeur versé dans
l'index remonterait sur toute recherche portant sur ce mot, quel que soit le morceau.

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

#### Scénario : Recherche portant sur le message

- **QUAND** les termes recherchés figurent dans le message d'un morceau, mais ni dans son
  titre ni dans son artiste
- **ALORS** ce morceau figure dans les résultats

#### Scénario : Le nom d'un contributeur n'est pas un terme de recherche

- **QUAND** les termes recherchés correspondent au nom d'un contributeur, sans figurer dans
  l'artiste, le titre ni le message d'un morceau
- **ALORS** ce morceau ne figure pas dans les résultats

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

### Requirement: Servir une liste coûte un nombre de requêtes constant

Le coût en requêtes de base pour servir une liste de morceaux SHALL être indépendant du
nombre de morceaux servis.

Cette exigence SHALL valoir pour **les résultats d'une recherche** comme pour une liste
demandée sans termes. Une recherche rend une liste de morceaux ; rien ne justifie qu'elle
coûte plus cher parce qu'elle a été obtenue autrement.

Aucune donnée de contributeur nécessaire au rendu d'une liste SHALL être lue morceau par
morceau : ce que le rendu lit, la requête de liste SHALL l'avoir chargé.

Ce coût SHALL être vérifié automatiquement. Une régression qui le rend proportionnel au
nombre de morceaux SHALL faire échouer la suite de tests — sans quoi elle revient au premier
accès ajouté dans un gabarit, sans que rien ne le signale.

#### Scénario : Le coût ne suit pas la taille de la liste

- **QUAND** une liste de morceaux est servie
- **ALORS** le nombre de requêtes de base émises ne dépend pas du nombre de morceaux qu'elle
  contient
- **ET** demander deux fois plus de morceaux n'émet pas deux fois plus de requêtes

#### Scénario : Le coût d'une recherche ne suit pas le nombre de résultats

- **QUAND** une recherche par termes rend des résultats
- **ALORS** le nombre de requêtes de base émises ne dépend pas du nombre de résultats
- **ET** une recherche rendant dix fois plus de morceaux n'émet pas dix fois plus de requêtes

#### Scénario : Le contributeur est chargé avec la liste

- **QUAND** le rendu d'une liste lit le nom d'affichage, l'identifiant ou le site d'un
  contributeur
- **ALORS** cette lecture n'émet aucune requête supplémentaire

#### Scénario : Une régression est détectée

- **QUAND** un accès lu morceau par morceau réapparaît sur un chemin de liste
- **ALORS** la suite de tests échoue
- **ET** elle nomme le coût constaté et celui attendu

#### Scénario : Les consommateurs à projection restreinte ne paient pas plus

- **QUAND** un appelant demande une liste en ne réclamant qu'une partie des champs
- **ALORS** il ne reçoit pas de données qu'il n'a pas demandées
- **ET** son coût en requêtes n'augmente pas
