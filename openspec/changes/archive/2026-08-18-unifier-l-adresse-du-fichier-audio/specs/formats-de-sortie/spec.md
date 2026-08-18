## MODIFIED Requirements

### Requirement: Sélection du format

Le système SHALL servir une représentation alternative lorsque le paramètre `format`
désigne un format connu, et SHALL déclarer le type de contenu correspondant. Un format
déclaré SHALL aboutir : il ne peut ni échouer, ni servir un corps vide.

Le type de contenu déclaré SHALL être celui de la représentation servie, sans réécriture
en aval. Il SHALL être identique qu'une réponse soit calculée ou servie depuis le cache.

L'adresse du fichier audio d'un morceau SHALL être construite d'une seule façon, quelle que
soit la représentation qui la porte et quelle que soit la route qui la sert. Elle SHALL
désigner l'emplacement configuré pour les fichiers — lequel peut différer de l'hôte du site —
et le nom de fichier y SHALL être encodé pour une URL.

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

#### Scénario : Adresse du fichier identique d'une représentation à l'autre

- **QUAND** un consommateur récupère un même morceau dans deux représentations différentes
- **ALORS** l'adresse de son fichier audio est la même dans les deux

#### Scénario : Adresse du fichier identique d'une route à l'autre

- **QUAND** un consommateur récupère un même morceau seul, puis dans une liste, dans la même
  représentation
- **ALORS** l'adresse de son fichier audio est la même dans les deux

#### Scénario : Nom de fichier encodé

- **QUAND** le nom du fichier audio contient un espace ou un caractère qu'une URL ne peut pas
  porter tel quel
- **ALORS** l'adresse servie l'encode
- **ET** elle reste une URL valide

#### Scénario : Emplacement configuré honoré

- **QUAND** les fichiers audio sont servis depuis un emplacement distinct de l'hôte du site
- **ALORS** les adresses servies désignent cet emplacement
- **ET** aucune représentation ne retombe sur l'hôte de la requête
