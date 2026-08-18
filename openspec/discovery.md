# Discovery : Musique Approximative

> Status: complete
> Created: 2026-08-18 · Last revised: 2026-08-18 (neuvième révision)

> **Portée : généraliste.** Ce plan a d'abord été rédigé sur le seul axe « tenir les
> formats machine ». L'auteur a corrigé le 2026-08-18 : ce n'est pas un plan d'axe, c'est
> le plan de release du projet. Les formats machine en restent la matière principale
> — c'est là qu'on a mesuré — mais rien n'y est exclu au motif de « relever d'un autre
> axe ». Voir le Change Log.

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
- 2026-08-18 (quatrième révision) — Mise à jour depuis une session de travail qui n'a
  touché aucune story de ce plan, mais en a déplacé le terrain : cinq changements archivés
  sur `desastres` et l'outillage de test. Voir « Ce que la session a changé pour ce plan ».
- 2026-08-18 (septième révision) — L'auteur apporte un axe neuf et le classe **Must** :
  le support complet de l'Unicode. L'exploration a montré que ce n'est pas une question
  d'affichage mais **d'encodage de la base de production**, avec des données déjà détruites.
- 2026-08-18 (sixième révision) — L'auteur tranche la question ouverte n°1 : **la
  pagination borne à 50 par défaut**. Les stories 2 et 3 sont débloquées. Entrée du jour
  également : la livraison de la story 1, premier amendement au contrat OpenAPI.
- 2026-08-18 (cinquième révision) — Deux apports de l'auteur. D'abord une correction de
  portée : **ce plan est généraliste**, il ne se limite pas aux formats machine. Ensuite
  la promotion en Must du travail d'accès mobile. Entrée du jour également : la livraison
  de la story 10, qui a produit quatre relevés machine versés aux stories concernées, et
  l'archivage de `fix-recherche-mobile`, livré en production sans avoir jamais figuré à ce
  plan.
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

## Ce que la session du 2026-08-18 a changé pour ce plan

Cinq changements ont été archivés ce jour-là. **Aucun ne correspond à une story de ce
plan** : ils portaient sur la spec `desastres` et sur l'outillage de test. Ils modifient
néanmoins le terrain sur lequel les stories seront construites.

### Le schéma OpenSpec a changé

`openspec/config.yaml` déclare désormais `schema: behaviour-driven` en lieu et place du
schéma sur mesure `musique-approximative`. Conséquence directe pour ce plan : le nouveau
schéma ajoute un artefact **`design`**, et `tasks` en dépend. Chaque story coûte donc un
document de plus qu'au moment où ce plan a été écrit.

La règle de dimensionnement — « proposal ≈ 200 mots » — reste juste, mais elle ne décrit
plus le coût total d'une story. Pour les stories manifestement non structurantes, le
`design.md` peut se réduire à une décision unique ; c'est ce qu'ont fait les changements de
cette session.

### L'environnement de test voit maintenant le cache

`settings.yml` déclare `cache: true` pour l'environnement `test`. La suite fonctionnelle
peut donc observer une réponse servie depuis le cache, et non plus seulement le premier
visiteur.

C'est directement utile à la **story 1**. `filters.yml` place `json_api` sous `cache`
délibérément :

> « Le Content-Type doit etre reecrit avant que `sfCacheFilter` n'ecrive l'entree, sinon la
> reponse mise en cache porte le type d'origine. »

Retirer `JsonApiFilter` supprime ce besoin, mais la story doit démontrer qu'elle ne
ressuscite pas cette classe de bug. C'était impossible à vérifier quand ce plan a été
écrit ; ça ne l'est plus.

### La suite de tests est verte, et exécutable en local

**16 fichiers, 461 tests, aucun échec.** Une cible `make test-init` prépare la base de
test. Les trois scripts qui échouaient ne révélaient aucun défaut de code : une base
absente et un cache d'autochargement périmé.

Ce qui rend la **story 6** — spécifier et couvrir les routes JSON non couvertes —
nettement moins chère qu'annoncé : il n'y a plus d'environnement à débloquer avant de
pouvoir écrire un test.

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

## Ce que « support complet de l'Unicode » recouvre

*Relevé du 2026-08-18, mesuré sur le dump de production et non déduit du code.*

### La racine

| | jeu de caractères |
| --- | --- |
| base de production | **`latin1` / `latin1_swedish_ci`** |
| `post`, `post_index`, toutes les tables `sf_guard_*` | `latin1` |
| `user_profile` | `utf8` — seule exception, incohérence en soi |
| base de test | `utf8` — trois octets, **ne porte pas les emoji** |
| DSN | **aucun `charset`** ; la connexion négocie `utf8` côté client, `latin1` côté base |

MySQL **convertit** donc à chaque écriture : le client envoie de l'UTF-8, la base range du
latin1. Les migrations Doctrine déclarent pourtant `'charset' => 'UTF8'` — l'intention était
là, la production ne l'a jamais suivie.

### Ce qui passe, et ce qui est détruit

Le `latin1` de MySQL est en réalité cp1252, plus large qu'ISO-8859-1. Mesuré :

| entrée | ce que la base en fait |
| --- | --- |
| `Sigur Rós`, `Björk`, `für Elise` | **intact** |
| `—` cadratin, `…`, apostrophe courbe, `€` | **intact** |
| `Сергей Прокофьев` | `?????? ?????????` |
| `坂本龍一` | `????` |
| `Δ Ω μ` | `? ? ?` |
| `🎵` | `?` (octet `3F`) |

La conversion est **silencieuse et irréversible**. Le `?` ne revient pas en arrière.

### Les dégâts déjà faits

**56 morceaux** portent un titre ou un auteur détruit — `?` en milieu de mot ou doublé, ce
qui exclut la ponctuation légitime :

```
Ko?ysanka Kapitana                     ← polonais « Kołysanka » : le ł n'est pas dans cp1252
????? — ??????                         ← titre entièrement cyrillique
??????????? ????? ??????????? ??????   ← idem
Andrei Rodionov & Tikhomirov Boris — ????????? ?????????
```

Ces données **ne sont pas récupérables depuis la base**. Migrer l'encodage empêchera les
pertes futures ; il ne rendra pas celles-ci.

### Pourquoi c'est un Must, et pourquoi c'est urgent

Musique Approximative est une playlist quotidienne, **vivante** : 8 097 morceaux, du
2008-06-10 au **17 août 2026**, dont 490 publiés en 2025-2026. Le cyrillique, le polonais,
le roumain et le turc n'y sont pas des cas limites : ce sont des musiques que le collectif
poste, et dont les titres sont effacés à la publication sans que personne n'en soit averti.

**La destruction est en cours.** Mesurée sur le corpus servi en production :

| | |
| --- | --- |
| morceaux au titre ou à l'auteur détruit | **81** |
| contributeurs concernés | **37** |
| détruits depuis 2022 | **22** |
| détruits en 2026 | **5** |
| caractères hors cp1252 dans les 8 097 morceaux servis | **0** |

Ce dernier chiffre est le plus parlant : sur dix-huit ans et huit mille morceaux, **pas un
seul caractère hors cp1252 n'a survécu**. Ce n'est pas que le collectif n'en poste pas —
c'est qu'aucun ne peut passer.

Échantillon de dégâts, où l'on voit la frontière de cp1252 passer au milieu d'un nom :

```
Pawe? Zadro?niak        ← Paweł Zadrożniak : ł et ż détruits
Somnoroase P?s?rele     ← roumain
Özdemir Erdo?an         ← le Ö passe (cp1252), le ğ turc non
Los Piran?as
????? ?????????         ← titre entièrement cyrillique
```

### Une erreur de méthode, consignée parce qu'elle se reproduira

La première rédaction de cette section concluait que **le site était dormant** — « dernier
morceau publié le 14 octobre 2021, zéro publication depuis » — et en tirait qu'il n'y avait
pas d'urgence, seulement une archive à réparer. C'était faux, et l'auteur l'a corrigé.

L'erreur venait de la source : la base de développement porte **un dump de production vieux
de cinq ans** — 6 103 morceaux contre 8 097 en ligne, arrêté en 2021. Il a été lu comme s'il
était la production.

Ce qui doit rester de cet épisode, pour quiconque reprendra ce plan : **la base locale n'est
pas la production, et l'écart se compte en années.** Toute mesure de volume, de date ou de
contenu doit venir du site en ligne, comme celles du tableau ci-dessus.

## MoSCoW

### Must

- **Publier un contrat OpenAPI de l'état actuel** — *ajouté le 2026-08-18.* C'est le
  nouveau squelette ambulant : il ne change rien à l'API, il la décrit, et chaque story
  suivante devient un amendement dont le diff est le journal. Il force par ailleurs les
  décisions que ce plan diffère — on n'écrit pas un `default` de pagination sans le
  trancher. Étape 1 du parcours de l'intégrateur.
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
  (1,0 Mo en JSON) — mais pas sur son propre gap, qui est l'admin sans recherche. Celui-ci
  est désormais en « Could » (remonté le 2026-08-18), et non plus écarté.
- **Une seule forme de morceau en JSON** — *reformulé en révision du 2026-08-18.*
  `/post/:slug` sert `{"posts":[…]}` et `/post/md5/:md5sum` sert l'objet nu : deux contrats
  pour le même objet, selon la façon dont on le désigne. `/posts/next|prev|random` sortent
  de ce compte : leur forme minimale est le contrat interne du lecteur du site. Étape 3.
- **La base porte tout l'Unicode** — *ajouté le 2026-08-18, à la demande de l'auteur.*
  La base de production est en `latin1` : tout caractère hors cp1252 est remplacé par `?`
  à l'écriture, silencieusement et sans retour. **56 morceaux ont déjà un titre ou un
  auteur détruit**, 37 contributeurs concernés, **22 depuis 2022 et 5 en 2026**. C'est le
  seul défaut de ce plan qui **détruise de la donnée** plutôt que de mal la servir, et il
  est en cours : le site publie quotidiennement. Sur 8 097 morceaux et dix-huit ans, **pas
  un seul caractère hors cp1252 n'a survécu**.
- **Le champ de recherche existe sur téléphone** — *inscrit rétroactivement le
  2026-08-18.* `main.css` le masquait sous 768 px. Le DJ de soirée n'avait pas de chemin
  vers `/posts?q=…` depuis son téléphone : son étape 2 était un gap complet, pas un
  « partiel ». **Livré** (story 13).
- **Le visiteur peut agrandir la page** — *ajouté le 2026-08-18.* `layout.php:7` le lui
  interdit. Une ligne à retirer, sans contrepartie depuis que le champ de recherche est à
  16 px. Aucun recours n'existe côté site ; les seuls qui existent ont été créés par les
  navigateurs contre l'auteur du site. Étape de tous les parcours servis en HTML
  (story 14).
- **Borner le XSPF** — *promu depuis Should en révision du 2026-08-18.* Le lien XSPF est
  offert au visiteur parmi les liens visibles d'une page de liste, la spec l'impose, et il
  coûte 2,7 à 3,1 s : l'étape 4 du parcours de l'auditeur sur le site. Ce n'était un
  « Should » que tant qu'on croyait ce format réservé aux intégrateurs — c'est un lien
  qu'un humain clique.

### Should

- **Réparer la navigation du site de documentation** — *ajouté le 2026-08-18.* Sept pages
  publiées et inatteignables, faute d'un `nav.adoc` que `antora.yml` déclare pourtant. Ce
  n'est pas de l'axe « formats machine », mais c'est la condition pour que quoi que ce soit
  de publié soit lu — y compris le contrat d'API.
