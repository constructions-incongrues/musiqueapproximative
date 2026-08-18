## MODIFIED Requirements

### Requirement: Définition d'un morceau publiable

Le système SHALL ne rendre publiquement accessible qu'un morceau marqué en ligne et dont
la date de publication est atteinte. La date de publication est considérée atteinte
jusqu'à deux heures dans le futur.

Cette définition SHALL valoir pour **toutes** les façons d'atteindre un morceau — par son
identifiant d'URL, par son identifiant interne, par l'empreinte de sa piste, par tirage
aléatoire, ou en suivant la navigation séquentielle. Aucun chemin ne SHALL en connaître une
autre.

#### Scénario : La définition vaut pour tous les chemins

- **QUAND** un morceau n'est pas publiable, quelle qu'en soit la raison
- **ALORS** aucune façon de le désigner ne permet de l'atteindre
- **ET** aucun tirage ni aucune navigation ne peut y conduire

#### Scénario : Le morceau est restitué tel qu'il a été saisi

- **QUAND** un morceau est enregistré avec un titre, un artiste ou un corps écrits dans un
  système d'écriture quelconque — latin étendu, cyrillique, idéogrammes, ou portant des
  symboles hors du répertoire latin
- **ALORS** les valeurs restituées sont identiques à celles qui ont été saisies
- **ET** aucun caractère n'est remplacé, tronqué ni perdu

#### Scénario : Restitution identique dans toutes les représentations

- **QUAND** un morceau ainsi écrit est servi dans l'une quelconque de ses représentations
- **ALORS** son titre et son artiste y figurent tels qu'ils ont été saisis

#### Scénario : Morceau hors ligne

- **QUAND** un morceau a son indicateur de mise en ligne à faux
- **ALORS** il n'apparaît dans aucune liste, aucun flux et aucune navigation
- **ET** son accès direct par identifiant renvoie une erreur 404

#### Scénario : Morceau à publication future

- **QUAND** la date de publication d'un morceau est postérieure à l'instant courant de
  plus de deux heures
- **ALORS** le morceau n'est pas publiquement accessible

#### Scénario : Morceau publiable sous peu

- **QUAND** la date de publication d'un morceau se situe dans les deux heures à venir
- **ALORS** le morceau est déjà publiquement accessible

#### Scénario : Morceau sans identifiant d'URL

- **QUAND** un morceau a un identifiant d'URL vide ou absent
- **ALORS** il est exclu des listes, afin de ne pas provoquer d'erreur de routage
