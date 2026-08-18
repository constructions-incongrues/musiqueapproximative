## Why

`/post/{slug}?format=json` sert `{"posts":[<morceau>]}`. `/post/md5/{md5sum}` sert le
morceau nu. Deux contrats pour le même objet, selon la façon dont on le désigne : un client
doit écrire deux analyseurs pour lire la même chose.

L'enveloppe est la forme commune. Le gabarit de liste porte le commentaire qui la justifie
— « Even single ressources are displayed as lists » — et `/post/{slug}` la sert déjà pour
un morceau isolé. `/md5` est la seule route qui s'en écarte.

## What Changes

- `/post/md5/{md5sum}` sert désormais `{"posts":[<morceau>]}`, comme les autres routes qui
  rendent un morceau en JSON.
- **BREAKING** : un client qui lit `réponse.id` devra lire `réponse.posts[0].id`.
- Amendement du contrat OpenAPI : la route passe du schéma de l'objet nu à celui de
  l'enveloppe, et la mention de l'écart disparaît avec l'écart.
- La divergence délibérée de `/posts/next`, `/posts/prev` et `/posts/random` est
  **documentée comme telle** dans le contrat : leur forme `{url, title}` est le contrat
  interne du lecteur du site, consommée sur quatre points d'appel de `layout.php`. Les
  aligner casserait la navigation.

Le contrat public est concerné : **la forme du corps d'une route change.**

## Ce que la vérification a corrigé au packet de la story

Le plan de release annonçait un second écart : `/post/md5/` exposerait des champs que
`formats-de-sortie` dit absents. **C'est faux**, et l'erreur venait du relevé de la story 10.

Les deux réponses ont été comparées clé à clé : elles servent le **même objet**, aux mêmes
champs, parce que toutes deux passent par `Post::toJson()`. `publish_on`, `created_at`,
`updated_at`, `track_duration`, `track_size` et `buy_url` figurent dans les deux. Rien n'est
propre à `/md5`.

Et rien n'est interdit : les trois prohibitions du scénario « Champs jamais exposés » — mise
en ligne, révision, objet utilisateur complet — portent sur `is_online`, `svn_revision` et
`sfGuardUser`, tous retirés par `toJson()`. Les autres champs ne sont pas interdits, ils ne
sont **pas décrits**. C'est un manque de la spécification, qui revient à la story 6.

**Cette story se réduit donc à l'enveloppe**, ce qu'elle disait avant que la révision ne
l'alourdisse à tort.

## Capabilities

### New Capabilities

Aucune.

### Modified Capabilities

- `catalogue-morceaux` : l'exigence « Recherche par empreinte du fichier » décrit la réponse
  comme « la représentation JSON complète du morceau ». Elle doit dire que cette
  représentation est **enveloppée comme les autres**.
- `formats-de-sortie` : l'exigence « Représentation JSON d'un morceau » décrit l'objet sans
  jamais dire s'il est enveloppé. Elle gagne cette forme, et la divergence assumée des
  routes de navigation du lecteur.

## Hors périmètre

- **La forme de l'objet lui-même** — `id`, `href`, `body`, `track`, `contributor`, `links`.
  Elle ne change pas. Seule son enveloppe change.
- **Les champs non décrits par la spec** (`publish_on`, `created_at`, `updated_at`,
  `track_duration`, `track_size`, `buy_url`). Ils ne sont pas en défaut ; les décrire est le
  travail de la story 6.
- **`/posts/next`, `/posts/prev`, `/posts/random`.** Leur forme minimale est le contrat
  interne du lecteur du site. Elles sont documentées, pas modifiées.
- **`ApiResponse`**, toujours sans appelant.
- **Le comportement en erreur** de `/md5` — une empreinte inconnue y produit une erreur
  fatale PHP, non un 404. C'est la story 5, et ce changement ne doit pas prétendre le régler.

## Impact

- **Modifié** : `src/apps/frontend/modules/post/actions/actions.class.php` (`executeMd5`),
  `src/web/openapi.yaml-dist`.
- **Vérifié sans être modifié** : `src/apps/frontend/templates/layout.php`, dont les quatre
  `$.get` consomment `data.url` et `data.title` — la navigation du site ne doit pas bouger ;
  `Post::toJson()`, partagé, qui ne change pas.
- **Dépendances** : story 1, livrée — le type de contenu devait être stabilisé avant qu'on
  touche aux corps.