- **Un format d'erreur analysable** — un 404 en HTML sur `format=json` oblige tout client
  à traiter le cas hors de son chemin normal. Étape 4, le second gap du parcours.
- **Spécifier les routes JSON non couvertes** — sans quoi le mainteneur n'a rien contre
  quoi vérifier ce que les stories précédentes viennent de changer.

### Could

> Les trois premières entrées viennent de « Won't », d'où la correction de portée du
> 2026-08-18 les a sorties. Elles n'y étaient pas pour leur mérite propre mais parce que
> le plan se croyait cantonné aux formats machine — motif qui n'existe plus. Un cran, pas
> deux : elles n'ont pas encore de story, et n'en auront qu'une fois promues en « Should »
> ou au-dessus. Les stories se taillent dans les « Must », puis dans les « Should ».

- **Publier la page Subsonic dans le site Antora** — *remplace « publier la documentation
  d'API », le 2026-08-18.* `API_SUBSONIC.md` est bon et illisible, hors du site. Le reste
  de l'ancienne entrée est parti ailleurs : le contrat d'API est passé en « Must », et la
  navigation en « Should ».
- **Un filtre ou une recherche dans l'admin** — *remonté de « Won't » le 2026-08-18.*
  Le mélomane fêlé, et c'est **son seul gap** : `generator.yml` porte
  `filter: class: false`, il parcourt ses 993 morceaux vingt par vingt. Ce plan dit de lui
  que son besoin est « poster facilement et retrouver facilement » ; retrouver est la
  moitié qu'il n'a pas. Il était écarté au seul motif de relever d'un autre axe — motif
  tombé avec la correction de portée. Application `admin`, que rien d'autre à ce plan ne
  touche. `postActions::buildQuery()` restreint déjà correctement la liste à ses propres
  morceaux : c'est la ligne `filter` qui manque, pas la requête.
- **Une recherche qui couvre le corps et le contributeur** — *remonté de « Won't » le
  2026-08-18.* Le DJ de soirée et le mélomane fêlé. `Searchable` n'indexe que
  `track_author` et `track_title` (`schema.yml`) : une bribe du texte accompagnant le
  morceau, un nom de contributeur, ne trouvent rien. C'est un changement de modèle et non
  de format — le plus coûteux des trois, et le seul qui touche `schema.yml`.
- **Une interface de pagination sur `/posts`** — *remonté de « Won't » le 2026-08-18.*
  Le DJ de soirée. La story 2 lui donne le *paramètre* pour demander moins ; elle ne lui
  donne pas les boutons « page suivante ». Un paramètre sans interface est un demi-service,
  et le DJ ne connaîtra jamais le paramètre. **Dépend de la story 2**, qui doit avoir
  tranché sa convention avant qu'on dessine des boutons dessus.
- **Un garde-fou à la saisie** — *ajouté le 2026-08-18.* `track_title` et `track_author`
  n'ont **aucun validateur** : le formulaire accepte un caractère que la base détruira, sans
  rien dire. Un validateur qui le signale transformerait une mutilation silencieuse en
  message. **Conditionnel** : le rythme est d'environ cinq morceaux par an et la story 19
  supprime la cause — ce garde-fou n'a de sens que si la migration tarde. À promouvoir en
  Must si elle glisse, à abandonner dès qu'elle est livrée.
- **Remplacer l'algorithme de hachage de sfGuard** — *ajouté le 2026-08-18.* `sha1` sans
  étirement de clé, avec un sel par compte. Ce n'est pas ce qui a causé la fuite, mais c'est
  ce qui la rend exploitable. Hors périmètre de la story 22, qui traite l'exposition et non
  le stockage.
- **Corriger le `context:` de `openspec/config.yaml`** — il affirme qu'aucun test
  automatisé ne couvre le contrat public ; cinq routes le sont depuis. Une ligne.

### Won't (cette release)

- **Migrer vers JSON:API 1.0** — écarté sciemment, pour les trois raisons données en
  Sources. `docs/API_JSON_API_TARGET.md` reste au dépôt comme trace de l'option étudiée.
  À rouvrir si un consommateur réel réclame l'interopérabilité avec de l'outillage
  JSON:API générique.
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

- [x] 1. `retablir-le-type-de-contenu-json` — le JSON est servi avec le type que la spec exige
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
    `filters.yml` ; retirer `JsonApiFilterTest` ; corriger l'assertion fonctionnelle qui
    verrouille le type actuel ; vérifier que `/rest` (Subsonic) et `/oembed`, que le filtre
    exemptait explicitement, conservent leur type de contenu ; vérifier que le type revient
    bien à `application/json` sur `/posts` et `/post/:slug`.
    — dehors : la forme du corps JSON, inchangée ; `ApiResponse`, qui reste sans appelant.
  - **Relevé du 2026-08-18, corrigé à la proposition** : le packet annonçait **six**
    assertions sur deux fichiers. Le compte réel est de **dix sur trois** —
    `unit/filter/JsonApiFilterTest.php` en porte **huit** (et non cinq), dont **trois**
    `unlike` et non deux ; `functional/frontend/postActionsTest.php:25` en porte une ;
    `functional/frontend/restActionsTest.php:37` en porte une, qui reste vraie après le
    retrait mais dont le libellé nomme le filtre. S'ajoutent **six déclarations au contrat
    OpenAPI**, qui n'existait pas quand ce packet a été écrit : le test de contrat échoue
    à la seconde où le filtre disparaît si le document n'est pas amendé dans le même
    geste. C'est le premier amendement, et le dispositif fonctionne comme prévu. Retirer le fichier de test unitaire ne suffit
    donc pas : le comportement que ses deux `unlike` protègent — `oembed` sert
    `application/json` — doit rester vrai après le retrait du filtre.
  - **Piège à ne pas ressusciter** : `filters.yml` place `json_api` **sous** `cache`
    délibérément, « sinon la reponse mise en cache porte le type d'origine ». C'est un bug
    déjà corrigé une fois. Depuis `2026-08-18-activer-le-cache-en-test`, l'environnement de
    test met en cache : la story peut et doit démontrer qu'une réponse servie depuis le
    cache porte le bon type.
  - **Code concerné** : `src/lib/filter/JsonApiFilter.class.php`,
    `src/apps/frontend/config/filters.yml`, `src/test/unit/filter/JsonApiFilterTest.php`,
    `src/test/functional/frontend/postActionsTest.php`
  - **Ajoutée** : 2026-08-18
  - **Change** : `2026-08-18-retablir-le-type-de-contenu-json` — **livré le 2026-08-18**

- [~] 2. `borner-les-listes-de-morceaux` — **en attente depuis le 2026-08-18**
  - **Motif** : *un consommateur réel de la page non bornée a été identifié — l'auteur
    lui-même.* Il se sert beaucoup de `/posts` en HTML **parce qu'on peut y faire Ctrl+F
    efficacement**. C'est le premier usage réel qu'ait reçu ce plan : sa question ouverte
    n°3 disait « aucun consommateur identifié », et cette réponse-là est arrivée par
    l'auteur, pas par le code.
  - **Pourquoi ce motif est plus fort qu'une préférence** : `listSuccess.php` rend chaque
    ligne sous la forme `artiste — titre (contributeur)`. Or `Searchable` n'indexe que
    `track_author` et `track_title` (`schema.yml`) : **le contributeur n'est pas cherchable
    par la recherche du site**. Ctrl+F sur la page complète l'est, instantanément et sans
    aller-retour réseau. La page non bornée est donc le **contournement d'une
    fonctionnalité manquante** — celle-là même que ce plan porte en « Could » sous le nom
    « une recherche qui couvre le corps et le contributeur ». La borner retirerait le
    contournement avant de livrer ce qu'il remplace.
  - **Ce que ça retourne dans ce plan** : le persona « DJ de soirée » avait servi à élargir
    cette story à la page HTML, au motif que 3,7 Mo sur le réseau d'une salle est un piège.
    C'était une hypothèse. L'usage Ctrl+F est un fait, rapporté en première personne. Quand
    une hypothèse et un fait se contredisent sur le même écran, c'est le fait qui décide —
    et il décide **pour la page HTML seulement**.
  - **Question qui reste ouverte** : le motif Ctrl+F ne vaut que pour le HTML. Aucun Ctrl+F
    ne s'applique à `format=json`, `xspf` ou `max`, qui pèsent 8,4 / 3,5 / 1,8 Mo et n'ont
    pas d'usage identifié. Faut-il les borner seuls ? Non tranché.
  - **Ce qui est conservé** : la proposition écrite le 2026-08-18 est retirée de
    `openspec/changes/` pour ne pas être prise par erreur comme prochaine story. Son
    contenu reste dans l'historique — trois relevés y valent d'être repris si la story
    repart : `search()` renvoie un tableau et non une requête ; les titres comptent les
    morceaux servis et mentiraient une fois bornés ; une limite négative vaut aujourd'hui
    « aucune limite » et contournerait le bornage.
  - **Ajoutée** : 2026-08-18 · **Élargie** : 2026-08-18 · **Mise en attente** : 2026-08-18
  - **Change** : `borner-les-listes-de-morceaux`, proposé puis retiré le 2026-08-18

- [ ] 3. `borner-le-xspf` — la playlist XSPF cesse de coûter 3,1 secondes
  - **Persona servi** : l'auditeur sur le site (principal), l'intégrateur, le mélomane fêlé
  - **Segment du parcours** : Emporter la playlist (auditeur, étape 4) · Récupérer
    (intégrateur, étape 3) · Montrer sa playlist (mélomane, étape 3)
  - **MoSCoW** : Must — *promue depuis Should le 2026-08-18*
  - **Pourquoi celle-ci, pourquoi maintenant** : le XSPF n'est pas un format d'intégrateur.
    La spec `formats-de-sortie` impose qu'il figure parmi les **liens visibles proposés au
    visiteur** sur une page de liste. Un humain clique dessus et attend 2,7 à 3,1 s pour
    3,5 Mo. C'est la seule latence de cet axe sur un chemin humain.
  - **Dépend de** : ~~story 2~~ — **plus rien, depuis le 2026-08-18.** La story 2 est en
    attente, sa convention de paramètre n'existera donc pas. Le motif qui l'a suspendue —
    Ctrl+F sur la page complète — **ne s'applique pas au XSPF** : c'est un fichier de
    playlist, on n'y cherche pas au clavier, et sa douleur est une latence de 2,7 à 3,1 s
    sur un lien qu'un humain clique. Cette story peut donc partir seule, en tranchant sa
    propre convention.
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

- [x] 4. `aligner-la-route-md5-sur-la-forme-commune` — un morceau récupéré par empreinte a la forme des autres
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
  - **Correction du 2026-08-18, à la proposition** : la révision précédente affirmait que
    `/post/md5/` exposait des champs que `formats-de-sortie` dit absents, et en tirait un
    second écart à corriger. **C'était faux, et l'erreur venait du relevé de la story 10.**
    Vérification faite en comparant les deux réponses : elles servent le **même objet, aux
    mêmes clés** — `publish_on`, `created_at`, `updated_at`, `track_duration`, `track_size`
    et `buy_url` figurent dans les deux, parce que les deux passent par `Post::toJson()`.
    Rien n'est propre à `/md5`. Et rien n'est interdit : les trois prohibitions nommées par
    le scénario « Champs jamais exposés » — mise en ligne, révision, objet utilisateur —
    portent sur `is_online`, `svn_revision` et `sfGuardUser`, qui sont bien retirés. Les
    autres champs ne sont pas interdits, ils ne sont pas décrits : c'est un manque de la
    spec, qui relève de la story 6, non un défaut du code.
    **La story 4 reste donc ce qu'elle était : l'enveloppe, et rien d'autre.**
  - **Code concerné** : `src/apps/frontend/modules/post/actions/actions.class.php`
    (`executeMd5`, `renderJsonPost`),
    `src/apps/frontend/modules/post/templates/md5Success.php`,
    `src/apps/frontend/templates/layout.php` (les quatre `$.get`, à ne pas casser)
  - **Ajoutée** : 2026-08-18 · **Resserrée** : 2026-08-18
  - **Change** : `2026-08-18-aligner-la-route-md5-sur-la-forme-commune` — **livré le 2026-08-18**

