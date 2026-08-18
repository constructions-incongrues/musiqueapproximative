## Why

L'API du module `post` n'a aucune description lisible par machine. Un intégrateur doit lire
le code du site pour deviner la forme des réponses, et le mainteneur n'a rien contre quoi
constater qu'un changement de format en est un. Le plan de release
(`openspec/discovery.md`, story 10) fait de ce contrat son squelette ambulant : il ne
change rien à l'API, il la décrit telle qu'elle est, et chaque story suivante devient un
amendement dont le diff est le journal des modifications.

La leçon de `docs/memory-bank/`, supprimée « après avoir dérivé au point de documenter cinq
routes inexistantes », commande la forme retenue : un contrat non vérifié ment mieux qu'une
prose non vérifiée, parce qu'il a l'autorité d'un format. Le test qui confronte le document
aux routes réellement servies n'est pas un raffinement, c'est la condition d'existence de ce
changement.

## What Changes

- Ajout de `src/web/openapi.yaml-dist`, document OpenAPI 3.1 décrivant les neuf routes du
  module `post` telles qu'elles répondent **aujourd'hui** : `/`, `/post/{slug}`,
  `/post/md5/{md5sum}`, `/posts`, `/posts/feed`, `/posts/next`, `/posts/prev`,
  `/posts/random`, `/oembed` — avec leurs paramètres (`format`, `q`, `c`, `play`), leurs
  codes de statut et leurs types de contenu observés.
- `servers:` alimenté par `${APP_DOMAIN}` via `make configure`, comme `app.yml-dist`, de
  sorte que chacun des trois profils obtienne le sien. Le fichier généré est ignoré par Git.
- Ajout d'un test fonctionnel qui, pour **chaque** `path` déclaré au contrat, demande la
  route et vérifie que le statut et le `Content-Type` annoncés sont ceux qui sont servis.
  Un `path` décrit mais absent du site, ou un type de contenu qui a dérivé, fait échouer la
  suite.
- Le document consigne l'état actuel, y compris ses écarts : `/posts?format=json` y est
  déclaré avec `application/vnd.api+json`, que la story 1 corrigera ; l'absence de tout
  paramètre de bornage y est visible, ce que la story 2 amendera.
- `docs/API_CURRENT_STATE.md` devient caduc et sera retiré, le contrat disant la même chose
  en vérifié.

Le contrat public est concerné en lecture seule : **aucune route, aucun corps, aucun
en-tête ne change**. Le changement ajoute une description et son test.

## Capabilities

### New Capabilities

- `contrat-openapi` : le site publie une description lisible par machine des routes qu'il
  sert, adaptée au domaine sur lequel il tourne, et cette description est vérifiée contre
  le site à chaque exécution de la suite de tests.

### Modified Capabilities

Aucune. `formats-de-sortie` reste inchangée : le contrat décrit ce qu'elle prescrit, il ne
prescrit rien lui-même. **La spec est normative, le contrat est descriptif et vérifié ;
s'ils divergent, c'est le contrat qui a tort.**

## Hors périmètre

- **Modifier quoi que ce soit à l'API.** Type de contenu, forme des corps, bornage des
  listes, format des erreurs : ce sont les stories 1 à 5 du plan de release. Ce changement
  décrit l'existant, y compris ce qui le gêne.
- **Valider les corps de réponse contre des schémas.** Cela demanderait un validateur JSON
  Schema en dépendance ; le socle est PHP 7.4 sans gestionnaire de paquets applicatif. Le
  test vérifie le statut et le type de contenu, pas la conformité du corps.
- **La surface Subsonic (`/rest/*`).** Elle a sa propre documentation
  (`docs/API_SUBSONIC.md`) et ses propres tests (`restActionsTest.php`). Le contrat ne la
  décrit pas et le test ne l'exerce pas.
- **Publier le contrat dans le site Antora.** La navigation du site de documentation est
  cassée ; c'est la story 11, indépendante.
- **Toute interface de consultation du contrat** — Swagger UI, Redoc, page de rendu. Le
  fichier est servi tel quel, comme `manifest.json` et `robots.txt`.

## Impact

- **Ajouté** : `src/web/openapi.yaml-dist`, un test fonctionnel sous
  `src/test/functional/frontend/`.
- **Modifié** : `.gitignore` (le `src/web/openapi.yaml` généré).
- **Retiré** : `docs/API_CURRENT_STATE.md`.
- **Non modifié** : `src/apps/frontend/`, `src/lib/`, `src/config/`. Aucun code applicatif
  n'est touché.
- **Dépendances** : aucune nouvelle. La lecture du YAML par le test passe par l'analyseur
  déjà présent dans le socle.
