# Discovery : tenir les formats machine

> Status: complete
> Created: 2026-08-18 · Last revised: 2026-08-18

> Plan de release produit par la compétence discovery. Le reprendre ou le réviser en la relançant.
> Pour construire : lancer `/opsx:propose` en lui demandant d'utiliser la prochaine story non cochée.
> Une story = un change OpenSpec (proposal ≈ 200 mots). Une seule à la fois.

## Sources

- 2026-08-18 — Pas de PRD. L'entrée est le projet lui-même, exploré à la lecture du code
  et mesuré en production. Axe retenu avec l'auteur : les représentations machine servies
  par le module `post`. Cadrage retenu : **cohérence** (réduire l'écart entre ce que l'API
  annonce et ce qu'elle sert) plutôt que **conformité** (migrer vers JSON:API 1.0).
- 2026-08-18 — `docs/API_CURRENT_STATE.md`, `docs/API_JSON_API_TARGET.md`,
  `docs/API_SUBSONIC.md`, `openspec/specs/formats-de-sortie/spec.md`.
- 2026-08-18 (révision) — Deux personas apportés par l'auteur : **l'auditeur sur le site**
  et **le mélomane fêlé**, le contributeur. Leur ajout a tranché la question ouverte n°2 et
  déplacé une priorité. Le second nom vient du site lui-même : « C'est l'exutoire anarchique
  d'une bande de mélomanes fêlé⋅e⋅s » (`layout.php`, section « À propos »).
- 2026-08-18 (seconde révision) — Correction de l'auteur sur le mélomane fêlé : il ne se
  sert pas de Radio Approximative ; ce qu'il veut est **poster facilement et retrouver
  facilement**. Et un sixième persona apporté : **le DJ de soirée**, qui se sert du site sur
  son portable ou son téléphone.

### Mesures relevées en production le 2026-08-18

| requête | résultat |
| --- | --- |
| `GET /posts` | 3,7 Mo · 8 097 morceaux · une seule page |
| `GET /posts?format=json` | 8,4 Mo |
| `GET /posts?format=xspf` | 3,5 Mo · 3,1 s |
| `GET /posts/feed` | 43 ko — le seul format qui se borne |
| `GET /post/<inconnu>?format=json` | HTTP 404 · `text/html` |
| `GET /posts?format=json` (en-tête) | `application/vnd.api+json` — alors que la spec exige `application/json` |

Relevés complémentaires du même jour, pris en révision :

| requête | résultat |
| --- | --- |
| `GET /posts?format=json`, cache froid | **16,5 s** — contre 0,84 s une fois chaud. Le poids n'est pas qu'un volume, c'est un temps de génération. |
| `GET /posts?c=bertier` | 506 ko · 993 morceaux |
| `GET /posts?c=bertier&format=json` | 1,0 Mo |
| `GET /posts?c=bertier&format=xspf` | 440 ko |

La playlist d'un seul contributeur pèse déjà 1 Mo en JSON, et rien ne la borne non plus.

### Pourquoi la conformité JSON:API a été écartée

`docs/API_JSON_API_TARGET.md` décrit une migration vers JSON:API 1.0. Elle n'est pas retenue :

1. **Subsonic a pris le travail.** La version 1.11 a livré 18 méthodes (`getArtists`,
   `getAlbum`, `search3`, `stream`, `getPlaylists`, `getCoverArt`…) en XML, JSON et JSONP,
   documentées et couvertes par cinq fichiers de tests. La surface machine sérieuse du
   projet, c'est elle. Le JSON du module `post` est devenu une surface héritée.
2. **La migration dégraderait l'usage.** Un client lit aujourd'hui `track.href` et
   `contributor.name` directement. Avec `relationships` + `included`, il résout des
   références pour obtenir la même chose, sur un objet où la piste et le contributeur
   sont toujours présents.
3. **Elle casserait un contrat spécifié** — cinq scénarios de `formats-de-sortie` — au
   bénéfice de consommateurs qu'on n'a pas identifiés.

## Personas

### L'intégrateur

- **Qui** : quelqu'un qui consomme les données de Musique Approximative depuis son propre
  outil — un patch Max/MSP, un script, un autre site. Aucun consommateur interne du JSON
  n'a été trouvé dans le dépôt : cette API n'existe que pour des tiers.
- **But** : récupérer les morceaux dans un format qu'il puisse traiter, sans lire le code
  du site pour deviner la forme des réponses.
- **Douleur aujourd'hui** : la réponse pèse 8,4 Mo et rien ne permet de la borner ;
  l'en-tête annonce un format que le corps ne respecte pas ; le même objet arrive sous
  trois formes selon la route ; une erreur lui revient en HTML.
- **Succès** : une requête bornée, un type de contenu qui dit la vérité, une erreur qu'il
  peut analyser sans la lire.

### L'auditeur sur le site

- **Qui** : quelqu'un qui ouvre `musiqueapproximative.net` pour écouter, au jour le jour.
  Il ne sait pas qu'il consomme une API — et pourtant c'est le cas.
- **But** : écouter le morceau du jour et enchaîner, sans y penser.
- **Douleur aujourd'hui** : elle est indirecte mais réelle. Le lien XSPF lui est proposé
  parmi les liens visibles d'une page de liste — la spec `formats-de-sortie` l'impose — et
  ce lien lui sert 3,5 Mo en 2,7 à 3,1 s. C'est la seule latence de cet axe sur un chemin
  humain.
- **Succès** : le lecteur enchaîne sans accroc, et la playlist qu'on lui propose s'ouvre
  au lieu de faire attendre.
- **Ce que ce persona a tranché** : les raccourcis `j` / `k` / `r` et l'enchaînement du
  lecteur passent par `/posts/next`, `/posts/prev` et `/posts/random`. `layout.php` lit
  `data.url` et `data.title` sur quatre points d'appel. Leur forme `{"url","title"}` n'est
  pas une incohérence : c'est le contrat interne du lecteur. Voir story 4.

### Le mélomane fêlé

- **Qui** : un membre du collectif, qui publie ses obsessions via l'admin sfGuard. Le site
  le nomme lui-même ainsi : « C'est l'exutoire anarchique d'une bande de mélomanes
  fêlé⋅e⋅s. »
- **But** : deux choses, et elles sont simples — **poster un morceau facilement**, et
  **le retrouver facilement** ensuite.
- **Douleur aujourd'hui** :
  - *Poster* fonctionne. Le formulaire est en français, avec aide en ligne, prévisualisation
    du corps, et une case « Morceau inédit ? » qui vérifie les doublons.
  - *Retrouver* est le point dur. `buildQuery()` restreint bien la liste de l'admin à ses
    propres morceaux, triés par date décroissante — mais `generator.yml` porte
    `filter: class: false`. **Ni filtre ni recherche.** Chez le contributeur le plus
    prolifique, 993 morceaux à parcourir vingt par vingt.
  - Côté site, la recherche n'aide qu'à moitié : `Searchable` n'indexe que `track_author`
    et `track_title`. Le message qu'il a écrit sous le morceau n'est pas cherchable.
- **Succès** : il retrouve un morceau qu'il a posté il y a trois ans sans faire défiler
  cinquante pages.

### Le DJ de soirée

- **Qui** : quelqu'un qui fait danser, avec le site ouvert sur son portable ou son
  téléphone. Wifi de salle ou données mobiles, une main sur la platine, pas de patience.
- **But** : retrouver un morceau vite et le lancer.
- **Douleur aujourd'hui** : elle est nette et se mesure. Le chemin par la recherche est
  rapide — `/posts?q=daft+punk` pèse 43 ko en 0,35 s. Le chemin par le catalogue est un
  piège : `/posts` lui envoie **3,7 Mo** et 8 097 liens. En soirée, sur le réseau d'une
  salle, c'est la différence entre trouver et renoncer. Et s'il ne se souvient que d'une
  bribe du texte accompagnant le morceau, la recherche ne le trouvera pas : elle n'indexe
  ni le corps, ni le contributeur.
- **Succès** : il trouve et lance en quelques secondes, sans vider son forfait.
- **Ce que ce persona a changé ici** : il rend intenable l'exclusion de la page HTML du
  bornage. Le paramètre se pose dans `executeList`, qui sert **tous** les formats. Voir
  story 2.

### Le mainteneur

- **Qui** : le contributeur du collectif qui touche au code des formats.
- **But** : modifier une représentation sans casser en silence un tiers invisible.
- **Douleur aujourd'hui** : `/post/md5/:md5sum` et `/posts/next|prev|random` ne sont
  spécifiés dans aucune requirement ; le comportement en erreur non plus ; il n'y a donc
  rien contre quoi vérifier un changement sur ces routes.
- **Succès** : chaque route JSON servie est décrite par un scénario, et le déploiement
  automatique à la poussée sur `main` cesse d'être un pari.

## Journey Map

Parcours de **l'intégrateur** — c'est le persona principal de cet axe :

```
  Découvrir ──► Choisir ──► Récupérer ──► Encaisser ──► Suivre
      │            │            │          l'erreur       │
     gap        supporté     partiel          gap      supporté
```

1. **Découvrir l'API** — il cherche ce que le site expose — **gap**. Les trois documents
   d'API (`API_CURRENT_STATE.md`, `API_JSON_API_TARGET.md`, `API_SUBSONIC.md`) sont du
   Markdown posé à la racine de `docs/`, hors du site Antora. Et `docs/antora.yml` déclare
   `nav: modules/ROOT/nav.adoc`, un fichier qui n'existe pas.
2. **Choisir un format** — il voit quels formats existent — **supporté**. Les
   `<link rel="alternate">` et les liens visibles sont en place et spécifiés
   (`formats-de-sortie`, scénarios « Formats annoncés sur une page de liste / de morceau »).
3. **Récupérer les données** — il appelle `/posts?format=json` — **partiel**. Ça répond,
   mais 8,4 Mo sans borne possible, un `Content-Type` qui ment, et trois formes de réponse
   pour un même objet selon la route.
4. **Encaisser une erreur** — il demande un slug qui n'existe pas — **gap**. HTTP 404 avec
   un corps `text/html`. `ApiErrorResponse` est écrite et testée, jamais appelée.
5. **Suivre les nouveautés** — il s'abonne — **supporté**. Le flux RSS est borné à 43 ko.

Parcours de **l'auditeur sur le site** :

```
  Ouvrir ──► Écouter ──► Enchaîner ──► Emporter la playlist
 supporté    supporté     supporté           partiel
```

1. **Ouvrir le site** — **supporté**. `/` redirige vers le dernier morceau publié.
2. **Écouter** — **supporté**. Le lecteur jPlayer est en place (`showSuccess.php`).
3. **Enchaîner** — **supporté**. Raccourcis `j`, `k`, `r` et boutons de navigation, servis
   par `/posts/next|prev|random` en `{"url","title"}`, consommés sur quatre points d'appel
   dans `layout.php`.
4. **Emporter la playlist** — **partiel**. Le lien XSPF, offert parmi les liens visibles,
   coûte 2,7 à 3,1 s pour 3,5 Mo.

Parcours du **mélomane fêlé** :

```
  Poster ──► Voir en ligne ──► Retrouver son morceau
 supporté      supporté               gap
```

1. **Poster** — **supporté**. Admin sfGuard, formulaire en français avec aide en ligne,
   prévisualisation du corps (`_body_preview.php`) et de la piste, case « Morceau inédit ? »
   pour le contrôle de doublon.
2. **Voir son morceau en ligne** — **supporté**. `publish_on` et `is_online`, avec l'index
   `(is_online, publish_on)` déclaré au schéma.
3. **Retrouver un morceau qu'il a posté** — **gap**. `postActions::buildQuery()` restreint
   correctement la liste à ses propres morceaux et la trie par date décroissante, mais
   `generator.yml` désactive les filtres (`filter: class: false`) : ni filtre ni recherche,
   sur 993 morceaux chez le plus prolifique. Sur le site public, `Searchable` n'indexe que
   `track_author` et `track_title` — pas le message, pas le contributeur.

Parcours du **DJ de soirée** :

```
  Ouvrir ──► Chercher ──► Parcourir ──► Lancer
 supporté     partiel        gap       supporté
```

1. **Ouvrir le site** — **supporté**. 139 octets, une redirection vers le dernier morceau.
2. **Chercher** — **partiel**. Rapide et léger quand la mémoire est bonne : 43 ko en 0,35 s.
   Mais `Searchable` ne couvre que l'artiste et le titre. Une bribe du texte, un nom de
   contributeur : rien.
3. **Parcourir le catalogue** — **gap**. `/posts` sert 3,7 Mo et 8 097 liens, sans borne ni
   pagination. Sur le réseau d'une salle, c'est le chemin qu'on abandonne.
4. **Lancer le morceau** — **supporté**. Le lecteur jPlayer démarre, `?play=1` gère la
   lecture automatique.

Parcours du **mainteneur** :

```
  Changer un format ──► Vérifier ──► Livrer
       partiel            partiel    supporté
```

1. **Changer un format** — **partiel**. Les routes `/md5`, `/next`, `/prev`, `/random` ne
   sont couvertes par aucune requirement.
2. **Vérifier** — **partiel**. `test/functional/frontend/postActionsTest.php` couvre `/`,
   `/posts`, `/posts?format=json`, `/post/:slug` et `/posts/feed`. Les autres routes JSON,
   non.
3. **Livrer** — **supporté**. Conventional Commits, release-please, déploiement automatique
   à la poussée sur `main`.

## MoSCoW

### Must

- **Servir le type de contenu que la spec exige** — `JsonApiFilter` sert
  `application/vnd.api+json` là où le scénario « Formats reconnus » impose
  `application/json`. C'est le seul écart du périmètre qui soit une violation d'une spec
  existante, et non un manque. Étape 3 du parcours de l'intégrateur.
- **Borner les listes servies par `executeList`** — *élargi en révision du 2026-08-18.*
  8,4 Mo en JSON, 3,7 Mo en HTML, et `buildOnlinePostsQuery($contributor, $count)` accepte
  une limite depuis toujours sans qu'`executeList` ne la passe jamais. Le persona « DJ de
  soirée » a rendu intenable de borner le JSON en laissant la page HTML à 3,7 Mo : c'est la
  même action, le même paramètre, et c'est son étape 3 qui est un gap. Étape 3 de
  l'intégrateur, étape 3 du DJ. Le mélomane fêlé en profite sur sa playlist publique
  (1,0 Mo en JSON) — mais pas sur son propre gap, qui est l'admin sans recherche et qui
  relève d'un autre axe.
- **Une seule forme de morceau en JSON** — *reformulé en révision du 2026-08-18.*
  `/post/:slug` sert `{"posts":[…]}` et `/post/md5/:md5sum` sert l'objet nu : deux contrats
  pour le même objet, selon la façon dont on le désigne. `/posts/next|prev|random` sortent
  de ce compte : leur forme minimale est le contrat interne du lecteur du site. Étape 3.
- **Borner le XSPF** — *promu depuis Should en révision du 2026-08-18.* Le lien XSPF est
  offert au visiteur parmi les liens visibles d'une page de liste, la spec l'impose, et il
  coûte 2,7 à 3,1 s : l'étape 4 du parcours de l'auditeur sur le site. Ce n'était un
  « Should » que tant qu'on croyait ce format réservé aux intégrateurs — c'est un lien
  qu'un humain clique.

### Should

- **Un format d'erreur analysable** — un 404 en HTML sur `format=json` oblige tout client
  à traiter le cas hors de son chemin normal. Étape 4, le second gap du parcours.
- **Spécifier les routes JSON non couvertes** — sans quoi le mainteneur n'a rien contre
  quoi vérifier ce que les stories précédentes viennent de changer.

### Could

- **Publier la documentation d'API dans le site Antora** — règle l'étape 1 du parcours,
  mais c'est un travail de documentation, pas de format.
- **Corriger le `context:` de `openspec/config.yaml`** — il affirme qu'aucun test
  automatisé ne couvre le contrat public ; cinq routes le sont depuis. Une ligne.

### Won't (cette release)

- **Migrer vers JSON:API 1.0** — écarté sciemment, pour les trois raisons données en
  Sources. `docs/API_JSON_API_TARGET.md` reste au dépôt comme trace de l'option étudiée.
  À rouvrir si un consommateur réel réclame l'interopérabilité avec de l'outillage
  JSON:API générique.
- **Rendre le catalogue parcourable par un humain** — index par artiste, par mois, par
  contributeur. `PostTable` sait déjà le faire et seul Subsonic l'expose. C'est un autre
  axe : il mérite sa propre discovery. *La révision du 2026-08-18 lui verse trois besoins
  précis, pour qu'ils ne se perdent pas :*
  - **Une interface de pagination sur `/posts`** — le DJ de soirée. Cette release lui donne
    le *paramètre* pour demander moins ; elle ne lui donne pas les boutons « page
    suivante ». Le paramètre sans l'interface est un demi-service, et l'autre moitié est du
    travail de navigation.
  - **Une recherche qui couvre le corps et le contributeur** — le DJ et le mélomane fêlé.
    `Searchable` n'indexe que `track_author` et `track_title` (`schema.yml`). Élargir
    l'index est un changement de modèle, pas de format.
  - **Un filtre ou une recherche dans l'admin** — le mélomane fêlé. `generator.yml` porte
    `filter: class: false` ; il parcourt ses 993 morceaux vingt par vingt. C'est
    l'application `admin`, que cet axe ne touche pas du tout.
- **Toute la surface Subsonic** — *sortie du périmètre le 2026-08-18, avec son persona.*
  Le module `rest` a sa propre spécification, ses propres tests et sa propre documentation ;
  il est déjà exempté du filtre que la story 1 retire. Ce qui en sort avec lui : la
  pagination de `getArtists` (360 ko par rafraîchissement, limite connue et documentée), le
  rattrapage des durées et le transcodage. Les stories 1 et 5 continuent de **vérifier**
  qu'elles ne cassent pas `/rest` — le protéger n'est pas le travailler.

  Subsonic reste néanmoins l'argument qui a fait écarter la conformité JSON:API : c'est
  parce que cette surface existe et fonctionne que le JSON du module `post` peut rester une
  surface héritée qu'on stabilise au lieu de la faire croître.

## Stories

Liste ordonnée. Une story = un change OpenSpec (proposal ≈ 200 mots).
Chaque story est une tranche verticale : elle se démontre seule.

- [ ] 1. `retablir-le-type-de-contenu-json` — le JSON est servi avec le type que la spec exige
  - **Persona servi** : l'intégrateur
  - **Segment du parcours** : Récupérer (étape 3)
  - **MoSCoW** : Must
  - **Pourquoi celle-ci, pourquoi maintenant** : squelette ambulant. C'est la tranche la
    plus fine qui traverse toute la chaîne — filtre, réponse, consommateur — et elle se
    démontre par un seul `curl -I`. C'est aussi le seul écart du périmètre qui contredise
    une requirement déjà écrite : le scénario « Formats reconnus » impose
    `application/json`. Elle retire du code au lieu d'en ajouter.
  - **Dépend de** : rien
  - **Périmètre** — dedans : retirer `JsonApiFilter` et son entrée `json_api` de
    `filters.yml` ; retirer `JsonApiFilterTest` ; vérifier que `/rest` (Subsonic) et
    `/oembed`, que le filtre exemptait explicitement, conservent leur type de contenu ;
    vérifier que le type revient bien à `application/json` sur `/posts` et `/post/:slug`.
    — dehors : la forme du corps JSON, inchangée ; `ApiResponse`, qui reste sans appelant.
  - **Code concerné** : `src/lib/filter/JsonApiFilter.class.php`,
    `src/apps/frontend/config/filters.yml`, `src/test/unit/filter/JsonApiFilterTest.php`,
    `src/test/functional/frontend/postActionsTest.php`
  - **Ajoutée** : 2026-08-18
  - **Change** : _pas encore proposé_

- [ ] 2. `borner-les-listes-de-morceaux` — on demande une tranche du catalogue, pas les 8 097 morceaux
  - **Persona servi** : l'intégrateur, le DJ de soirée, le mélomane fêlé
  - **Segment du parcours** : Récupérer (intégrateur, étape 3) · Parcourir (DJ, étape 3) ·
    Montrer sa playlist (mélomane)
  - **MoSCoW** : Must
  - **Pourquoi celle-ci, pourquoi maintenant** : c'est la douleur mesurée de l'axe — 8,4 Mo
    en JSON, 3,7 Mo en HTML, 16,5 s de génération à froid — et le câble est déjà posé :
    `buildOnlinePostsQuery()` accepte un `$count` qu'`executeList` ne lui passe jamais. Le
    coût est dans la décision de valeur par défaut, pas dans le code.
  - **Dépend de** : rien
  - **Périmètre** — dedans : paramètres `limit` et `offset` sur `executeList`, qui vaut donc
    pour **tous** les formats qu'elle sert, HTML compris ; exposition du total ; comportement
    quand ils sont absents, négatifs ou non numériques ; interaction avec `q` et `c`, qui
    filtrent déjà. — dehors : l'**interface** de pagination du site — boutons « page
    suivante », numéros de page — qui relève de l'axe navigation ; les formats `xspf` et
    `max` (story 3).
  - **Ce qui a élargi cette story** : *révision du 2026-08-18.* Elle excluait explicitement
    la page HTML, au motif qu'elle relevait d'un autre axe. Le persona « DJ de soirée » a
    rendu la ligne intenable : la page HTML est sa douleur principale (3,7 Mo sur le réseau
    d'une salle), et c'est **la même action et le même paramètre** que le JSON. Exclure le
    HTML revenait à ne pas brancher un paramètre là où il est déjà écrit. Ce qui reste
    dehors, c'est l'interface de pagination — pas le paramètre.
  - **Question ouverte à trancher dans la proposal** : la valeur par défaut. Voir
    « Questions ouvertes », question 1 : elle porte maintenant sur trois personas.
  - **Code concerné** : `src/apps/frontend/modules/post/actions/actions.class.php`
    (`executeList`), `src/lib/model/doctrine/PostTable.class.php`
    (`buildOnlinePostsQuery`, `countOnlinePosts`),
    `src/apps/frontend/modules/post/templates/listSuccess.json.php`,
    `src/apps/frontend/modules/post/templates/listSuccess.php`
  - **Ajoutée** : 2026-08-18 · **Élargie** : 2026-08-18
  - **Change** : _pas encore proposé_

- [ ] 3. `borner-le-xspf` — la playlist XSPF cesse de coûter 3,1 secondes
  - **Persona servi** : l'auditeur sur le site (principal), l'intégrateur, le mélomane fêlé
  - **Segment du parcours** : Emporter la playlist (auditeur, étape 4) · Récupérer
    (intégrateur, étape 3) · Montrer sa playlist (mélomane, étape 3)
  - **MoSCoW** : Must — *promue depuis Should le 2026-08-18*
  - **Pourquoi celle-ci, pourquoi maintenant** : le XSPF n'est pas un format d'intégrateur.
    La spec `formats-de-sortie` impose qu'il figure parmi les **liens visibles proposés au
    visiteur** sur une page de liste. Un humain clique dessus et attend 2,7 à 3,1 s pour
    3,5 Mo. C'est la seule latence de cet axe sur un chemin humain.
  - **Dépend de** : story 2 (elle réutilise la convention de paramètre et la valeur par
    défaut qui y auront été tranchées)
  - **Périmètre** — dedans : `limit`/`offset` sur `format=xspf` ; le titre de playlist, que
    `formats-de-sortie` spécifie, doit rester juste quand la liste est tronquée ; le cas
    `?c=<contributeur>`, qui sert la playlist personnelle d'un mélomane (440 ko pour 993
    morceaux chez le plus prolifique). — dehors : le format `max`, sans douleur mesurée.
  - **Question ouverte à trancher dans la proposal** : ce que reçoit le visiteur qui clique
    le lien visible. Une troncature arbitraire le sert mal ; le plus juste est sans doute
    que le lien porte le contexte de la liste qu'il regarde. La réponse ne peut pas être la
    même pour lui et pour un intégrateur qui pagine.
  - **Code concerné** : `src/apps/frontend/modules/post/templates/listSuccess.xspf.php`,
    `src/apps/frontend/modules/post/templates/_xspfPlaylist.xspf.php`,
    `src/apps/frontend/modules/post/actions/actions.class.php` (`setFormats`)
  - **Ajoutée** : 2026-08-18
  - **Change** : _pas encore proposé_

- [ ] 4. `aligner-la-route-md5-sur-la-forme-commune` — un morceau récupéré par empreinte a la forme des autres
  - **Persona servi** : l'intégrateur
  - **Segment du parcours** : Récupérer (étape 3)
  - **MoSCoW** : Must
  - **Pourquoi celle-ci, pourquoi maintenant** : `/post/:slug` sert `{"posts":[…]}` et
    `/post/md5/:md5sum` sert l'objet nu. Deux contrats pour le même objet obligent un client
    à écrire deux analyseurs selon la façon dont il désigne le morceau.
  - **Dépend de** : story 1 (le type de contenu doit être stabilisé avant qu'on touche aux
    corps)
  - **Périmètre** — dedans : aligner `/post/md5/:md5sum` sur la forme des autres routes ;
    documenter que `/posts/next|prev|random` divergent **volontairement**. — dehors :
    modifier `/posts/next|prev|random`, et la forme commune elle-même, qui ne change pas.
  - **Ce qui a resserré cette story** : *révision du 2026-08-18.* Elle s'appelait
    `unifier-la-forme-json-du-morceau`, couvrait quatre routes et portait un avertissement
    de taille. L'ajout du persona « auditeur sur le site » a tranché : `layout.php` lit
    `data.url` et `data.title` sur quatre points d'appel, pour les raccourcis `j` / `k` / `r`
    et l'enchaînement du lecteur. La forme `{"url","title"}` de `/posts/next|prev|random`
    n'est pas une incohérence à corriger mais le contrat interne du lecteur : l'aligner
    casserait la navigation du site. La story se réduit donc à `/md5`, et l'avertissement de
    taille tombe.
  - **Code concerné** : `src/apps/frontend/modules/post/actions/actions.class.php`
    (`executeMd5`, `renderJsonPost`),
    `src/apps/frontend/modules/post/templates/md5Success.php`,
    `src/apps/frontend/templates/layout.php` (les quatre `$.get`, à ne pas casser)
  - **Ajoutée** : 2026-08-18 · **Resserrée** : 2026-08-18
  - **Change** : _pas encore proposé_

- [ ] 5. `servir-les-erreurs-en-json` — une erreur sur une route JSON revient en JSON
  - **Persona servi** : l'intégrateur
  - **Segment du parcours** : Encaisser l'erreur (étape 4)
  - **MoSCoW** : Should
  - **Pourquoi celle-ci, pourquoi maintenant** : c'est le second gap du parcours.
    Aujourd'hui `/post/<inconnu>?format=json` renvoie HTTP 404 avec un corps `text/html` —
    un client machine reçoit une page habillée. `ApiErrorResponse` est écrite et testée,
    et n'a jamais été appelée : la story la branche enfin.
  - **Dépend de** : story 4 (la forme du succès doit être fixée avant celle de l'échec)
  - **Périmètre** — dedans : slug inconnu, md5 inconnu, et le cas où la navigation
    next/prev n'a pas de suivant ; le corps porte le type de contenu du format demandé ; le
    code HTTP reste celui d'aujourd'hui. — dehors : `/rest`, qui suit le protocole Subsonic
    et répond 200 même en erreur ; `/oembed`, qui a sa propre spécification.
  - **Code concerné** : `src/lib/helper/ApiErrorResponse.php`,
    `src/apps/frontend/modules/post/actions/actions.class.php`,
    `src/apps/frontend/config/settings.yml`
  - **Ajoutée** : 2026-08-18
  - **Change** : _pas encore proposé_

- [ ] 6. `specifier-les-routes-json-non-couvertes` — chaque route JSON servie est décrite par un scénario
  - **Persona servi** : le mainteneur
  - **Segment du parcours** : Changer un format, Vérifier
  - **MoSCoW** : Should
  - **Pourquoi celle-ci, pourquoi maintenant** : elle vient en dernier parce qu'elle acte
    l'état atteint par les cinq précédentes. `formats-de-sortie` décrit la représentation
    JSON d'un morceau et d'une liste, mais ni `/md5`, ni next/prev/random, ni la
    pagination, ni le comportement en erreur.
  - **Dépend de** : stories 1 à 5
  - **Périmètre** — dedans : requirements et scénarios pour les routes et comportements
    ci-dessus ; couverture fonctionnelle des routes JSON que
    `test/functional/frontend/postActionsTest.php` ignore encore. — dehors : les specs
    `embarquement-oembed` et Subsonic, déjà tenues ailleurs.
  - **Code concerné** : `openspec/specs/formats-de-sortie/spec.md`,
    `src/test/functional/frontend/postActionsTest.php`
  - **Ajoutée** : 2026-08-18
  - **Change** : _pas encore proposé_

- [~] 7. ~~`paginer-getartists-subsonic`~~ — **supersédée le 2026-08-18**
  - **Raison** : son unique persona, « l'auditeur en client Subsonic », a été retiré du plan
    par l'auteur. Une story sans personne à servir n'est plus une story.
  - **Ce qu'elle disait**, pour qui la reprendrait : `getArtists` n'est pas paginé — environ
    360 ko retéléchargés à chaque rafraîchissement de bibliothèque, sur 5 100 artistes. La
    limite est connue et déjà consignée dans `docs/API_SUBSONIC.md`, qui note aussi que le
    protocole Subsonic ne prévoit pas de pagination sur cette méthode. Code concerné :
    `src/apps/frontend/modules/rest/actions/actions.class.php`,
    `src/lib/model/doctrine/PostTable.class.php` (`getDistinctArtists`).
  - **Où la reprendre** : dans une discovery consacrée à la surface Subsonic, ou au moment
    où un client réellement utilisé s'en plaindra.
  - **Ajoutée** : 2026-08-18 · **Supersédée** : 2026-08-18

- [ ] 8. `publier-la-documentation-d-api` — l'intégrateur trouve l'API sans lire le code
  - **Persona servi** : l'intégrateur
  - **Segment du parcours** : Découvrir (étape 1)
  - **MoSCoW** : Could
  - **Pourquoi celle-ci, pourquoi maintenant** : elle ferme le dernier gap du parcours, et
    elle vient après les autres parce qu'elle documente ce qu'elles auront changé —
    documenter avant, c'est réécrire deux fois. `docs/antora.yml` déclare
    `nav: modules/ROOT/nav.adoc`, absent du dépôt ; les trois documents d'API sont du
    Markdown à la racine de `docs/`, hors du site Antora.
  - **Dépend de** : stories 1 à 6
  - **Périmètre** — dedans : créer le `nav.adoc` manquant ; verser la documentation d'API
    dans `docs/modules/ROOT/pages/` ; y refléter l'état atteint. — dehors :
    `docs/API_JSON_API_TARGET.md`, qui décrit une option écartée et reste une archive.
  - **Code concerné** : `docs/antora.yml`, `docs/modules/ROOT/`, `docs/API_CURRENT_STATE.md`,
    `docs/API_SUBSONIC.md`
  - **Ajoutée** : 2026-08-18
  - **Change** : _pas encore proposé_

- [ ] 9. `corriger-le-contexte-openspec` — le contexte projet cesse d'affirmer un faux
  - **Persona servi** : le mainteneur
  - **Segment du parcours** : Vérifier
  - **MoSCoW** : Could
  - **Pourquoi celle-ci, pourquoi maintenant** : le `context:` de `openspec/config.yaml`
    affirme « aucun test automatisé ne le couvre aujourd'hui » à propos du contrat public,
    alors que `test/functional/frontend/postActionsTest.php` couvre cinq routes. Ce
    contexte est injecté dans les instructions de chaque artefact : l'erreur se propage à
    tout ce qu'OpenSpec produit. À faire quand la story 6 aura fixé la couverture réelle.
  - **Dépend de** : story 6
  - **Périmètre** — dedans : la phrase fautive, mise à jour d'après la couverture réelle.
    — dehors : le reste du `context:`, vérifié exact.
  - **Code concerné** : `openspec/config.yaml`
  - **Ajoutée** : 2026-08-18
  - **Change** : _pas encore proposé_

## Questions ouvertes

1. **La valeur par défaut de la pagination** (bloque les stories 2 et 3). Borner par défaut
   règle le poids pour tout le monde mais change ce que reçoit un appelant existant. Ne pas
   borner préserve le contrat et laisse la douleur à qui ignore le nouveau paramètre.
   Deux arguments se sont accumulés en révision du 2026-08-18, et ils poussent tous deux
   vers le bornage par défaut :
   - à froid, le JSON global met **16,5 s** à se générer. Ce n'est pas seulement du poids
     sur le réseau, c'est du temps de calcul sur l'hôte à chaque expiration de cache ;
   - le **DJ de soirée** ne connaîtra jamais le paramètre. Un défaut non borné ne protège
     que les appelants qui lisent la documentation — c'est-à-dire pas lui, et pas le
     visiteur. Le défaut est ce que reçoit celui qui ne demande rien.
   La décision porte donc sur trois personas, dont deux ne sauront jamais qu'un paramètre
   existe.

2. ~~**Qui consomme `/posts/next|prev|random` ?**~~ — **tranchée le 2026-08-18.** Le site
   lui-même, via `layout.php` : quatre `$.get` qui lisent `data.url` et `data.title`, pour
   les raccourcis `j` / `k` / `r`, le bouton aléatoire et l'enchaînement du lecteur. Ces
   routes ne seront pas alignées ; leur divergence sera documentée. Voir story 4.

3. **Qui consomme le JSON ?** — toujours ouverte, et sans candidat solide. La révision
   avait avancé Radio Approximative ; l'auteur a corrigé : ce n'est pas ce que fait le
   mélomane fêlé, et rien dans le dépôt n'établit que ce projet lise le JSON. L'hypothèse
   est retirée. Aucun consommateur du JSON n'est identifié à ce jour — ce qui est en soi un
   argument pour la question 1 : on ne peut pas casser prudemment un contrat dont on ignore
   les usagers, mais on ne peut pas non plus le figer indéfiniment pour des usagers
   hypothétiques.

4. **Reste-t-il des patchs Max/MSP en service ?** Le format `max` existe et a été réparé en
   2026-08. Il n'est borné par aucune story, faute de douleur mesurée — mais s'il a des
   utilisateurs, il mérite le traitement du XSPF. Même remarque que pour la question 3 : la
   réponse est chez les contributeurs, pas dans le code.

## Change Log

- 2026-08-18 — Création. Axe « tenir les formats machine » retenu parmi quatre proposés.
  Cadrage **cohérence** retenu contre **conformité JSON:API 1.0**, cette dernière versée
  en « Won't » avec ses motifs. Neuf stories.

- 2026-08-18 (révision) — Deux personas ajoutés : **l'auditeur sur le site** et **le
  mélomane fêlé**, le contributeur. Trois conséquences, toutes tirées du code :
  la question ouverte n°2 est tranchée (le site consomme `/posts/next|prev|random`) ;
  la story 4 passe de quatre routes à une, perd son avertissement de taille et change de
  nom — `unifier-la-forme-json-du-morceau` devient
  `aligner-la-route-md5-sur-la-forme-commune` ; `borner-le-xspf` monte de Should à Must,
  le lien XSPF étant offert au visiteur et coûtant 2,7 à 3,1 s. Aucune story n'a été
  ajoutée ni supprimée : les deux personas ne réclament rien de neuf, ils réordonnent et
  resserrent ce qui était déjà là.

- 2026-08-18 (seconde révision) — Le mélomane fêlé est corrigé : l'hypothèse Radio
  Approximative était une inférence de la discovery, pas un usage réel, et elle est retirée
  de la question 3. Ses deux besoins réels — poster facilement, retrouver facilement —
  déplacent son étape 3 de « partiel » à **gap** : `generator.yml` désactive les filtres de
  l'admin, et `Searchable` n'indexe que l'artiste et le titre. Un sixième persona ajouté,
  **le DJ de soirée**, dont l'étape « Parcourir » est un gap à 3,7 Mo. Conséquence sur le
  plan : la story 2 s'élargit à tous les formats servis par `executeList`, page HTML
  comprise, et prend le nom `borner-les-listes-de-morceaux` — exclure le HTML revenait à ne
  pas brancher un paramètre là où il est déjà écrit. L'interface de pagination, elle, reste
  hors périmètre. Trois besoins des nouveaux personas sont versés nommément en « Won't »,
  car ils relèvent de l'axe navigation : la pagination visible, l'élargissement de l'index
  de recherche, et un filtre dans l'admin. Aucune story ajoutée.

- 2026-08-18 (troisième révision) — Le persona « l'auditeur en client Subsonic » est retiré
  du plan à la demande de l'auteur. Son parcours et son entrée « Could » disparaissent avec
  lui ; la story 7 est **supersédée** plutôt que supprimée, avec son contenu conservé pour
  qui la reprendrait. Toute la surface Subsonic passe en « Won't » d'un bloc : le module
  `rest` a sa spécification, ses tests et sa documentation propres, et les stories 1 et 5
  se contentent de vérifier qu'elles ne le cassent pas. L'argument Subsonic reste en place
  là où il fonde le rejet de la conformité JSON:API — c'est un fait sur la surface, pas sur
  le persona. Le plan retient cinq personas et huit stories actives.