- [x] 5. `servir-les-erreurs-en-json` — une erreur sur une route JSON revient en JSON
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
  - **Ce que la story 10 a changé pour elle** : *2026-08-18.* Elle croyait n'avoir qu'à
    formater des 404. Le relevé machine dit autre chose : `/post/md5/<inconnu>` et
    `/posts/next|prev` sans `current` ne renvoient pas 404 mais une **erreur fatale PHP**.
    `executeMd5()` appelle `toJson()` sur `false` sans `forward404Unless` préalable
    (`actions.class.php:157`) ; `getNextPost()` reçoit `false` là où sa signature exige un
    `Post` (`:299` et `:305`). La story a donc d'abord des 500 à supprimer, ensuite des
    404 à habiller. C'est plus de travail qu'annoncé, et c'est un défaut plus grave.
  - **Code concerné** : `src/lib/helper/ApiErrorResponse.php`,
    `src/apps/frontend/modules/post/actions/actions.class.php`,
    `src/apps/frontend/config/settings.yml`
  - **Ajoutée** : 2026-08-18
  - **Change** : `2026-08-18-servir-les-erreurs-en-json` — **livré le 2026-08-18**

- [x] 6. `specifier-les-routes-json-non-couvertes` — chaque route JSON servie est décrite par un scénario
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
  - **Devenue moins chère le 2026-08-18** : écrire un test fonctionnel ne demande plus de
    débloquer l'environnement au préalable. `make test-init` prépare la base, la suite est
    verte — 16 fichiers, 461 tests — et l'environnement de test met en cache. Le coût
    annoncé de cette story supposait un environnement qu'il fallait d'abord réparer.
  - **Code concerné** : `openspec/specs/formats-de-sortie/spec.md`,
    `src/test/functional/frontend/postActionsTest.php`
  - **Reformulée le 2026-08-18, et c'est l'audit qui l'a imposé** : la moitié « spécifier »
    était **déjà faite**. Les stories 1, 4 et 5 ont écrit en passant les scénarios de
    `/post/md5/`, des routes de navigation et du comportement en erreur ; la pagination n'en
    a pas, mais sa story est en attente. L'audit a compté **59 scénarios** sur les deux
    capacités de cet axe, et une preuve mince : quinze assertions dans `postActionsTest`,
    dix sur le type et le cache, et le test de contrat qui ne regarde que statut, type et
    champs de premier niveau. Le mainteneur n'a donc pas un problème de description, **il a
    un problème de preuve**. La story continue sous le nom
    `verifier-les-representations-machine`.
  - **Ajoutée** : 2026-08-18 · **Reformulée** : 2026-08-18
  - **Change** : `2026-08-18-verifier-les-representations-machine` — **livré le 2026-08-18**.
    Couverture portée de ~12 à ~50 scénarios sur 59 ; 507 → 602 tests. Trois trouvailles :
    l'adresse du fichier audio (corrigée, story 15), le tirage aléatoire qui peut servir une
    page morte (**non corrigé**, story 16), et l'index de recherche vide en test (corrigé
    dans le bootstrap).

- [x] 15. `unifier-l-adresse-du-fichier-audio` — un fichier audio a une seule adresse
  - **Persona servi** : l'intégrateur, et quiconque suit une adresse de fichier
  - **Segment du parcours** : Récupérer (étape 3)
  - **MoSCoW** : Must
  - **Née d'une vérification, non d'un plan** : trouvée en écrivant la couverture de la
    story 6. Le même morceau était servi sous deux adresses selon la représentation —
    et `/post/{slug}?format=max` rendait `http://localhost/tracks/un titre.mp3`, **espace
    brut, URL cassée**. Le scénario « Structure identique à celle d'une liste » l'interdisait
    déjà ; rien ne l'exerçait.
  - **Ce que l'exploration a élargi** : le défaut ne touchait pas un gabarit mais **trois**.
    Le `max` isolé et les deux gabarits XSPF construisaient l'adresse depuis l'hôte de la
    requête, ignorant `app_urls_tracks`. En production les deux coïncident, ce qui explique
    que personne ne l'ait vu — ils divergent dès que les fichiers sont servis ailleurs, ce
    qui est la raison d'être de ce réglage.
  - **Périmètre** — dedans : les trois gabarits alignés sur `Post::getTrackUrl()` ; une
    exigence de spec disant que l'adresse se construit d'une seule façon. — dehors : le flux
    et oEmbed, qui passaient déjà par le modèle ; la valeur du réglage.
  - **Code concerné** : `showSuccess.max.php`, `listSuccess.xspf.php`,
    `showSuccess.xspf.php`, `_xspfPlaylist.xspf.php`
  - **Ajoutée** : 2026-08-18 · **Livrée** : 2026-08-18
  - **Change** : `2026-08-18-unifier-l-adresse-du-fichier-audio` — **livré le 2026-08-18**

- [x] 18. `porter-la-base-de-test-en-utf8mb4` — un titre cyrillique survit, et un test le prouve
  - **Persona servi** : le mélomane fêlé
  - **Segment du parcours** : Poster (étape 1)
  - **MoSCoW** : Must — **squelette ambulant de l'axe Unicode**
  - **Pourquoi celle-ci, pourquoi maintenant** : aujourd'hui aucun test ne peut échouer sur
    ce défaut, parce que la base de test est en `utf8` et les fixtures ne contiennent aucun
    caractère hors cp1252. Cette story rend l'écart **exécutable** : elle produit le test qui
    dit ce que le site devrait faire, avant qu'on touche à la production.
  - **Dépend de** : rien
  - **Périmètre** — dedans : la base de test passe en `utf8mb4` ; le DSN de test porte
    `charset=utf8mb4` ; les fixtures gagnent un morceau au titre cyrillique, un au titre
    japonais et un portant un emoji ; un test fonctionnel vérifie qu'ils traversent intacts
    la page, le JSON, le XSPF et le `max`. — dehors : la base de production, qui ne bouge
    pas ; les 56 morceaux déjà détruits.
  - **Pourquoi `utf8mb4` et non `utf8`** : `utf8` de MySQL tient sur trois octets et ne porte
    pas les emoji. La base de test est aujourd'hui en `utf8` — elle ne suffirait pas.
  - **Ce que ça ne fait pas, et qu'il faut dire** : le test passera en environnement de test
    et le site restera cassé en production. C'est voulu — l'écart devient constatable avant
    d'être réparé, ce qui est la seule façon de démontrer ensuite que la réparation opère.
  - **Code concerné** : `src/config/databases.yml-dist`, `src/data/fixtures/subsonic.sql`,
    `src/test/bootstrap/database.php`, `Makefile` (cible `test-init`)
  - **Ajoutée** : 2026-08-18
  - **Change** : `2026-08-18-porter-la-base-de-test-en-utf8mb4` — **livré le 2026-08-18**
  - **Ce que sa livraison a corrigé au packet** : la base de test en `utf8` tenait **déjà**
    le cyrillique, les idéogrammes et les latines étendues — tout le BMP. Elle ne rejetait
    que les emoji, et **bruyamment** (erreur MySQL 1366), là où `latin1` détruit en silence.
    Le manque principal n'était donc pas le jeu de caractères mais **le fait qu'aucune
    fixture ne l'exerçait**. `utf8mb4` ne gagne que les emoji, plus l'alignement sur ce que
    la story 19 posera.
  - **Deux pièges rencontrés, qui valent pour la story 19** : le `charset=` du DSN **n'a
    aucun effet** — Doctrine 1 analyse le DSN lui-même ; le levier est `encoding`, qui
    produit un `SET NAMES`. Et ajouter des morceaux au fichier de fixtures partagé, même
    avec des identifiants neufs, fait basculer **42 assertions** dans quatre suites qui
    comptent des morceaux et des artistes.

- [x] 19. `migrer-la-base-en-utf8mb4` — le site cesse de détruire ce qu'on lui confie
  - **Persona servi** : le mélomane fêlé (principal), l'auditeur, l'intégrateur
  - **Segment du parcours** : Poster (étape 1), et tout ce qui lit ensuite
  - **MoSCoW** : Must
  - **Pourquoi celle-ci, pourquoi maintenant** : c'est le seul défaut de ce plan qui
    **détruise de la donnée**. Chaque morceau posté avec un caractère hors cp1252 perd son
    titre à la seconde où il est enregistré, sans avertissement.
  - **Dépend de** : story 18 — le test doit exister avant la migration, sans quoi rien
    n'établira qu'elle a opéré.
  - **Périmètre** — dedans : `charset=utf8mb4` au DSN ; `CONVERT TO CHARACTER SET utf8mb4`
    sur `post`, `post_index`, `user_profile` et les tables `sf_guard_*` ; reconstruction de
    l'index de recherche, dont la collation change ; vérification que le test de la story 18
    passe désormais **contre la production**. — dehors : les 56 morceaux déjà détruits, qui
    ne se réparent pas par une migration ; le passage du site à un autre SGBD.
  - **Ce qui la dé-risque, et qui a été mesuré** : le corpus ne porte **aucune séquence
    doublement encodée** — zéro occurrence de `Ã©`, `Ã¨` ou équivalent. Les octets stockés
    sont donc du latin1 authentique, ce qui est le cas où `CONVERT TO CHARACTER SET` fait
    exactement ce qu'il faut. Si ce n'était pas le cas, la migration aggraverait au lieu de
    réparer.
  - **Ce qui reste à trancher dans la proposal** : le geste de migration lui-même. Le
    déploiement est automatique à la poussée sur `main`, et une migration de schéma ne l'est
    pas — il faut dire qui la lance, quand, et ce qu'on fait si elle échoue à mi-parcours.
  - **Code concerné** : `src/config/databases.yml-dist`, `src/lib/migration/doctrine/`,
    `src/config/doctrine/schema.yml`
  - **Ajoutée** : 2026-08-18
  - **Change** : `2026-08-18-migrer-la-base-en-utf8mb4` — **livré le 2026-08-18**, et la
    **conversion de production a été menée par l'auteur**, sa vérification passée. La
    seconde moitié est la story 21, également livrée.
  - **Ce que la répétition a corrigé** : le contrôle préalable cherchait `LIKE '%Ã©%'`. Une
    chaîne littérale traverse une conversion de jeu de caractères avant d'atteindre une
    colonne `latin1`, et le résultat dépend du client. Sur MariaDB — le moteur réel — il
    remontait **3 538 corps sur 8 216**, donc s'arrêtait toujours. La comparaison porte
    désormais sur les octets via `HEX()` : 0 sur le corpus réel, 1 dès qu'on empoisonne une
    ligne. **Ce défaut n'a été vu que parce que l'environnement de dev est passé sur
    MariaDB** ; MySQL le masquait.
  - **Vérifié sur les données réelles** : 0 double encodage sur 8 216 morceaux, 12 tables
    converties, `directus_*` non touchées, 8 216 morceaux et 82 détruits avant comme après.

