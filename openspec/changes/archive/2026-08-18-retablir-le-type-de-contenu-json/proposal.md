## Why

`JsonApiFilter` réécrit le type de contenu de toute réponse JSON en
`application/vnd.api+json`. La spécification `formats-de-sortie` exige
`application/json` — scénario « Formats reconnus ». C'est le seul écart du plan de release
qui contredise une exigence déjà écrite, et non un manque à combler.

Le nom du filtre dit ce qu'il promettait : la convention JSON:API 1.0. Cette migration a
été écartée sciemment (`openspec/discovery.md`). Il ne reste donc qu'un en-tête qui annonce
un format que le corps ne respecte pas — la moitié d'une promesse abandonnée.

C'est aussi **le premier amendement au contrat OpenAPI**, publié la veille. Le contrat
déclare `application/vnd.api+json` sur six routes ; ce changement les corrige, et le diff
du contrat est le journal de la modification. C'est le dispositif tel qu'il a été conçu.

## What Changes

- Retrait de `JsonApiFilter` et de son entrée `json_api` dans `filters.yml`. Le type de
  contenu redevient celui que l'action pose : `application/json` sur `/post/{slug}`,
  `/post/md5/{md5sum}`, `/posts`, `/posts/next`, `/posts/prev`, `/posts/random`.
- **Le paramètre `charset=utf-8` disparaît des réponses JSON** — effet constaté à
  l'implémentation, non prévu à la proposition. Il n'était présent que parce que le filtre
  le codait en dur dans sa chaîne. Sans lui, le socle ne l'ajoute qu'aux types `text/*`,
  `*xml` et `*javascript`. C'est conforme : la RFC 8259 ne définit aucun paramètre
  `charset` pour `application/json`, et la spécification du projet écrit `application/json`
  sans paramètre. Les autres formats — HTML, XSPF — le conservent, inchangés.
- **BREAKING** pour tout appelant qui filtrerait sur `application/vnd.api+json`. Aucun
  consommateur du JSON n'a été identifié à ce jour ; le contrat publié rend la rupture
  lisible dans un diff, ce qui est le seul recours dont ce projet dispose.
- Amendement du contrat `src/web/openapi.yaml-dist` : six déclarations de type et la
  mention en prose de l'écart, qui disparaît puisque l'écart disparaît.
- Retrait de `src/test/unit/filter/JsonApiFilterTest.php`, et report de ce que ses trois
  `unlike` protégeaient — `/oembed` sert `application/json+oembed`, le format HTML n'est
  pas réécrit — vers des vérifications qui restent vraies sans le filtre.
- Correction de l'assertion de `postActionsTest.php` qui verrouille le type actuel.
- Une vérification que le type survit au **cache** : c'est le piège que ce retrait doit
  démontrer ne pas ressusciter.

Le contrat public est concerné : **un en-tête change sur six routes.** Aucun corps, aucune
route, aucun paramètre.

## Capabilities

### New Capabilities

Aucune.

### Modified Capabilities

- `contrat-openapi` : rien dans les exigences ne change. Le document qu'elles décrivent est
  amendé, et son test le vérifie — c'est le fonctionnement normal, pas une modification de
  comportement. Aucun delta n'est produit pour cette capacité.
- `formats-de-sortie` : le scénario « Formats reconnus » exige déjà `application/json`.
  L'exigence ne bouge pas ; elle cesse d'être contredite. Le delta ajoute ce que la spec ne
  dit pas encore et que ce changement doit garantir : que le type annoncé survit au cache,
  et que les surfaces qui servent un autre type JSON — oEmbed, le protocole d'écoute
  tierce — le conservent.

## Hors périmètre

- **La forme du corps JSON.** L'enveloppe `posts`, l'objet nu de `/post/md5/`, la forme
  `{url,title}` du lecteur : ce sont les stories 4 et 5 du plan. Ce changement ne touche
  qu'un en-tête.
- **`ApiResponse`**, écrite et sans appelant. Elle le reste. La brancher est un autre
  travail que retirer un filtre.
- **Le protocole d'écoute tierce (`/rest`)** et **`/oembed`**, que le filtre exemptait
  explicitement. Ce changement vérifie qu'ils ne bougent pas ; il ne les travaille pas.
- **Les autres écarts consignés au contrat** — absence de bornage, objet nu, erreurs
  fatales. Chacun a sa story.

## Impact

- **Retiré** : `src/lib/filter/JsonApiFilter.class.php`,
  `src/test/unit/filter/JsonApiFilterTest.php`, l'entrée `json_api` de
  `src/apps/frontend/config/filters.yml`.
- **Modifié** : `src/web/openapi.yaml-dist` (six types, une mention),
  `src/test/functional/frontend/postActionsTest.php`.
- **Vérifié sans être modifié** : `src/test/functional/frontend/restActionsTest.php`, dont
  l'assertion de non-réécriture reste vraie et dont le message doit cesser de nommer un
  filtre disparu.
- **Non modifié** : les actions et les gabarits. Aucune ne change de comportement ; elles
  posaient déjà le bon type, il était écrasé au retour de la chaîne de filtres.
- **Dépendances** : aucune. Ce changement en retire.
