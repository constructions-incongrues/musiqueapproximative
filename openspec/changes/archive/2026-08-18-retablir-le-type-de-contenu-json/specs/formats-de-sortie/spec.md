## MODIFIED Requirements

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