- [x] 21. `poser-l-encodage-de-connexion-en-utf8mb4` — la connexion cesse de convertir
  - **Persona servi** : le mélomane fêlé
  - **Segment du parcours** : Poster
  - **MoSCoW** : Must
  - **Seconde moitié de la story 19**, séparée parce que **l'ordre est une contrainte** :
    poser `encoding: utf8mb4` avant que les tables soient converties enverrait de l'utf8mb4
    vers des colonnes `latin1`, c'est-à-dire le mécanisme qui détruit aujourd'hui, en pire.
  - **Dépend de** : la conversion effective de la base de production, geste manuel qui
    n'appartient pas au dépôt. Cette story ne peut pas partir avant.
  - **Périmètre** — dedans : `encoding: utf8mb4` sur le bloc `all` de `databases.yml-dist`,
    avec le même commentaire que le bloc `test` — le `charset=` du DSN n'a aucun effet,
    Doctrine 1 analysant le DSN lui-même. Vérification sur le site en ligne en postant un
    morceau cyrillique et un morceau avec emoji. — dehors : tout le reste.
  - **État sûr entre les deux** : l'application lit des tables `utf8mb4` sur une connexion
    `utf8`. MySQL reconvertit, et `utf8` couvre tout le plan multilingue de base : rien de
    l'existant ne se perd. Seuls les emoji restent refusés, et bruyamment. On peut y rester
    des jours.
  - **Code concerné** : `src/config/databases.yml-dist`
  - **Ajoutée** : 2026-08-18
  - **Change** : `2026-08-18-poser-l-encodage-de-connexion-en-utf8mb4` — **livré le
    2026-08-18**. Vérifié par le chemin applicatif : `Сергей Прокофьев 坂本龍一` et
    `Пятое время года 🎵🔥` saisis par le modèle et relus identiques.
  - **Deux choses trouvées en vérifiant** : le cache de configuration masquait le
    changement — `databases.yml` est compilé dans `cache/`, un `cache:clear` est
    nécessaire, et le déploiement ne le fait pas. Et le dump d'amorçage du dépôt, daté de
    2021, précédait les colonnes du schéma : toute installation neuve échouait en 1054.
    Remplacé par un extrait de la production converti et **anonymisé** — le dépôt étant
    public, le dump versionné exposait 179 empreintes de mots de passe et 173 courriels.

