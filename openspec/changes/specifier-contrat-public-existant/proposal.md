## Why

Le site expose un contrat public — routes, formats de sortie, flux, oEmbed — qu'aucun
test automatisé ne couvre et qu'aucun document ne décrit fidèlement. La seule
description existante, `docs/memory-bank/README.adoc`, a dérivé : elle annonce des
routes qui n'existent pas (`/random`, `/next`, `/prev`, `/md5/:md5sum`, `/feed.rss`)
et un lecteur JW Player qui n'est plus utilisé.

Ce comportement n'est connu que du code. Sortir un jour de Symfony 1.5 suppose de
savoir ce qu'il ne faut pas casser : c'est ce contrat, écrit, qui tient lieu de filet
de non-régression tant qu'il n'y a pas de tests.

Ce changement ne modifie aucun comportement. Il documente l'existant, tel qu'il est
observable aujourd'hui, y compris ses défauts.

## What Changes

- Création de cinq spécifications décrivant le comportement public actuel, à partir
  d'une lecture du code — jamais d'une reconstitution de mémoire.
- Aucune modification de code, aucun changement de comportement, aucune dépendance.
- Les écarts et défauts constatés sont consignés dans les specs comme comportement
  actuel, sans être corrigés ici. Ils fourniront la matière de changements ultérieurs.

Défauts déjà relevés lors de la lecture, spécifiés tels quels :

- `showSuccess.max.php` contient le seul mot `TODO` : le format `max` d'un morceau
  isolé ne renvoie rien d'exploitable, alors que le format est annoncé par `setFormats()`.
- Le flux RSS lit intégralement chaque fichier audio en mémoire (`file_get_contents`)
  pour en calculer la taille déclarée dans l'enclosure.
- `/oembed` ne répond ni en `application/json+oembed` ni avec un type utilisable
  lorsqu'un format inconnu est demandé.

### Hors périmètre

- Toute correction des défauts consignés : ce changement décrit, il ne répare pas.
- L'application d'administration (`src/apps/admin`) et l'authentification `sfGuard`.
- Le moteur de règles du plugin `sfDesastrePlugin` — seul son effet observable sur la
  réponse HTTP est spécifié, pas sa grammaire d'expressions ni son évaluateur.
- Les gabarits d'affichage, le thème et le lecteur de la page de morceau.
- Les métadonnées OpenGraph, déjà couvertes par le changement
  `remplacer-embed-flash-opengraph`.

## Capabilities

### New Capabilities

- `catalogue-morceaux`: quels morceaux sont publiquement visibles, dans quel ordre, et
  comment on navigue entre eux (dernier morceau, suivant, précédent, aléatoire,
  recherche par empreinte MD5, liste, recherche plein texte).
- `formats-de-sortie`: les représentations alternatives d'un morceau ou d'une liste
  (`json` conforme à jsonapi.org, `xspf`, `max`) et la négociation par le paramètre
  `format`.
- `flux-syndication`: le flux RSS 2.01 publié sur `/posts/feed`, ses filtres et le
  contenu de ses items.
- `embarquement-oembed`: le point d'entrée `/oembed` et le gabarit d'embarquement
  servi par `/post/:slug?embed`.
- `desastres`: l'altération aléatoire et conditionnelle des pages par des recettes
  déclarées en configuration.

### Modified Capabilities

Aucune. `openspec/specs/` est vide ; ce changement est le premier à peupler le corpus.

## Impact

- Contrat public : **décrit, jamais modifié**. Aucune route, aucun format, aucune
  réponse ne change.
- Aucun fichier de `src/` n'est touché.
- Sources de la lecture : `src/apps/frontend/config/routing.yml`,
  `src/apps/frontend/modules/post/actions/actions.class.php`,
  `src/lib/model/doctrine/Post.class.php`, `src/lib/model/doctrine/PostTable.class.php`,
  les gabarits de `src/apps/frontend/modules/post/templates/`,
  `src/apps/frontend/config/desastres.yml` et `src/plugins/sfDesastrePlugin/`.
- Après validation, ces specs ont vocation à être promues dans `openspec/specs/` par
  `openspec sync` : elles décrivent l'état stable, pas un changement à venir.
