## Why

Servir le catalogue coûte **8 271 requêtes et 7,17 s**. Une seule en suffit.

`buildOnlinePostsQuery` joint `sfGuardUser` mais **jamais `UserProfile`**. Trois lectures par
morceau retombent donc dans la base :

| site de lecture | ce qu'il lit |
| --- | --- |
| `Post::toJson()` ligne 119 | `getSfGuardUser()->UserProfile->website_url` |
| `sfGuardUser::getDisplayName()` | `$this->UserProfile->display_name` |
| `listSuccess.max.php` | `getSfGuardUser()->username` |

Mesuré en local sur 8 099 morceaux, **identity map vidée avant chaque essai** :

| | requêtes | durée |
| --- | --- | --- |
| hydratation seule | **1** | 0,79 s |
| + accès au contributeur | **8 099** | 6,63 s |
| + `Markdown(body)` | 1 | 0,65 s |
| + `html_entity_decode` ×4 | 1 | 0,49 s |

**L'accès au contributeur pèse 88 % du coût au-dessus de la ligne de base.** Ni le rendu
Markdown ni le décodage d'entités ne comptent.

Cette mesure a défait la story 3, qui attribuait la lenteur au volume sérialisé et
s'apprêtait à borner les représentations machine — donc à rompre un contrat public, sans
canal de dépréciation, pour traiter un symptôme.

## What Changes

- `buildOnlinePostsQuery` joint `UserProfile` et projette explicitement, **quand l'appelant
  n'a pas demandé de projection restreinte**.
- Un test compte les requêtes. Sans lui, le N+1 reviendra au premier `getSfGuardUser()`
  ajouté dans un gabarit, et personne ne le verra — c'est exactement ainsi qu'il est arrivé.

Mesuré : **8 271 requêtes / 7,17 s → 1 requête / 1,08 s**, soit 6,6× plus rapide.

Le contrat public n'est pas concerné : aucune route, aucun format, aucune sortie ne change.
C'est le même document, servi plus vite.

## Hors périmètre

- **Borner ou paginer quoi que ce soit.** La story 3 est gelée ; elle repartira si un
  consommateur réclame la pagination, pas sur un argument de latence.
- **`PostTable::search()`**, qui porte son propre N+1 — une requête par résultat — et rend
  un tableau PHP au lieu d'une requête. Story distincte, déjà nommée.
- **Les chemins Subsonic**, qui passent une projection restreinte et n'ont jamais lu
  `UserProfile`. Ce change ne doit rien leur coûter, ce qui est précisément la difficulté.