- [~] 22. `purger-les-identifiants-de-l-historique` — **archivée le 2026-08-18 sans avoir atteint son objectif**
  - **Persona servi** : les 171 contributeurs dont l'adresse et l'empreinte de mot de passe
    sont publiées, et qui ne le savent pas
  - **Segment du parcours** : aucun — c'est une dette de sécurité, pas une fonctionnalité
  - **MoSCoW** : Must
  - **Née d'une vérification** : trouvée en remplaçant le dump d'amorçage. Le dépôt est
    **public** et son historique porte **207 empreintes SHA1 distinctes et 171 courriels
    réels**, sur cinq versions de deux dumps de production. L'anonymisation du 2026-08-18
    règle l'avenir et rien du passé.
  - **L'ordre est le fond de la story** : invalider les mots de passe d'abord, prévenir les
    personnes ensuite, réécrire l'historique en dernier. Une réécriture **ne récupère
    rien** — les objets sont clonés partout, présents dans **4 forks** qu'elle n'atteint
    pas, et circulent depuis des années. Ce qui change l'exposition, c'est que les
    empreintes cessent d'ouvrir un compte.
  - **Ce qui aggrave** : `sf_guard_user.algorithm` vaut `sha1`, et le sel figure dans le
    même dump. Ce sont des mots de passe à traiter comme lisibles, non comme protégés.
  - **Coût de coordination mesuré** : 4 forks, 46 branches distantes, une PR de release en
    cours, 7 observateurs.
  - **Périmètre** — dedans : invalidation, notification, réécriture par `git filter-repo`,
    demande de purge au support GitHub, information des propriétaires de forks. — dehors :
    changer l'algorithme de hachage ; les copies hors du dépôt, qu'aucune réécriture
    n'atteint et qu'il serait malhonnête de prétendre purger.
  - **Dépend de** : rien
  - **Code concerné** : l'historique git ; `src/data/fixtures/musiqueapproximative.sql` et
    `src/data/fixtures/net_musiqueapproximative_www.dump.sql`, ce dernier supprimé depuis
    mais présent dans l'historique
  - **Ajoutée** : 2026-08-18
  - **Change** : `2026-08-18-purger-les-identifiants-de-l-historique` — **archivé le
    2026-08-18, 3 tâches sur 21.** Case laissée à `[~]` et non à `[x]` : le change est clos,
    la story ne l'est pas. L'énoncé — « le dépôt public cesse de distribuer des mots de
    passe » — est **faux à ce jour**.
  - **Ce qui a été fait** : sauvegarde miroir vérifiée ; inventaire corrigé (trois dumps et
    non deux, `vagrant.dump.sql` avait échappé au premier relevé) ; réécriture appliquée aux
    **branches**, `main` passant de `4df38ae` à `a99802b` ; amorçage anonymisé rétabli en un
    commit sans passé ; dépôt local réaligné.
  - **Ce qui a bloqué** : les **33 étiquettes** ont été rejetées — `GH013 … Cannot update
    this protected ref`. Un clone les récupérant par défaut et 14 d'entre elles portant
    l'ancien historique, **l'exposition est inchangée : 207 empreintes, 171 courriels.** La
    règle n'est lisible dans aucune API accessible ; la lever demande un accès
    administrateur.
  - **Effet de bord non anticipé, et il coûte** : la réécriture a changé toutes les
    empreintes, or les étiquettes désignent toujours les anciennes. `v1.11.0` n'est plus un
    ancêtre de `main`, release-please a perdu son ancre et proposait un changelog 1.12.0
    contenant l'histoire entière du dépôt. Contourné par `last-release-sha` dans
    `release-please-config.json` — **à retirer quand les étiquettes passeront**.
  - **Reste à faire, hors travail technique** : lever la règle puis
    `git push --force 'refs/tags/*:refs/tags/*'` ; invalider les 207 mots de passe ;
    prévenir les 173 personnes (brouillon dans `notification.md` de l'archive) ; solliciter
    le support GitHub ; informer les 4 forks. Ces gestes demandent des décisions du
    collectif, pas du code.

- [ ] 20. `inventorier-les-morceaux-detruits` — on sait ce qu'on a perdu, et on le dit
  - **Persona servi** : le mélomane fêlé, le mainteneur
  - **Segment du parcours** : Retrouver son morceau
  - **MoSCoW** : Should
  - **Pourquoi celle-ci, pourquoi maintenant** : la story 19 arrête l'hémorragie, elle ne
    soigne pas la plaie. **56 morceaux** portent un titre ou un auteur irrécupérable depuis
    la base. Les laisser sans inventaire, c'est décider de les oublier sans le dire.
  - **Dépend de** : story 19 — inventorier avant d'avoir arrêté la destruction laisserait la
    liste s'allonger sous l'inventaire.
  - **Périmètre** — dedans : produire la liste, avec l'identifiant, la date de publication et
    le contributeur de chacun ; établir si une sauvegarde antérieure au dommage existe ;
    porter la question aux contributeurs concernés, qui sont les seuls à savoir ce qu'ils
    avaient saisi. — dehors : deviner les titres, ce qui reviendrait à inventer des données.
  - **Ce que le tableau des modalités a durci** : « le seul recours est humain » était une
    hypothèse ; c'est maintenant chiffré. **81 morceaux, 37 contributeurs**, sur dix-huit
    ans. Ils sont joignables — le collectif publie quotidiennement — mais se souvenir de ce
    qu'on a saisi en 2009 n'est pas se souvenir de la semaine dernière. **22 des 81 datent
    de 2022 ou après** : ceux-là ont une chance réelle d'être reconstitués, les autres
    beaucoup moins. La story doit distinguer les deux.
  - **Ce que la story doit donc prévoir** : le cas où personne ne répond. Marquer les
    morceaux concernés comme altérés est le minimum, et c'est la seule forme de notification
    encore possible — elle ne rend rien, elle cesse de faire passer une mutilation pour un
    titre.
  - **Question ouverte** : la restauration depuis une sauvegarde n'aidera pas. La destruction
    a lieu **à l'écriture**, donc au moment du post : une sauvegarde ancienne porte les mêmes
    `?`.
  - **Code concerné** : aucun. C'est un travail de données et de conversation.
  - **Ajoutée** : 2026-08-18
  - **Change** : _pas encore proposé_

- [x] 16. `restreindre-les-tirages-aux-morceaux-publiables` — le hasard ne mène plus à une page morte
  - **Persona servi** : l'auditeur sur le site, le DJ de soirée — quiconque clique le bouton
    aléatoire ou frappe `r`
  - **Segment du parcours** : Enchaîner (auditeur, étape 3)
  - **MoSCoW** : Must
  - **Née d'une vérification** : trouvée en écrivant la couverture de la story 6. Le
    scénario « Morceau aléatoire : le morceau tiré est publiable » y est marqué **non
    vérifié**, non parce que le test manque mais parce que le site ne tient pas la promesse.
  - **Le défaut** : `PostTable::WHERE_ONLINE` définit un morceau publiable par
    `is_online = 1 AND publish_on <= … AND slug IS NOT NULL AND slug != ''`. Quatre méthodes
    réécrivent cette condition **à la main** en omettant la clause de slug :
    `getRandomPost` (`:228`), `getNextPost` (`:106`), `getPreviousPost` (`:136`) et
    `getByMd5Sum` (`:251`). `/posts/random` peut donc servir `/post/`, une page morte.
  - **Gravité** : deux des sept candidats des fixtures, **un sur 6 103 en production**. Rare,
    mais le chemin est celui du bouton aléatoire et du raccourci `r`.
  - **Périmètre** — dedans : les quatre méthodes utilisent `WHERE_ONLINE` au lieu de
    réécrire ; le scénario passe de non vérifié à vérifié. — dehors : les autres requêtes
    qui interpolent déjà la constante ; le morceau sans slug lui-même, qui est une donnée.
  - **Dépend de** : rien
  - **Code concerné** : `src/lib/model/doctrine/PostTable.class.php`,
    `src/test/functional/frontend/catalogueEtNavigationTest.php`
  - **Ajoutée** : 2026-08-18
  - **Change** : `2026-08-18-restreindre-les-tirages-aux-morceaux-publiables` — **livré le
    2026-08-18**. Six conditions alignées sur `WHERE_ONLINE`, 8 098 publiables avant comme
    après, le scénario passe de non vérifié à vérifié.
  - **Ce que la livraison a montré** : le seul morceau atteignable à tort était
    `????? — ??????`, publié en 2009 — un titre cyrillique détruit par l'encodage `latin1`,
    dont `Sluggable` n'a rien pu tirer. Les deux défauts s'enchaînent : l'encodage détruit
    le titre, l'absence de titre empêche le slug, l'absence de slug fait servir une page
    morte.

- [ ] 17. `assainir-les-avertissements-de-markdown` — les corps de réponse cessent de porter des avertissements PHP
  - **Persona servi** : le mainteneur
  - **Segment du parcours** : Vérifier
  - **MoSCoW** : Should
  - **Née d'une vérification** : c'est ce qui rendait erratiques les sondes de plusieurs
    changes de cette session, selon l'ordre des requêtes.
  - **Le défaut** : l'environnement de test déclare
    `error_reporting: (E_ALL | E_STRICT) ^ E_NOTICE`, qui laisse passer `E_DEPRECATED`. La
    bibliothèque PHP-Markdown vendorisée emploie `$matches[2]{0}` (`markdown.php:910`),
    déprécié en PHP 7.4. Le premier rendu Markdown d'un processus émet donc des
    avertissements **dans le corps de la réponse**, qui cesse d'être du JSON analysable.
  - **Ce qui le rend plus qu'un désagrément** : cette syntaxe est **supprimée en PHP 8**.
    C'est un verrou de montée de version, pas seulement du bruit de test.
  - **Contourné aujourd'hui** par une requête de chauffe dans deux fichiers de test, nommée
    comme telle. La production n'est pas touchée : huit échantillons y renvoient du JSON
    valide.
  - **Périmètre** — dedans : corriger la bibliothèque ou masquer `E_DEPRECATED` en test, et
    retirer les requêtes de chauffe. — dehors : la montée en PHP 8 elle-même.
  - **Dépend de** : rien
  - **Code concerné** : `src/lib/vendor/PHP-Markdown/markdown.php`,
    `src/apps/frontend/config/settings.yml`
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

- [~] 8. ~~`publier-la-documentation-d-api`~~ — **supersédée le 2026-08-18**, éclatée en trois
  - **Raison** : le packet reposait sur un constat faux et sur une hypothèse plus faible que
    prévu. Faux : il tenait l'étape « Découvrir » pour un `gap`, alors que le site Antora
    est **en ligne, bâti à chaque poussée touchant `docs/`**, et publié à
    `constructions-incongrues.github.io/musiqueapproximative`. Faible : « publier la
    documentation » recouvrait trois travaux qui n'ont pas les mêmes dépendances ni le même
    bénéficiaire.
  - **Ce que l'exploration a trouvé** : le site n'a **aucune entrée de navigation** —
    `docs/antora.yml` déclare `nav: modules/ROOT/nav.adoc`, absent du dépôt — et son
    `index.adoc` est une table des matières tenue à la main qui a dérivé : **8 pages liées
    sur 15**. Sept pages sont publiées mais inatteignables, dont
    `developpement/environnement` et `developpement/tests`, écrites le jour même.
  - **Remplacée par** : story 10 pour le contrat d'API, story 11 pour la navigation,
    story 12 pour la page Subsonic. `docs/API_CURRENT_STATE.md` devient caduc : le document
    OpenAPI dit la même chose, en vérifié.
  - **Ajoutée** : 2026-08-18 · **Supersédée** : 2026-08-18

- [ ] 9. `corriger-le-contexte-openspec` — le contexte projet cesse d'affirmer un faux
  - **Persona servi** : le mainteneur
  - **Segment du parcours** : Vérifier
  - **MoSCoW** : Could
  - **Pourquoi celle-ci, pourquoi maintenant** : le `context:` de `openspec/config.yaml`
    affirme « aucun test automatisé ne le couvre aujourd'hui » à propos du contrat public.
    Ce contexte est injecté dans les instructions de chaque artefact : l'erreur se propage
    à tout ce qu'OpenSpec produit.
  - **L'écart s'est creusé le 2026-08-18** : la phrase était déjà fausse — cinq routes
    couvertes — elle l'est devenue davantage. La suite compte désormais **16 fichiers et
    461 assertions**, dont quatre fichiers fonctionnels qui exercent le contrat public, le
    protocole Subsonic et le comportement du cache. La ligne à corriger n'a pas bougé ;
    ce qu'elle contredit, si.
  - **Dépend de** : story 6
  - **Périmètre** — dedans : la phrase fautive, mise à jour d'après la couverture réelle.
    — dehors : le reste du `context:`, vérifié exact.
  - **Code concerné** : `openspec/config.yaml`
  - **Ajoutée** : 2026-08-18
  - **Change** : _pas encore proposé_

- [x] 10. `publier-le-contrat-openapi` — l'API a un contrat lisible par machine, servi et vérifié
  - **Persona servi** : l'intégrateur
  - **Segment du parcours** : Découvrir (étape 1)
  - **MoSCoW** : Must
  - **Pourquoi celle-ci, pourquoi maintenant** : **c'est le nouveau squelette ambulant du
    plan.** Elle décrit l'état **actuel** de l'API, sans rien changer, et devient la base
    que chaque story suivante amende. Le diff du contrat entre deux stories est le journal
    des modifications de l'API — ce qui retourne l'argument qui reléguait la documentation
    en dernier : réécrire n'est plus du gaspillage, c'est le livrable.
    Elle force par ailleurs les décisions que le plan diffère. On n'écrit pas
    `parameters: [{name: limit, schema: {default: ???}}]` sans trancher la question
    ouverte n°1.
  - **Dépend de** : rien
  - **Périmètre** — dedans : `src/web/openapi.yaml-dist` décrivant les routes JSON
    existantes ; `servers:` alimenté par `${APP_DOMAIN}` via `make configure`, comme
    `app.yml-dist`, de sorte que les trois profils obtiennent le leur ; un test fonctionnel
    qui, pour chaque `path` déclaré, demande la route et vérifie le statut et le
    `Content-Type` annoncés. — dehors : la validation du **corps** contre les schémas, qui
    demanderait un validateur JSON Schema en dépendance ; toute modification de l'API, qui
    est le travail des autres stories.
  - **Ce qui la rend honnête** : `config.yaml` porte l'avertissement écrit avec du sang —
    la banque de mémoire de `docs/memory-bank/` a été supprimée « après avoir dérivé au
    point de documenter cinq routes inexistantes ». Un contrat non vérifié ment mieux
    qu'une prose non vérifiée, parce qu'il a l'autorité d'un format. Le test qui vérifie
    que chaque route déclarée existe est **la partie non négociable** de cette story.
  - **Frontière avec `openspec/specs/`** : les specs disent CE QUI doit arriver et
    pourquoi ; le contrat dit la FORME de l'échange. **La spec est normative, le contrat
    est descriptif et vérifié.** S'ils divergent, c'est le contrat qui a tort.
  - **Code concerné** : `src/web/` (fichiers statiques déjà servis — `manifest.json` et
    `robots.txt` répondent en production), `src/Makefile` (cible `configure`),
    `etc/*/.env` (`APP_DOMAIN`), `src/test/functional/frontend/`
  - **Ajoutée** : 2026-08-18
  - **Change** : `2026-08-18-publier-le-contrat-openapi` — **livré le 2026-08-18**
  - **Ce que sa livraison a rapporté** : quatre relevés machine, versés aux stories
    concernées. (1) `/post/md5/<inconnu>` et `/posts/next|prev` sans `current` produisent
    une **erreur fatale PHP**, pas un 404 — voir story 5. (2) `/post/md5/` expose
    `publish_on`, `created_at`, `updated_at`, `track_duration`, `track_size`, que
    `formats-de-sortie` dit absents — voir story 4. (3) `docs/API_CURRENT_STATE.md`
    annonçait **neuf routes inexistantes** sous une mention « Auto-generated » : retiré.
    (4) La CI rendait les `-dist` par un `envsubst` nu, qui mangeait les clés `$ref` du
    contrat — deux moteurs de rendu, deux fichiers. Corrigé par une liste blanche
    explicite, plus un garde-fou dans le test. **Contrainte à connaître pour tout futur
    `-dist` : un `$` non destiné à la substitution y est fragile.**

- [ ] 11. `reparer-la-navigation-du-site` — les pages publiées deviennent atteignables
  - **Persona servi** : le mainteneur, le contributeur, l'intégrateur — tout le monde
  - **Segment du parcours** : Découvrir (étape 1)
  - **MoSCoW** : Should
  - **Pourquoi celle-ci, pourquoi maintenant** : sept pages sont publiées et inatteignables.
    `docs/antora.yml` déclare un `nav.adoc` qui n'existe pas, donc le site n'a aucune barre
    latérale, et `index.adoc` est un sommaire manuel qui a dérivé à 8 pages sur 15. Deux des
    orphelines expliquent comment faire tourner les tests. Rien dans cette story ne dépend
    de ce que les autres changeront.
  - **Dépend de** : rien
  - **Périmètre** — dedans : créer `docs/modules/ROOT/nav.adoc` et y inscrire les quinze
    pages ; décider si `index.adoc` garde son sommaire manuel ou renvoie à la navigation.
    — dehors : le contenu des pages, la documentation d'API.
  - **Question ouverte à trancher dans la proposal** : un sommaire manuel dérive
    silencieusement — c'est ce qui vient de se produire. Un contrôle en CI qui échoue
    lorsqu'une page n'est pas dans `nav.adoc` vaut-il son coût, ou accepte-t-on la dérive ?
  - **Code concerné** : `docs/antora.yml`, `docs/modules/ROOT/nav.adoc`,
    `docs/modules/ROOT/pages/index.adoc`
  - **Ajoutée** : 2026-08-18
  - **Change** : _pas encore proposé_

- [ ] 12. `publier-la-page-subsonic` — l'auditeur en client Subsonic trouve comment se connecter
  - **Persona servi** : hors des cinq personas de ce plan — celui qui écoute depuis une
    application Subsonic, retiré du plan le 2026-08-18
  - **Segment du parcours** : Découvrir
  - **MoSCoW** : Could
  - **Pourquoi celle-ci, pourquoi maintenant** : `docs/API_SUBSONIC.md` est du Markdown à la
    racine de `docs/`, hors du site Antora. Il est bon — configuration d'un client,
    structure de la bibliothèque, méthodes supportées, limites connues — et personne ne peut
    le lire. C'est le seul morceau de l'ancienne story 8 qui reste un déménagement.
  - **Dépend de** : story 11, sans quoi la page serait publiée et orpheline à son tour
  - **Périmètre** — dedans : verser `API_SUBSONIC.md` sous `docs/modules/ROOT/pages/`, en
    AsciiDoc, inscrit dans la navigation. — dehors : le protocole lui-même, et
    `docs/API_JSON_API_TARGET.md`, qui décrit une option écartée et reste une archive.
  - **Code concerné** : `docs/API_SUBSONIC.md`, `docs/modules/ROOT/pages/`
  - **Ajoutée** : 2026-08-18
  - **Change** : _pas encore proposé_

- [x] 13. `fix-recherche-mobile` — le champ de recherche existe sur téléphone
  - **Persona servi** : le DJ de soirée (principal), le mélomane fêlé
  - **Segment du parcours** : Chercher (DJ, étape 2)
  - **MoSCoW** : Must
  - **Inscrite après coup** : *2026-08-18.* Ce travail a été fait et mis en production
    sans figurer à ce plan — parce que le plan se croyait cantonné aux formats machine et
    tenait l'interface pour un autre axe. La correction de portée du jour la fait entrer
    rétroactivement : elle sert le persona que ce plan a lui-même identifié, sur l'étape
    de son parcours que ce plan a lui-même marquée « partiel ».
  - **Ce qu'elle a fait** : `main.css` posait `.search-container { display: none }` sous
    768 px. Le formulaire de recherche était **purement absent sur téléphone**, alors que
    `/posts?q=…` fonctionnait. Retrait de la règle, `flex-wrap` sur le bandeau, cibles
    tactiles de 44 px, police à 16 px contre la mise à l'échelle iOS. Vérifié à 320, 360,
    480, 768 et 1280 px, sans débordement horizontal à aucune.
  - **Ce qu'elle a délibérément laissé dehors** : le verrouillage du zoom — story 14.
  - **Code concerné** : `src/web/frontend/assets/stylesheets/main.css`
  - **Ajoutée** : 2026-08-18 (rétroactivement)
  - **Change** : `2026-08-18-fix-recherche-mobile` — **livré** en `f062b4f` (PR #146)

- [x] 14. `retirer-le-verrouillage-du-zoom` — le visiteur peut agrandir la page
  - **Persona servi** : le DJ de soirée, l'auditeur sur le site — et au premier chef
    quiconque a besoin d'agrandir pour lire
  - **Segment du parcours** : toutes les étapes servies en HTML
  - **MoSCoW** : Must
  - **Pourquoi celle-ci, pourquoi maintenant** : `layout.php:7` déclare
    `minimum-scale=1, maximum-scale=1`. La page ne peut pas être agrandie. La réparation
    est d'**une ligne** et sans contrepartie : le seul champ de saisie du frontend est
    celui de la recherche, et il est désormais à 16 px — la balise ne protège donc plus
    rien. `input.mailing`, l'autre champ qu'elle couvrait, n'existe dans aucun gabarit.
  - **Dépend de** : story 13, livrée — c'est elle qui a rendu la balise inutile.
  - **Périmètre** — dedans : retirer `minimum-scale=1, maximum-scale=1` de la balise
    `viewport` ; vérifier que la mise au point du champ de recherche ne met toujours pas
    la page à l'échelle, ce que `font-size: 16px` garantit seul. — dehors : le reste de la
    balise (`width=device-width, initial-scale=1`), et le CSS mort d'`input.mailing`.
  - **Ce qui la justifie plutôt qu'un simple nettoyage** : `2026-08-18-fix-recherche-mobile`
    a rempli le tableau des modalités pour cette contrainte. Aucune loi ne l'interdit — un
    site de collectif n'entre ni dans la loi 2005-102 ni dans l'European Accessibility Act.
    La norme la condamne : WCAG 2.1 critère 1.4.4, RGAA 10.4. **Le seul recours existant a
    été créé par Apple et Google contre l'auteur du site** — Safari iOS ignore la
    déclaration depuis iOS 10, Chrome Android l'ignore si le visiteur a trouvé le réglage.
    Côté site, recours : aucun. Le visiteur qui pince sans effet conclut que le site est
    mal fait. Cette règle n'a jamais été votée comme une règle : elle a été écrite dans une
    balise, en une ligne, sans que personne ait à défendre l'idée que les visiteurs de ce
    site n'ont pas le droit d'agrandir le texte.
  - **Artefacts** : `specs` et `tasks` seulement. **Pas de `design.md`** — un changement
    d'une ligne sans architecture n'en a pas, et la règle du dépôt le dit
    (`config.yaml`). Le schéma fait dépendre `tasks` de `design` ; c'est un appui, pas
    une barrière. Voir « Ce que l'appareil de planification coûte ».
  - **Code concerné** : `src/apps/frontend/templates/layout.php` (ligne 7)
  - **Ajoutée** : 2026-08-18
  - **Change** : `2026-08-18-retirer-le-verrouillage-du-zoom` — **livré le 2026-08-18**

## Ordre d'exécution

L'ordre du fichier n'est plus l'ordre des travaux : les stories 10 à 12 sont arrivées après
coup et gardent leur numéro pour que rien ne se perde. La séquence réelle est celle-ci.

```
  10  publier-le-contrat-openapi          ◄── squelette ambulant, ne dépend de rien
   │
   ├─ 1  retablir-le-type-de-contenu-json     premier amendement au contrat
   ├─ 2  borner-les-listes-de-morceaux        débloquée — défaut tranché à 50
   ├─ 3  borner-le-xspf                       ← 2
   ├─ 4  aligner-la-route-md5                 ← 1
   ├─ 5  servir-les-erreurs-en-json           ← 4
   └─ 6  specifier-les-routes-json            ← 1 à 5

  18  porter-la-base-de-test-en-utf8mb4   ◄── squelette ambulant de l'axe Unicode
   ├─ 19  migrer-la-base-en-utf8mb4           ← 18
   └─ 20  inventorier-les-morceaux-detruits   ← 19

  13  fix-recherche-mobile                ✅ livré hors plan, inscrit rétroactivement
   └─ 14  retirer-le-verrouillage-du-zoom     ← 13 · une ligne, ne dépend de rien d'autre

  11  reparer-la-navigation-du-site       ◄── ne dépend de rien, à tout moment
   └─ 12  publier-la-page-subsonic            ← 11

   9  corriger-le-contexte-openspec       ← 6
```

Les stories 10 et 11 peuvent démarrer le même jour : elles ne se touchent pas.

## Ce que ce plan promulgue

<!-- incongru-voix: lessig — la valeur par défaut de la pagination et le type de contenu servi, régulés par l'architecture seule — recours: aucun -->

*Analyse tenue depuis une position réformiste déclarée : elle ne conteste pas qu'on puisse
changer le JSON, elle demande par quelle voie un tiers pourrait s'y opposer. Si cette
réponse arrange, c'est peut-être qu'elle évite une question plus dure — celle de savoir si
cette API devait exister sans contrat.*

Les stories 1, 2 et 3 changent ce que reçoit un appelant du JSON qui n'a rien signé. Ce
n'est pas un détail d'implémentation : c'est une règle promulguée. Le tableau ci-dessous
la remplit.

### Contrainte : ce que reçoit un appelant qui ne demande rien

| modalité | ce qui régule ici |
| --- | --- |
| **loi** | rien. Aucune condition d'utilisation, aucune mention légale, aucun contrat d'API. L'AGPLv3 régit le code du site, pas le service qu'il rend. |
| **norme** | rien. Aucun canal d'annonce vers les intégrateurs. `CHANGELOG.adoc` s'adresse aux contributeurs du dépôt, et la seule adresse publiée est éditoriale. |
| **prix** | le coût de la rétro-ingénierie — mais seulement pour qui s'aperçoit qu'il y a quelque chose à contourner. Il n'existe aucune documentation d'API publiée : c'est la story 8, non faite. |
| **architecture** | totale. `executeList` décide, l'appelant ne négocie rien. |

**Recours : aucun.** Pas de version d'API, pas d'en-tête de dépréciation, pas de délai. Et
sur ce dépôt le déploiement est automatique : `main` part en production à la poussée. La
règle entre donc en vigueur sans notification **et sans le délai que d'autres projets
obtiennent par accident**, entre la fusion et la mise en ligne.

### La question qui suit toujours

Formulée comme une règle plutôt que comme une story :

> « À compter de la prochaine poussée, tout appelant de `/posts?format=json` qui ne
> demande rien recevra cinquante morceaux au lieu de huit mille, sans préavis, sans version,
> et sans moyen de s'en plaindre. »

L'aurait-on votée ainsi ? La réponse honnête est qu'on ne sait pas, parce que personne n'a
été mis en position de la voter.

Ce qui ne rend pas le statu quo innocent : servir 8,4 Mo et 16,5 secondes de génération à
tout le monde est aussi une contrainte imposée sans consentement. **Il n'existe pas d'état
non régulé** — la seule question est qui écrit la règle, et s'il avait mandat pour le
faire.

### Ce que ça change dans ce plan

*Révisé le 2026-08-18, après exploration.* Cette analyse concluait que la story 8 —
publier la documentation d'API — était la seule à créer un canal, et qu'elle arrivait trop
tard. Les deux moitiés de cette conclusion ont bougé.

**Trop généreux sur le canal.** Une documentation en prose crée un *panneau d'affichage* :
un endroit où une annonce de dépréciation pourrait être posée, que personne n'est tenu de
venir lire. Ce n'est pas un canal.

**Un contrat OpenAPI, en revanche, remplit la colonne.** Un document versionné, daté,
diffable et vérifiable en CI est un objet que la colonne « norme » peut contenir — un
`info.version` et un `deprecated: true` existent, là où un paragraphe n'a pas de prise.
C'est la story 10, désormais en tête du plan.

**Ce qui reste vrai malgré tout** : rien n'est poussé vers l'intégrateur. Il doit venir
lire. La conclusion inconfortable tient donc — ne pas borner par défaut tant qu'on ignore
qui consomme — avec une différence : casser le contrat **se verra dans un diff**. La
rupture passe de invisible à coûteuse à ignorer. C'est un progrès, pas un recours.

Trois façons de rendre la règle contestable, par coût croissant :

1. **Publier la documentation d'abord.** Déplacer la story 8 avant les stories 2 et 3. Ne
   crée pas un recours, mais crée l'endroit où une annonce devient possible.
2. **Borner sur demande explicite d'abord, par défaut ensuite.** Le paramètre existe, le
   défaut ne bouge pas ; on observe qui l'utilise avant de trancher. Répond directement à
   la question ouverte n°1 sans la trancher à l'aveugle.
   *(Cette option n'a pas été retenue : l'auteur a tranché le défaut à 50 le 2026-08-18,
   sans phase d'observation préalable. La conclusion inconfortable ci-dessous a donc été
   lue et écartée, ce qui est le sort normal d'un argument — mais il faut que ce soit
   visible plutôt que silencieux.)*
3. **Versionner la sortie.** Le coût réel, et sans doute disproportionné pour ce projet.

### Le point d'inconfort

La première option est la moins chère et laisse le mainteneur avancer. C'est-à-dire
qu'elle est confortable pour celui qui détient déjà le pouvoir de décider, ce qui doit
être dit plutôt que masqué par la finesse du tableau.

La conclusion inconfortable est la seconde, et elle porte sur la question ouverte n°1 :
**ne pas borner par défaut tant qu'on ignore qui consomme.** Un défaut est une loi assortie
d'une procédure de dérogation qu'on sait inutilisable — presque personne ne change un
défaut, et ici presque personne ne peut même apprendre qu'il a changé.

## Ce que le contrat coûtera

<!-- incongru-voix: illich — seuil ≈ 9 h englouties pour 6 amendements rendus lisibles ; bascule le jour où les amendements cessent — le mainteneur unique -->

La story 10 vient d'être promue « Must » et faite squelette ambulant du plan. Elle ajoute
un artefact que chaque changement du JSON devra tenir à jour. Ce relevé chiffre ce que ça
coûte, et nomme le jour où ça s'inverse.

### Le calcul

| poste englouti | estimation |
| --- | --- |
| Écrire le contrat pour 10 routes nommées et leurs formats — json, xspf, max, rss, oembed | ~4 h |
| Le test qui vérifie que chaque `path` déclaré répond | ~2 h |
| Le tenir à jour sur les six amendements que ce plan déclare (stories 1 à 6) | ~3 h |
| **Total sur l'horizon du plan** | **~9 h** |

Ce que ça rend, en trois postes de nature différente :

- **Il force une décision différée.** On n'écrit pas `default:` sans le trancher. La
  question ouverte n°1 bloque deux stories depuis la rédaction de ce plan ; le contrat la
  rend indifférable. Ce poste-là est certain et immédiat.
- **Il rend six ruptures lisibles.** Chaque amendement devient un diff. Valeur réelle
  pendant le plan.
- **Il sert des consommateurs.** Valeur proportionnelle à leur nombre — et la question
  ouverte n°3 dit qu'**aucun n'est identifié**. Ce poste est inconnu, possiblement nul.

Sur l'horizon du plan, le seuil est franchement sous 1 : neuf heures pour une décision
débloquée et six ruptures documentées, c'est payé. **Le problème n'est pas là.**

### Où ça s'inverse

Le taux d'amendement historique de ce contrat est mesurable : **un changement archivé sur
vingt-deux** a touché une capacité que le contrat décrirait, et c'était celui qui a créé
les specs. Une fois les stories 1 à 6 passées, la fréquence retombe à peu près à zéro.

C'est là que le seuil se franchit, et ce n'est pas un ratio — c'est une date :

```
  pendant le plan          6 amendements en quelques semaines
                           la discipline s'exerce, le contrat reste vrai
                                        │
  après le plan            ~0 amendement par an
                           plus personne ne l'ouvre
                                        │
                                        ▼
                           il devient la banque de mémoire
```

`config.yaml` a déjà écrit la fin de cette histoire : la banque de mémoire de
`docs/memory-bank/` « a été supprimée après avoir dérivé au point de documenter cinq
routes inexistantes ». Un contrat non exercé ne se maintient pas seul, et il ment avec
l'autorité d'un format.

**Conséquence directe** : le test de vérification n'est pas un raffinement de la story 10,
c'est la condition de son existence. Sans lui, le contrat est rentable pendant six semaines
puis nuisible pour toujours. Le packet de la story 10 le dit déjà — ce relevé chiffre
pourquoi.

### Verdict de convivialité

| question | réponse | ce qui la fonde |
| --- | --- | --- |
| L'usager comprend-il comment ça marche ? | **oui** | ~200 lignes de YAML dans un format que toute l'industrie lit. Ni codegen, ni annotations, ni étape de compilation. |
| Peut-il le réparer ? | **oui** | Éditer le fichier, relancer le test. Le `servers:` passe par `make configure`, mécanisme déjà en place pour `app.yml`. |
| Peut-il s'en passer ? | **oui** | `openspec/specs/` décrit déjà le contrat public et il est validé par l'outil. |

Trois oui. L'outil sert son usager, il ne l'emploie pas. C'est un résultat, pas une
formalité : beaucoup de dispositifs de contrat échouent au deuxième — swagger-php par
annotations, une génération depuis le code, un pipeline de validation — et celui-ci les a
tous écartés.

### Le point d'inconfort

Ce relevé conclut « faites-le, mais gardez le test ». C'est confortable : ça valide la
décision qui vient d'être prise. La lecture inconfortable est ailleurs, et la question
ouverte n°3 la porte déjà — **on écrit un contrat pour des consommateurs dont on n'a
jamais établi l'existence.** Neuf heures et un artefact permanent, sur une intuition.

Le poste qui tient debout sans eux est le premier : forcer la décision différée. Si c'est
la vraie raison, elle mérite d'être dite comme telle plutôt que d'être présentée comme un
service rendu à des tiers qu'on n'a pas trouvés.

## Ce que l'appareil de planification coûte

<!-- incongru-voix: illich — seuil : 1,5 ligne de plan par ligne de code au global, mais ×150 sur un change d'une ligne — le mainteneur unique -->

Ce plan vient d'inscrire une story dont le code tient en **une ligne** — retirer deux
déclarations de la balise `viewport`. Le schéma en réclame quatre artefacts. Le moment est
bon pour compter ce que l'appareil prend, plutôt que d'y revenir le jour où il pèsera.

### Le décompte, depuis le premier change archivé (2026-08-01)

| poste | lignes |
| --- | --- |
| `openspec/` — plans, specs, artefacts de change | **+9 407** |
| `src/` hors tests | +3 646 |
| `src/test` | +2 720 |
| **Rapport plan / code** | **1,48 ligne de plan par ligne de code** |

24 changes archivés, 6 162 lignes d'artefacts, **moyenne 256 lignes par change**. Ce plan
lui-même fait 1 072 lignes après cinq révisions en une journée.

### Où le rapport se retourne

Le rapport global ne dit rien : c'est une moyenne entre des cas qui n'ont rien à voir.

| change | planification | code | rapport |
| --- | --- | --- | --- |
| `publier-le-contrat-openapi` | 627 l. | ~700 l. (contrat + test) | **0,9** |
| `fix-recherche-mobile` | 278 l. | 55 l. de CSS | **5,1** |
| story 14, projetée | ~150 l. | **1 l.** | **~150** |

Le coût de l'appareil est **fixe** — une proposition, un delta de spec, une liste de tâches,
une validation : de l'ordre d'une heure et de 150 lignes, quelle que soit la taille du
changement. Ce qu'il rend est **proportionnel** aux décisions que le changement porte. Le
seuil est donc là où le changement cesse de porter des décisions, pas là où il devient
petit — et les deux ne coïncident pas.

### Le verdict de convivialité, et il est bon

| question | réponse | ce qui la fonde |
| --- | --- | --- |
| Le mainteneur comprend-il comment ça marche ? | **oui** | Des fichiers Markdown dans un dossier. Ni base, ni service, ni format propriétaire. |
| Peut-il le réparer ? | **oui** | Éditer le fichier. `openspec validate` dit ce qui cloche. |
| Peut-il s'en passer ? | **oui, et il le fait déjà** | `design.md` n'existe que dans **6 changes sur 24**. La règle du dépôt — « ne produire cet artefact que si le changement est structurant » — est appliquée les trois quarts du temps. |

Trois oui. **L'appareil n'a pas franchi son second seuil**, et la troisième réponse est la
plus importante : la modulation n'est pas théorique, elle est mesurée. Un outil dont on se
dispense aux trois quarts sur un artefact ne gouverne pas son usager.

### Ce que ce calcul décide

Une chose, et elle est immédiate. Le schéma `behaviour-driven` fait dépendre `tasks` de
`design`, ce qui pousse à écrire un `design.md` même quand la règle du dépôt dit de ne pas
le faire. La commande de proposition tranche déjà ce conflit — les dépendances sont des
appuis, pas des barrières — mais il faut le savoir pour l'appliquer.

**Pour la story 14 : pas de `design.md`.** Un changement d'une ligne sans architecture n'en
a pas. Il garde ses `specs` — « le visiteur peut agrandir la page » est un engagement de
comportement durable, et c'est exactement ce qu'une spec est faite pour tenir — et ses
`tasks`, dont la vérification manuelle est réelle.

**Règle générale, à appliquer aux stories à venir de ce plan** : sous une dizaine de lignes
de code, l'artefact qui se justifie encore est celui qui porte un engagement de
comportement. Les autres restatent le packet de la story. Restater sans vérifier est
exactement ce qui a fait dériver la banque de mémoire.

### Le point d'inconfort

Ce relevé conclut « l'outil va bien ». C'est confortable, et c'est le résultat qu'on obtient
en mesurant un appareil le jour où on s'en sert bien. La lecture inconfortable est dans le
premier tableau : **ce dépôt a produit une fois et demie plus de plan que de code en trois
semaines.** Le rapport est défendable tant que quelqu'un lit le plan. Personne n'a encore
mesuré ça, et ce chiffre-là n'a pas de dénominateur.

## Questions ouvertes

1. ~~**La valeur par défaut de la pagination**~~ — **tranchée le 2026-08-18 par l'auteur :
   50.** Elle débloque les stories 2 et 3.

   La valeur n'est pas arbitraire et n'invente rien : **`/posts/feed` borne déjà à 50** par
   son paramètre `count` (`actions.class.php:233`). C'est la seule route bornée du site.
   La décision aligne donc le reste sur le seul précédent existant, plutôt que de créer un
   second seuil à côté du premier.

   Ce qu'elle promulgue, sans l'adoucir : un appelant de `/posts?format=json` qui ne
   demande rien recevra **50 morceaux au lieu de 8 097**, sans préavis. Les arguments qui
   avaient été accumulés en faveur du bornage tiennent — 16,5 s de génération à froid, et
   deux personas sur trois qui ne connaîtront jamais le paramètre.

   **Conséquence à ne pas perdre de vue** : par défaut, `/posts` en HTML n'affichera plus
   que 50 morceaux, et l'**interface** de pagination est en « Could », pas dans la story 2.
   Entre la livraison de la story 2 et celle de cette interface, le catalogue complet ne
   sera atteignable qu'en connaissant le paramètre `offset`. Le DJ de soirée passe donc de
   « 3,7 Mo qu'il abandonne » à « 50 morceaux au-delà desquels il ne peut pas aller ».
   Ce n'est pas un argument contre la décision — c'est un argument pour promouvoir
   l'interface de pagination une fois la story 2 livrée.

2. ~~**Qui consomme `/posts/next|prev|random` ?**~~ — **tranchée le 2026-08-18.** Le site
   lui-même, via `layout.php` : quatre `$.get` qui lisent `data.url` et `data.title`, pour
   les raccourcis `j` / `k` / `r`, le bouton aléatoire et l'enchaînement du lecteur. Ces
   routes ne seront pas alignées ; leur divergence sera documentée. Voir story 4.

3. **Qui consomme le JSON ?** — toujours ouverte pour le JSON. **Mais la question voisine,
   « qui consomme la page HTML complète », a reçu sa première réponse le 2026-08-18 :
   l'auteur, par Ctrl+F.** C'est le premier usage réel qu'ait reçu ce plan, et il a
   immédiatement mis une story en attente. Ce que ça enseigne dépasse la story 2 : quatre
   heures de lecture de code n'avaient identifié aucun consommateur, et une phrase de
   l'auteur en a produit un. Le code dit ce que le site fait, pas ce dont on se sert.
   Pour le JSON proprement dit, rien de neuf : La session du
   2026-08-18 n'a rien apporté sur ce point, et c'est en soi un signal : quatre heures
   passées dans ce dépôt, à lire les filtres, les gabarits, les tests et la configuration,
   sans jamais croiser un appelant du JSON. Ce n'est pas une preuve d'absence, mais c'est
   un indice de plus. La révision
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

- 2026-08-18 (septième révision) — **L'auteur apporte l'axe Unicode et le classe Must.**
  L'exploration a montré que la question n'est pas d'affichage mais d'encodage : la base de
  production est en `latin1`, et tout caractère hors cp1252 y est remplacé par `?` à
  l'écriture, silencieusement et sans retour. **56 morceaux ont déjà un titre ou un auteur
  détruit** — du cyrillique, du polonais, du japonais. C'est le seul défaut de ce plan qui
  détruise de la donnée au lieu de mal la servir, ce qui justifie le Must sans discussion.
  Trois stories : la **18** rend l'écart exécutable en portant la base de test en `utf8mb4`
  — `utf8` ne suffit pas, il ne porte pas les emoji ; la **19** migre la production ; la
  **20** inventorie ce qui est perdu, puisque la migration n'y rendra rien.
  Un fait mesuré dé-risque la 19 : le corpus ne porte aucune séquence doublement encodée,
  donc `CONVERT TO CHARACTER SET` fera exactement ce qu'il faut.
  **Une erreur de méthode a été commise et corrigée dans la même révision**, et elle est
  consignée parce qu'elle se reproduira. Une première rédaction concluait que le site était
  dormant — « dernier morceau le 14 octobre 2021 » — et en tirait qu'il n'y avait pas
  d'urgence. L'auteur a corrigé : le site publie quotidiennement. La source était fautive,
  non le raisonnement : **la base de développement porte un dump vieux de cinq ans**, 6 103
  morceaux contre 8 097 en ligne. Toute mesure de volume, de date ou de contenu doit
  désormais venir du site en ligne.
  Les chiffres corrigés, relevés sur la production : **81 morceaux détruits, 37
  contributeurs, 22 depuis 2022, 5 en 2026**, et **zéro caractère hors cp1252 survivant sur
  8 097 morceaux et dix-huit ans**. La destruction est donc en cours.
  Deux conséquences : un **garde-fou à la saisie** entre en « Could », conditionnel — il n'a
  de sens que si la migration tarde, la story 19 supprimant la cause ; et la story 20
  distingue désormais les 22 dégâts récents, reconstituables, des 59 anciens qui le sont
  beaucoup moins.

- 2026-08-18 (septième révision) — **La story 2 passe en attente**, à la demande de
  l'auteur, et pour un motif qui vaut mieux qu'une préférence : il se sert de `/posts` non
  bornée pour y faire Ctrl+F. Vérification faite dans `listSuccess.php`, chaque ligne rend
  `artiste — titre (contributeur)` — et `Searchable` n'indexe pas le contributeur. La page
  complète est donc le contournement d'une fonctionnalité que ce plan porte en « Could ».
  La borner retirerait le contournement avant de livrer ce qu'il remplace.
  Conséquences : la proposition est retirée de `openspec/changes/` pour ne pas être reprise
  par erreur ; la story 3 perd sa dépendance à la story 2 et peut partir seule, le motif
  Ctrl+F ne s'appliquant pas à un fichier de playlist ; la question ouverte n°3 enregistre
  son premier consommateur identifié — **le premier de tout le plan**, et il est venu de
  l'auteur, non du code.
  Reste non tranché : borner les seuls formats machine, auxquels aucun Ctrl+F ne s'applique.

- 2026-08-18 (sixième révision) — **La question ouverte n°1 est tranchée : 50.** C'était la
  seule décision de produit en suspens du plan, et elle bloquait deux stories depuis sa
  rédaction. La valeur aligne le catalogue sur `/posts/feed`, qui borne déjà à 50 et
  était jusqu'ici la seule route bornée du site. La règle promulguée est mise à jour en
  conséquence — cinquante morceaux, non vingt. L'option « borner sur demande d'abord, par
  défaut ensuite », que l'analyse des modalités avançait comme la conclusion inconfortable,
  a été écartée : c'est consigné là où elle est formulée, plutôt que retiré.
  Une conséquence est versée à la question tranchée : avec un défaut à 50 et l'interface de
  pagination en « Could », le catalogue complet ne sera atteignable qu'en connaissant
  `offset` tant que cette interface n'existe pas. Argument pour la promouvoir après la
  story 2, non contre la décision.
  Enfin la **story 1 est livrée** (`2026-08-18-retablir-le-type-de-contenu-json`) : six
  routes servent `application/json`, le contrat a reçu son premier amendement, et son test
  a rendu cet amendement obligatoire — le dispositif de la story 10 a mordu comme annoncé.

- 2026-08-18 (cinquième révision) — **Correction de portée par l'auteur : ce plan est
  généraliste.** Il se croyait cantonné à l'axe « tenir les formats machine » et rejetait
  en « Won't » tout ce qui relevait de l'interface, au motif que cela méritait sa propre
  discovery. Cette ligne n'était pas tenable : `fix-recherche-mobile` a été livré en
  production sans jamais figurer au plan, alors qu'il servait un persona que le plan avait
  identifié, sur une étape que le plan avait marquée. Le titre change en conséquence.
  Conséquences : deux stories mobiles entrent en **Must** — la 13, livrée et inscrite
  rétroactivement, et la 14, le verrouillage du zoom, qu'elle avait délibérément laissé
  dehors. La story 10 est **livrée** ; ses quatre relevés machine sont versés aux stories
  concernées, dont deux qui s'alourdissent : la story 5 découvre qu'elle a des erreurs
  fatales à supprimer avant d'avoir des 404 à habiller, et la story 4 découvre une fuite
  de champs en plus de son problème d'enveloppe. **Reste à trancher : les trois besoins
  d'interface versés en « Won't » lors de la seconde révision n'ont plus de motif de s'y
  trouver.**

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
  de recherche, et un filtre dans l'admin. *(Ce motif a été renversé le 2026-08-18 : les
  trois sont remontés en « Could ». Entrée conservée telle quelle comme trace.)* Aucune story ajoutée.

- 2026-08-18 (troisième révision) — Le persona « l'auditeur en client Subsonic » est retiré
  du plan à la demande de l'auteur. Son parcours et son entrée « Could » disparaissent avec
  lui ; la story 7 est **supersédée** plutôt que supprimée, avec son contenu conservé pour
  qui la reprendrait. Toute la surface Subsonic passe en « Won't » d'un bloc : le module
  `rest` a sa spécification, ses tests et sa documentation propres, et les stories 1 et 5
  se contentent de vérifier qu'elles ne le cassent pas. L'argument Subsonic reste en place
  là où il fonde le rejet de la conformité JSON:API — c'est un fait sur la surface, pas sur
  le persona. Le plan retient cinq personas et huit stories actives.

- 2026-08-18 (cinquième révision) — Exploration de la story 8, qui l'a démolie et
  remplacée. Deux constats l'ont défaite : le site de documentation est **en ligne** et
  reconstruit à chaque poussée, donc l'étape « Découvrir » n'était pas un `gap` ; et
  « publier la documentation » recouvrait trois travaux aux dépendances différentes. Le
  site n'a par ailleurs **aucune navigation** — `antora.yml` déclare un `nav.adoc` absent —
  et son sommaire manuel a dérivé à 8 pages liées sur 15, laissant sept orphelines dont
  deux écrites le jour même.
  L'auteur a tranché trois questions : le contrat vit dans `src/web/openapi.yaml`, servi
  par le site ; `openspec/specs/` reste normatif à côté ; et la chose passe en tête du
  plan. D'où les stories **10** (contrat OpenAPI, nouveau squelette ambulant), **11**
  (navigation) et **12** (page Subsonic), la story 8 étant supersédée sans être effacée.
  Un bloc « Ordre d'exécution » est ajouté, l'ordre du fichier ne valant plus séquence.
  L'analyse Lessig est révisée : elle surestimait le pouvoir d'une page en prose, et un
  contrat versionné remplit réellement la colonne « norme » qu'elle laissait vide.
- 2026-08-18 (quatrième révision) — Mise à jour depuis une session de travail. **Aucune
  story n'a avancé** : les cinq changements archivés ce jour-là — couverture de la
  configuration des désastres, dédoublonnage des règles et recettes, cache en environnement
  de test, couverture de l'invariance, préparation de la base de test locale — portent sur
  la spec `desastres` et sur l'outillage, pas sur les formats machine. Le terrain a
  néanmoins bougé sur trois points, consignés dans « Ce que la session a changé pour ce
  plan » : le schéma OpenSpec est passé à `behaviour-driven`, qui ajoute un artefact
  `design` à chaque story ; l'environnement de test met en cache, ce dont la story 1 a
  directement besoin ; et la suite est verte et exécutable en local, ce qui abaisse le coût
  de la story 6. Les packets des stories 1, 6 et 9 sont mis à jour en conséquence. Le
  relevé le plus concret concerne la story 1 : six assertions, sur deux fichiers, verrouillent
  le type de contenu qu'elle veut changer.

- 2026-08-18 (analyse lessig) — Le plan est passé au tableau des quatre modalités, sur la
  contrainte qu'il impose aux appelants du JSON. Résultat : régulation par l'architecture
  seule, recours aucun, aggravé par le déploiement automatique qui supprime le délai entre
  fusion et mise en ligne. Conséquence sur l'ordonnancement : la story 8 est la seule à
  créer un canal d'annonce, et elle est aujourd'hui classée « Could » en dernière position.
  L'analyse est consignée sous « Ce que ce plan promulgue » ; elle n'a pas modifié le
  MoSCoW ni les stories, la décision revenant à l'auteur.

- 2026-08-18 (relevé illich) — La story 10, promue « Must » et faite squelette ambulant, est
  passée au calcul de seuil. Résultat : ~9 h englouties sur l'horizon du plan, payées par
  une décision débloquée et six ruptures rendues lisibles. Trois oui au verdict de
  convivialité. Le seuil ne se franchit pas sur un ratio mais sur une date : le taux
  d'amendement historique est d'**un changement archivé sur vingt-deux**, si bien qu'après
  les stories 1 à 6 le contrat cesse d'être exercé et devient ce que `docs/memory-bank/`
  est devenu. Conséquence consignée : le test de vérification est la condition d'existence
  de la story 10, pas un raffinement. Le relevé note aussi que le poste de valeur le plus
  solide est de forcer la question ouverte n°1, et non de servir des consommateurs dont
  aucun n'est identifié. Aucune story n'a été modifiée.

- 2026-08-18 (huitième révision) — **Réconciliation du backlog après la session.** Douze
  stories livrées et archivées ; la **22** est archivée sans avoir atteint son objectif et
  passe à `[~]` plutôt qu'à `[x]` — les branches sont réécrites, les étiquettes rejetées,
  l'exposition inchangée. La règle de protection qui les bloque n'est lisible dans aucune
  API accessible.
  Consigné aussi parce que ça se reproduira : **une réécriture d'historique dont les
  étiquettes ne suivent pas casse release-please.** Toutes les empreintes changent, plus
  aucune étiquette n'est atteignable depuis `main`, l'outil remonte au commit racine et
  propose l'histoire entière comme changelog. Contourné par `last-release-sha`, à retirer
  quand les étiquettes passeront.
  **Cinq changes livrés ce jour ne correspondent à aucune story** et le plan ne les
  couvrait pas : `preparer-la-base-de-test-en-local`, `activer-le-cache-en-test`,
  `dedoublonner-regles-et-recettes`, `couvrir-la-configuration-des-desastres`,
  `couvrir-l-invariance-des-desastres`. Les deux premiers sont déjà relevés en contexte —
  ils ont rendu les autres moins chères. Les trois derniers sont du travail sur les
  désastres, hors des axes de ce plan : ils sont nommés ici pour que le décompte
  « 24 changes archivés » reste vérifiable, non pour être rétro-inscrits en stories.
  Aucune story n'a été ajoutée ni supprimée.

- 2026-08-18 (neuvième révision) — **La story 10 n'était pas livrée, et le plan le disait.**
  Le contrat OpenAPI existait, était vérifié à chaque exécution de la suite, et **répondait
  404 en production** : seul `openapi.yaml-dist` était versionné, le rendu venait de
  `make configure`, et le déploiement se fait par `git pull`. Un document que rien ne sert
  n'est pas publié. Corrigé hors plan par le change `servir-le-contrat-sans-rendu`, archivé
  le jour même : le contrat devient un fichier versionné servi tel quel, avec une adresse de
  serveur relative. La capacité `contrat-openapi` est amendée en conséquence — c'est un
  changement de comportement observable, pas un détail d'implémentation.
  **Ce que ça révèle et qui dépasse le contrat** : la convention `-dist` + `make configure`
  datait du déploiement par rsync, qui rendait les fichiers avant de les envoyer. Depuis le
  passage à `git pull`, **aucun fichier `-dist` ajouté n'arrive en production**. Les autres
  `-dist` du dépôt sont antérieurs et présents sur le serveur depuis l'ère rsync ; rien ne
  prouve qu'ils soient à jour. C'est une story à écrire, et elle commence par mesurer.
  **Le trou de vérification reste ouvert** : le contrat n'est confronté qu'à l'instance de
  test. C'est ce qui a laissé un document 404 passer pour publié pendant une journée. Une
  vérification contre la production est une story à part, non écrite à ce jour.
  Consigné aussi : la mise en ligne a mis le site en 500 sur toutes ses pages PHP, non à
  cause du change, mais d'un `make configure` lancé sur le serveur — la cible y trouve un
  `src/.env` absent, ne substitue rien, et réécrit toute la configuration en gabarits bruts.
  Rétabli. La commande n'est pas exécutable sur le serveur en l'état.
