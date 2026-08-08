# Support de l'API Subsonic

> Spec de conception — 2026-08-07
> Révisée après revue d'ingénierie (`/plan-eng-review`) — voir le rapport en fin de document.

## Objectif

Exposer l'archive de Musique Approximative à travers l'API Subsonic, afin de
pouvoir la parcourir et l'écouter depuis n'importe quel client Subsonic
(Symfonium, Feishin, Substreamer, play:Sub, DSub…).

Périmètre : **lecture seule sur les morceaux**, plus les regroupements
éditoriaux du site (playlists par contributeur) exposés comme playlists
Subsonic. Pas de favoris, pas de scrobbling enregistré, pas de playlists
créées depuis le client.

Version du protocole annoncée : `1.16.1`.

## Décisions structurantes

### Authentification : ouverte

Tout couple `u`/`p` — ou `t`/`s` — est accepté sans vérification.

Les fichiers audio sont déjà servis publiquement en statique depuis `/tracks/`
par Nginx : une authentification ne protégerait rien qui ne le soit pas déjà.
Elle serait par ailleurs coûteuse, car le mode token de Subsonic
(`t=md5(password+salt)`) exige que le serveur connaisse le mot de passe **en
clair**, ce que sfGuard ne permet pas (hashs salés).

Conséquence : l'endpoint est ouvert et non authentifié. C'est ce qui rend la
pagination et le plafonnement des tailles de réponse obligatoires plutôt
qu'optionnels — voir « Pagination ».

### Structure de bibliothèque : albums chronologiques

Dans Subsonic, tout morceau appartient à un album et tout album à un artiste.
`Post` étant un flux plat, il faut fabriquer des albums.

Un album = **un mois de publication**, intitulé `Musique Approximative —
2024-06`. Le vrai artiste reste porté par chaque morceau.

Ce choix suit la nature réelle du corpus — un flux quotidien, pas une
discothèque — et produit des albums d'une trentaine de morceaux.
`getAlbumList2?type=newest` affiche alors le mois en cours en tête. Les vrais
artistes restent listés par `getArtists` et retrouvables par `search3` ;
ouvrir un artiste affiche les mois où il est passé.

L'alternative « un album par artiste » aurait produit plusieurs milliers
d'albums d'un seul morceau sur quinze ans de posts quotidiens. L'alternative
« album unique » aurait mis toute l'archive dans un seul objet, que la plupart
des clients tronquent.

### Métadonnées : durée et taille stockées en base

`Post` ne porte ni durée, ni taille, ni bitrate. Sans durée, la barre de
progression et le seek sont inopérants dans les clients — ce qui vide de son
sens l'objectif poursuivi. Deux colonnes sont ajoutées et rattrapées par une
tâche batch.

## Correspondance des identifiants

Les identifiants Subsonic sont des chaînes opaques. On les rend **réversibles**,
ce qui évite toute table de correspondance et toute requête de résolution.

| Entité   | Identifiant                     | Résolution                                     |
| -------- | ------------------------------- | ---------------------------------------------- |
| Morceau  | `<post.id>`                     | clé primaire directe                           |
| Album    | `al-2024-06`                    | `publish_on` dans le mois                      |
| Artiste  | `ar-<base64url(track_author)>`  | décodage → `WHERE track_author = ?`            |
| Playlist | `pl-<username>`                 | `WHERE u.username = ?`                         |
| Pochette | `co-<post.id>` / `co-al-2024-06`| fichier avatar                                 |

Le base64url pour les artistes (`strtr(base64_encode($a), '+/', '-_')` sans
padding) évite le hachage, qui aurait imposé de parcourir tous les
`track_author` distincts à chaque requête pour retrouver l'original.

Un identifiant malformé ou non résoluble renvoie l'erreur `70`.

### Champs d'un morceau

| Champ Subsonic | Source                                            |
| -------------- | ------------------------------------------------- |
| `id`           | `post.id`                                         |
| `title`        | `track_title`                                     |
| `artist`       | `track_author`                                    |
| `artistId`     | `ar-<base64url(track_author)>`                    |
| `album`        | `Musique Approximative — YYYY-MM`                 |
| `albumId`      | `al-YYYY-MM`                                      |
| `track`        | rang dans le mois — **uniquement dans `getAlbum`** |
| `year`         | année de `publish_on`                             |
| `created`      | `publish_on` au format ISO 8601                   |
| `suffix`       | extension de `track_filename`                     |
| `contentType`  | déduit de l'extension (`audio/mpeg`…)             |
| `duration`     | `track_duration` — **omis si nul**                |
| `size`         | `track_size` — **omis si nul**                    |
| `coverArt`     | `co-<post.id>`                                    |
| `isDir`        | `false`                                           |
| `path`         | `<YYYY-MM>/<track_filename>`                      |

Un attribut absent est mieux géré par les clients qu'une valeur `0` : `duration`
et `size` sont omis tant que les colonnes ne sont pas remplies.

**`track` n'est émis que par `getAlbum`.** MySQL 5.7 (`docker-compose.yml`) n'a
pas de fonctions de fenêtrage. Dans `getAlbum` le rang se calcule en PHP sur le
mois déjà chargé ; ailleurs (`getSong`, `search3`, `getPlaylist`) il exigerait
une sous-requête corrélée par ligne. `track` étant optionnel dans le protocole,
il est simplement omis hors `getAlbum`.

### Champs d'un album et d'un artiste

L'objet `album` porte `id`, `name`, `created`, `year`, `coverArt`, `songCount`,
`duration`, et `artist="Various Artists"` **sans `artistId`**.

Un mois contient trente artistes différents et Subsonic n'en accepte qu'un.
`Various Artists` est la convention de compilation que les clients
comprennent ; omettre `artistId` (optionnel) évite une référence pendante vers
un artiste que `getArtists` ne renverra jamais, ce qui fait planter certains
clients.

`songCount` et `duration` proviennent du même
`GROUP BY DATE_FORMAT(publish_on, '%Y-%m')` que la liste des mois, avec
`COUNT(*)` et `SUM(track_duration)`. Coût nul.

L'objet `artist` porte `id`, `name`, `coverArt` et `albumCount` (nombre de mois
distincts où l'artiste apparaît).

## Périmètre des méthodes

### Méthodes réelles

`ping`, `getLicense`, `getMusicFolders`, `getArtists`, `getArtist`, `getAlbum`,
`getAlbumList2`, `getSong`, `search3`, `getPlaylists`, `getPlaylist`,
`getCoverArt`, `stream`, `download`.

`stream` et `download` partagent la même implémentation.

`getLicense` annonce `valid="true"` sans date d'expiration. `getMusicFolders`
renvoie un dossier unique, `id="0"`, nommé `Musique Approximative`.

`getArtists` regroupe les artistes par lettre initiale dans des éléments
`index` (`ignoredArticles` vide) ; les initiales non alphabétiques tombent dans
un index `#`.

`getAlbumList2` gère `type` = `newest` (défaut), `alphabeticalByName`, `random`,
`byYear`, ainsi que `frequent` et `recent` qui retombent sur `newest` faute de
statistiques d'écoute.

`getPlaylists` renvoie un élément par contributeur ayant au moins un post en
ligne, avec `songCount` et `duration` obtenus en un seul `GROUP BY` sur
`contributor_id`.

### Alias legacy

`getAlbumList` et `search2` renvoient les mêmes données que leurs équivalents
en `2`/`3`, avec la sérialisation attendue par les anciennes versions du
protocole. `getStarred` accompagne `getStarred2`.

### Talons

Réponse vide ou succès sans effet, parce que les clients les appellent au
démarrage et qu'une erreur y produit des popups inutiles :

`getUser` (droits en lecture seule), `getStarred2`, `getGenres`,
`getNowPlaying`, `getVideos`, `getRandomSongs`, et `scrobble` qui répond `ok`
sans rien enregistrer.

### Explicitement refusées

`star`, `unstar`, `createPlaylist`, `updatePlaylist`, `deletePlaylist`
renvoient l'erreur Subsonic `50` (« opération non autorisée »). Comportement
standard d'un serveur en lecture seule, correctement interprété par les
clients.

### Hors périmètre pour l'instant

`getIndexes` et `getMusicDirectory` — la navigation legacy par répertoires
qu'utilisent les clients anciens (DSub notamment). Tous les clients modernes
passent par la navigation ID3. À ajouter uniquement si un client réellement
utilisé le réclame.

## Architecture

### Routage et répartition

Un module `rest` dans l'application `frontend`. Deux routes vers la même
action, les clients historiques appelant `/rest/ping.view` et certains clients
OpenSubsonic `/rest/ping` :

```yaml
subsonic_view:
  url: /rest/:method.view
  param: { module: rest, action: index }
  requirements: { method: \w+ }

subsonic:
  url: /rest/:method
  param: { module: rest, action: index }
  requirements: { method: \w+ }
```

`restActions::executeIndex()` répartit en préfixant le nom reçu —
`subsonicPing`, `subsonicGetAlbum`… — et en vérifiant l'existence de la méthode
protégée correspondante. Le préfixe sert de liste blanche implicite : aucune
méthode arbitraire de `sfActions` n'est atteignable depuis l'URL. Méthode
inconnue → erreur `70`. Paramètre requis manquant → erreur `10`.

Chaque gestionnaire renvoie un tableau PHP. Il ne touche ni à la réponse, ni à
la sérialisation, ni aux en-têtes.

### Couche de requêtes : une seule règle de visibilité

**Toutes** les requêtes Subsonic passent par `PostTable::buildOnlinePostsQuery()`
(promue `public`), qui porte déjà la règle de visibilité complète :
`is_online = 1`, `publish_on <= now() + 2h`, slug non vide.

La règle est aujourd'hui recopiée dans six méthodes de `PostTable`. Ajouter six
copies supplémentaires sur une surface publique non authentifiée est le moyen
le plus court d'exposer un jour des posts programmés ou retirés, sans qu'aucun
test ne le signale.

Cela résout aussi un second problème : `PostTable::FIELDS_BASIC` n'inclut ni
`publish_on` ni `created_at`, et Doctrine 1 charge silencieusement les colonnes
manquantes une par une au premier accès — un `SELECT` supplémentaire par
enregistrement. La sérialisation d'un morceau lit `publish_on`, donc un
`getAlbum` de trente titres aurait déclenché trente-et-une requêtes.

`buildOnlinePostsQuery()` reçoit un **argument optionnel de liste de champs**
(défaut `'*'`). Les requêtes Subsonic passent une liste explicite **sans
`body`** : cette colonne `TEXT` n'est jamais sérialisée par l'API et pèse lourd
sur un `getPlaylist` de plusieurs centaines de morceaux.

Méthodes ajoutées à `PostTable`, toutes construites sur ce socle :
`getMonths()` (avec `COUNT(*)` et `SUM(track_duration)`), `getPostsByMonth()`,
`getDistinctArtists()` (avec `albumCount`), `getMonthsByArtist()`,
`getPostsByArtist()`, `getContributors()` (avec `songCount` et `duration`),
`searchArtists()`, `searchSongs()`.

### Pagination

L'endpoint étant ouvert, la pagination n'est pas un confort : c'est ce qui
empêche une requête unique de faire tomber la base.

- `getAlbumList2` : `size` (défaut 10, **plafonné à 500**), `offset`.
- `search3` : `artistCount`/`artistOffset`, `albumCount`/`albumOffset`,
  `songCount`/`songOffset` (défaut 20 chacun), mêmes plafonds.
- `getRandomSongs` : `size` (défaut 10, plafonné à 500).

Toute valeur reçue est bornée avant d'atteindre le `LIMIT`.

### `search3`

`PostTable::search()` **n'est pas réutilisable ici**. Elle enveloppe le
comportement `Searchable` de Doctrine sur `post_index`, ne renvoie que des
identifiants de posts classés — donc aucun résultat « artiste » ni « album » —
et déclenche une requête par résultat, sans borne
(`PostTable.class.php:158-172`). Sur un endpoint ouvert appelé à chaque frappe
par les clients qui font de la recherche incrémentale, c'est une amplification
gratuite.

`search3` est donc implémentée directement sur `buildOnlinePostsQuery()` :

- **artistes** : `DISTINCT track_author LIKE ?`
- **morceaux** : `track_title LIKE ? OR track_author LIKE ?`
- **albums** : toujours vide — un album chronologique nommé `2024-06` n'a rien
  de pertinent à faire correspondre.

Une `query` vide renvoie l'ensemble non filtré et paginé, ce que les clients
utilisent pour « tout parcourir ».

Contrepartie assumée : la recherche de l'API et celle du site n'utilisent plus
le même moteur et peuvent classer différemment. Les deux couvrent les mêmes
deux colonnes, et aucun consommateur ne compare les deux classements.

### Sérialisation

Un helper `SubsonicResponse` (dans `src/lib/helper/`, aux côtés de
`ApiResponse`) enveloppe le tableau dans `subsonic-response` — attributs
`status`, `version="1.16.1"`, `type="musiqueapproximative"` — et sérialise en
XML ou en JSON selon `f=`, avec JSONP si `callback` est fourni.

Convention :

- valeur scalaire → attribut XML / clé JSON
- tableau associatif → élément enfant unique
- tableau indexé → éléments répétés en XML, tableau en JSON

**Les collections répétables sont déclarées explicitement.** Le sérialiseur
tient une constante listant les noms d'éléments répétables (`song`, `album`,
`artist`, `index`, `playlist`, `musicFolder`, `child`, `entry`). Sans cela une
collection vide est indistinguable d'un objet vide en PHP — `[]` dans les deux
cas — et `json_encode` émet `[]` là où Subsonic attend `{}`. Les clients
strictement typés (Symfonium et les autres clients Kotlin désérialisent vers
des types déclarés) lèvent une exception. Ce n'est pas un cas limite : les sept
talons ne renvoient que des conteneurs vides, et les clients les appellent à la
connexion. Le XML n'est pas concerné.

**XML : `SimpleXMLElement` + `addAttribute()`, valeurs brutes, aucun
pré-échappement.** `addAttribute()` échappe déjà. Le seul précédent du dépôt,
`executeOembed` (`actions.class.php:262`), applique `htmlentities()` *puis*
`addChild()` et produit du double-encodage ; il ne doit pas servir de modèle.
Un test avec `&` et `<` dans un titre de morceau verrouille le comportement.

### URL des morceaux

Une méthode `Post::getTrackUrl()` devient la seule définition, et **les cinq
sites d'appel passent par elle** :

| Site | État actuel |
| --- | --- |
| `Post::toJson()` (`Post.class.php:47`) | `urlencode` sur un segment de chemin → espace encodé en `+`, URL non résoluble |
| `executeShow` (`actions.class.php:56`) | `rawurlencode` sur l'URL entière → `%2F%2Fdomain%2Ftracks%2F…` |
| `listSuccess.xspf.php:33` | correct |
| `executeFeed` (`actions.class.php:204`) | correct, mais base d'URL différente |
| Subsonic `stream`/`download` | nouveau |

Règle : `rawurlencode` sur le **nom de fichier seul**, base d'URL unique. Sur
un site français, les noms de fichiers contiennent des espaces et des accents :
se tromper ici fait échouer le streaming morceau par morceau, silencieusement.

Corriger les deux sites cassés modifie la sortie de deux endpoints publics.
C'est assumé : ils émettent aujourd'hui des URL qui ne résolvent pas.

### Streaming

`stream` et `download` répondent par une redirection **302** vers le fichier
statique servi par Nginx. Aucun octet ne transite par PHP, le cache Cloudflare
des fichiers est préservé, et le support des requêtes `Range` est acquis
gratuitement.

**Ne pas utiliser `$this->redirect()`.** `app_urls_tracks` vaut
`//${APP_DOMAIN}/tracks` (relatif au protocole). `sfWebController::genUrl()` ne
laisse passer une chaîne telle quelle que si elle correspond à
`^[a-z][a-z0-9+.\-]*://` ; `//host/path` échoue ce test et repart dans la
génération de route, produisant un `Location` relatif au site. Il faut
construire une URL absolue avec schéma et l'émettre directement :

```php
$this->getResponse()->setStatusCode(302);
$this->getResponse()->setHttpHeader('Location', $url);
return sfView::NONE;
```

Risque assumé : une minorité de clients ne suit pas les redirections sur
`stream`. Le repli, si le cas se présente, est un proxy `readfile()` avec
gestion de `Range` — non écrit tant que le besoin n'est pas constaté.

`maxBitRate` et `format` sont ignorés : sans ffmpeg dans l'image Docker, le
fichier original est toujours servi. Comportement légal côté protocole.

### Pochettes

`getCoverArt` redirige vers `/avatars/<post.id>.png`. La pochette d'un album
mensuel est l'avatar du premier post du mois.

Ces fichiers sont probablement absents pour l'essentiel de l'archive : dans
`Post::postSave`, l'appel de génération est commenté (`// $process->run();`).
Le design n'en dépend donc pas — avatar absent → redirection vers
`/theme/<theme>/images/logo_500.png`.

### Caches et session — le module `rest` doit sortir de trois mécanismes

Trois comportements globaux de l'application cassent une API paramétrée par
query string. Aucun n'est visible en développement.

**1. Cache de pages Symfony.** `apps/frontend/config/cache.yml` déclare
`default: { enabled: true, with_layout: true, lifetime: 86400 }` — un
`cache.yml` au niveau application s'applique à *tous* les modules — et
`settings.yml` active `cache: true` en production. La clé de cache est
construite depuis `getCurrentInternalUri()`, c'est-à-dire les paramètres de
**route** uniquement : `id`, `query`, `size`, `offset`, `f`, `callback` n'y
figurent pas. `getAlbum.view?id=al-2024-06` et `?id=al-2019-03` partagent la
même clé, et un client JSON reçoit le XML mis en cache par le premier appelant.

→ `src/apps/frontend/modules/rest/config/cache.yml` avec
`default: { enabled: false }`. À vérifier par une requête réelle en
environnement `prod`.

**2. Session.** `sfDesastreFilter` est global et appelle
`$this->context->getUser()->getAttribute(…)` **avant** son test de type de
contenu (`sfDesastreFilter.class.php:29-31`), ce qui instancie `sfUser` et
démarre une session. Les clients Subsonic interrogent `ping` et
`getAlbumList2` en continu et jettent les cookies : un fichier de session par
requête, dans un répertoire que rien ne purge. Le `Set-Cookie` rend en outre
toute réponse non cacheable en périphérie.

→ `sf_use_session: false` pour le module `rest`.

**3. Cloudflare.** Le site est derrière Cloudflare (tâche de purge et
configuration `app_cloudflare.*`). Sans en-tête explicite, une seconde couche
de cache de 24 h se réinstalle devant l'API.

→ `Cache-Control: no-store` sur toutes les réponses `/rest`. Les fichiers audio
restent cachés ; l'API ne l'est pas.

Par ailleurs, `sfConfig::set('sf_web_debug', false)` dans le module `rest`,
comme le fait déjà `executeMd5` — sinon la barre de debug corrompt les réponses
en développement.

### Filtres

`JsonApiFilter` réécrit le `Content-Type` de toute réponse contenant « json »
en `application/vnd.api+json`, ce qui casserait les clients Subsonic. Il reçoit
une garde : sortie immédiate si le module courant est `rest`.

## Schéma et remplissage des métadonnées

### Colonnes

```yaml
    track_duration: integer   # secondes
    track_size:     integer   # octets
```

Nullables volontairement : le rattrapage peut être progressif, et l'API omet
l'attribut correspondant tant que la valeur manque.

### Index

```yaml
  indexes:
    online_publish_idx:
      fields: [is_online, publish_on]
    track_author_idx:
      fields:
        track_author:
          length: 191
```

`Post` ne porte aujourd'hui aucun index hors celui du slug. Les clés du bloc
`indexes:` s'écrivent **sans** suffixe `_idx` : Doctrine 1 l'ajoute lui-même,
et `online_publish_idx:` produirait `online_publish_idx_idx`.

La longueur de préfixe sur `track_author` n'est pas décorative : la colonne est
`varchar(2000)` en latin1, soit 2000 octets. C'est sous la limite de 3072
octets de MySQL 5.7 en format `DYNAMIC`, donc l'index passe ici — mais la
version de MySQL de l'hôte de production n'est documentée nulle part, et un
préfixe de 191 ne coûte rien.

### Ce que ces index achètent, mesuré

Sur les 6 155 lignes actuelles, `EXPLAIN` donne :

| Requête | Résultat |
| --- | --- |
| `WHERE is_online=1 AND publish_on<=NOW() ORDER BY publish_on DESC LIMIT 1` | `online_publish_idx`, **Using index** (couvrant) |
| `WHERE is_online=1 AND track_author = ?` | `track_author_idx`, `rows=1` |
| `GROUP BY DATE_FORMAT(publish_on,'%Y-%m')` | parcours complet, `key=NULL` |
| `GROUP BY track_author` / `DISTINCT track_author` | parcours complet, `key=NULL` |

Les deux premières formes couvrent l'essentiel du trafic existant du site —
`executeShow`, `executeNext`, `executePrev`, la home — ainsi que
`getPostsByArtist` et les filtres exacts de `search3`. C'est le vrai gain.

Les deux agrégats, eux, **ne bénéficient d'aucun index et n'en bénéficieront
pas** : `is_online = 1` couvre 99 % des lignes, donc l'optimiseur préfère à
juste titre le parcours complet ; et un index de préfixe ne peut pas servir un
`GROUP BY` ou un `DISTINCT`, seulement des égalités et des intervalles.

C'est assumé. À 6 155 lignes un parcours complet est bon marché, et le corpus
croît d'une ligne par jour. Le jour où ça se mesure, la réponse sera une
colonne générée indexée sur le mois, pas un index supplémentaire sur
`publish_on`.

### Remplissage

- **`Post::preSave`** — si le fichier est lisible et les colonnes vides. Pas de
  seconde écriture, et un fichier arrivant après la création du post ne bloque
  rien.
- **Tâche `musiqueapproximative:scan-tracks`** — calquée sur
  `musiqueapproximativeRebuildMd5Task`. Parcourt les posts à
  `track_duration IS NULL`, options `--force` et `--limit`. Un fichier manquant
  est signalé et compté, jamais fatal.

Lecture de la durée via `james-heinrich/getid3` (`playtime_seconds`), qui gère
les en-têtes Xing et le VBR. Taille via `filesize()`. `ffmpeg` n'est pas
présent dans l'image Docker et un parseur MP3 maison ne traiterait pas
correctement le VBR.

## Erreurs

Subsonic répond **toujours en HTTP 200**, y compris en erreur : le statut est
dans le corps, et le format `f=` doit être respecté sur les erreurs comme sur
les succès.

`SubsonicResponse::error($code, $message)` produit l'enveloppe
`status="failed"` contenant un élément `error`.

| Code | Usage                                                  |
| ---- | ------------------------------------------------------ |
| `0`  | erreur générique                                       |
| `10` | paramètre requis manquant                              |
| `50` | opération non autorisée (`star`, `createPlaylist`…)    |
| `70` | introuvable — identifiant ou méthode inconnus          |

Le code `40` (identifiants invalides) n'est jamais émis, l'authentification
étant ouverte.

## Tests

### Base de test et fixtures

`config/databases.yml-dist` ne déclare qu'une connexion `all:`. Le bootstrap
fonctionnel crée un environnement applicatif `test` mais pointe sur la même
base, et les fixtures existantes sont des dumps SQL de production de 3,5 Mo.
Aujourd'hui, un test fonctionnel qui affirme quoi que ce soit sur le contenu
est non déterministe, et tout test touchant `preSave` écrit dans la base de
développement.

Une connexion `test:` est ajoutée à `databases.yml-dist` (variable
`DATABASE_NAME_TEST` dans chaque `etc/<profil>/.env-dist`), accompagnée d'un
jeu de fixtures Doctrine YAML minimal :

- deux contributeurs, deux mois, une dizaine de posts ;
- un post `is_online = 0` ;
- un post daté dans le futur ;
- un post au slug vide ;
- un nom de fichier contenant espace, accent et `&` ;
- un `track_author` accentué.

Sans base seedée, l'assertion la plus importante de cette branche — qu'un post
non publié n'est pas atteignable via Subsonic — est impossible à écrire.

**Ceci invalide la note « aucune configuration de déploiement » de la première
version de cette spec.** Voir aussi la section Déploiement.

### Fichiers

Selon la convention existante (`src/test/unit/helper/`, `src/test/unit/filter/`) :

- `src/test/unit/helper/SubsonicResponseTest.php` — sérialisation XML et JSON
  d'une même structure, conteneurs vides rendus `{}` et non `[]`, échappement
  de `&` `<` `>` `"`, enveloppe JSONP, forme des réponses d'erreur.
- `src/test/unit/helper/SubsonicIdTest.php` — aller-retour des identifiants,
  avec les cas qui font mal : artistes contenant accents, espaces, `/`, `+` ;
  `al-YYYY-MM` ; `pl-<username>` ; identifiant malformé → `70`.
- `src/test/unit/model/PostTrackUrlTest.php` — **régression, critique.**
  `getTrackUrl()` produit `%20` et non `+` pour un espace ; `toJson()` émet
  bien cette URL ; l'URL OpenGraph de `executeShow` n'est plus encodée à
  travers ses barres obliques.
- `src/test/functional/frontend/restActionsTest.php` — `ping` dans les deux
  formats, méthode inconnue → `70`, paramètre manquant → `10`, `star` → `50`,
  `getAlbumList2` (types, `size` plafonné, `offset` honoré), `getAlbum`
  (rang de piste, `Various Artists`), `search3` (trois ensembles, requête vide,
  zéro résultat), `getPlaylists` (`songCount`, `duration`), `stream` → 302 vers
  une URL absolue correctement encodée, `getCoverArt` sans avatar → repli logo,
  et **post hors ligne / daté dans le futur absent de tous les résultats**.

Le stub généré `src/test/functional/frontend/postActionsTest.php` interroge
`/post/index`, une route qui n'existe pas. Il n'a jamais rien testé.

## Déploiement et exploitation

Quatre points qui ne sont pas des questions de code mais décident si la mise en
production se passe bien.

### Ordre des opérations

Il n'existe pas de répertoire de migrations, et `make deploy` est un simple
rsync. Comme la couche de requêtes utilise `select('*')` — que Doctrine 1
développe en la liste des colonnes déclarées par le modèle — **déployer le
nouveau `BasePost` avant d'avoir exécuté l'`ALTER TABLE` fait tomber tout le
site**, pas seulement l'API : chaque page lèverait
`Unknown column 'p.track_duration'`.

Ordre imposé :

1. `ALTER TABLE post ADD COLUMN track_duration INT NULL, ADD COLUMN track_size INT NULL;`
   puis les deux index, exécutés **sur la base de production d'abord** ;
2. `composer install` en local, puis `make deploy` (le rsync emporte
   `src/vendor`, dont getid3) ;
3. `php symfony cache:clear` sur l'hôte ;
4. `php symfony musiqueapproximative:scan-tracks` sur l'hôte.

### Dépendance getid3

`composer install` n'est lancé ni par le `Dockerfile` ni par `make deploy` : il
ne l'est que par la commande de démarrage de `docker-compose`. `/src/vendor`
est gitignoré mais les fichiers d'exclusion rsync sont vides, donc le vendor
part bien vers la production — à condition qu'il ait été construit localement.
Ajouter `composer install --no-dev` à la cible `deploy` du `Makefile` ferme le
trou.

### Rattrapage des durées

`/src/web/tracks` est gitignoré : les fichiers audio n'existent que sur l'hôte
distant. `scan-tracks` s'exécute donc en production, sur environ 7 000
fichiers, soit de l'ordre de dix à vingt minutes de lecture de tags. Utiliser
`--limit` pour lotir si nécessaire.

**Tant que le rattrapage n'est pas terminé, aucun morceau n'a de durée** — donc
le seek, qui justifie à lui seul l'ajout des colonnes, ne fonctionne pas. C'est
une étape de la mise en production, pas une tâche de fond optionnelle.

### Vérification post-déploiement

Une requête réelle en environnement `prod` sur deux albums différents, pour
confirmer que le cache de pages est bien désactivé sur `/rest` et qu'aucun
`Set-Cookie` n'est émis.

## Documentation

Une page `docs/API_SUBSONIC.md` listant les méthodes supportées et la
configuration d'un client : URL du serveur, identifiant et mot de passe
quelconques.

## Points assumés, hors périmètre

**Pas de cache applicatif sur `getArtists`.** La requête reste un `DISTINCT`
avec table temporaire sur quelques milliers de lignes, et aucun index ne
l'évite. Les clients l'appellent rarement. Pas de cache tant que la lenteur
n'est pas mesurée.

**Dimension artiste bâtie sur du texte libre.** `track_author` n'est jamais
canonicalisé : variantes de casse, « X feat. Y », ponctuation. `getArtists`
produira donc beaucoup d'artistes à un seul morceau — le problème même qui a
fait écarter « un album par artiste ». C'est acceptable parce que l'artiste
n'est ici qu'une dimension secondaire de navigation, la chronologie restant
l'axe principal.

**Navigation album → artiste sans issue.** Conséquence directe de
`Various Artists` sans `artistId`. Choisi en connaissance de cause : une
référence pendante casse davantage de clients qu'un cul-de-sac.

**Bug de `Post::postSave` non corrigé.** Outre la génération d'avatar
commentée, le test d'existence a une parenthèse mal placée —
`file_exists(sprintf('%s/avatars/%s.png'), $webDir, $postId)` passe les
arguments à `file_exists` au lieu de `sprintf`. Réel, signalé, hors périmètre.

**Double-encodage de `executeOembed` non corrigé.** Même famille que le
problème d'échappement XML traité ici, mais sur un endpoint que cette branche
ne touche pas.

**Pas de transcodage.** Voir la section Streaming.

**Image ghcr sans vendor.** Le `Dockerfile` copie `./src` sans exécuter
`composer install`, donc l'image publiée n'a pas de dépendances. Problème
préexistant, sans rapport avec cette branche, mais qui invalide l'image comme
chemin de déploiement.

## Implementation Tasks
Synthesized from this review's findings. Each task derives from a specific
finding above. Run with Claude Code or Codex; checkbox as you ship.

- [ ] **T1 (P1, human: ~1h / CC: ~10min)** — PostTable — Rendre `buildOnlinePostsQuery()` publique avec argument liste de champs, y router toutes les requêtes Subsonic
  - Surfaced by: Architecture A1 — règle de visibilité recopiée 6× + lazy-load N+1 (`FIELDS_BASIC` omet `publish_on`)
  - Files: `src/lib/model/doctrine/PostTable.class.php`
  - Verify: `php symfony test:unit` + assertion « post hors ligne absent »
- [ ] **T2 (P1, human: ~1h / CC: ~10min)** — Post — `Post::getTrackUrl()` et migration des 5 sites de construction d'URL
  - Surfaced by: Architecture A2 — `urlencode` sur un segment de chemin, `rawurlencode` sur une URL entière
  - Files: `src/lib/model/doctrine/Post.class.php`, `src/apps/frontend/modules/post/actions/actions.class.php`, `src/apps/frontend/modules/post/templates/listSuccess.xspf.php`
  - Verify: `php symfony test:unit model/PostTrackUrlTest`
- [ ] **T3 (P1, human: ~30min / CC: ~5min)** — schema — Colonnes `track_duration`/`track_size` + index `(is_online, publish_on)` et `track_author(191)`, puis `doctrine:build-model`
  - Surfaced by: Performance P1 — aucun index sur `publish_on` ni `track_author`
  - Files: `src/config/doctrine/schema.yml`
  - Verify: `EXPLAIN` sur `getMonths` montre un parcours d'index
- [ ] **T4 (P1, human: ~2h / CC: ~20min)** — rest — Routes `/rest/:method(.view)`, répartiteur à préfixe, garde `JsonApiFilter`
  - Surfaced by: Architecture — routage et répartition
  - Files: `src/apps/frontend/config/routing.yml`, `src/apps/frontend/modules/rest/actions/actions.class.php`, `src/lib/filter/JsonApiFilter.class.php`
  - Verify: `php symfony test:functional frontend/restActionsTest`
- [ ] **T5 (P1, human: ~2h / CC: ~20min)** — helper — `SubsonicResponse` : XML via `addAttribute` sans pré-échappement, ensemble explicite de collections répétables, JSONP, enveloppe d'erreur
  - Surfaced by: Code quality C1 et C2 — double-échappement XML, `[]` au lieu de `{}` en JSON
  - Files: `src/lib/helper/SubsonicResponse.php`
  - Verify: `php symfony test:unit helper/SubsonicResponseTest`
- [ ] **T6 (P1, human: ~1h / CC: ~10min)** — rest — `cache.yml` désactivé, `sf_use_session: false`, `Cache-Control: no-store`, `sf_web_debug` off
  - Surfaced by: Outside voice 1, 4 et 5 — cache de pages 24 h à clé sans query string, session par requête, Cloudflare
  - Files: `src/apps/frontend/modules/rest/config/cache.yml`, `src/apps/frontend/modules/rest/config/settings.yml`
  - Verify: deux requêtes `getAlbum` sur des `id` différents en env `prod` renvoient des albums différents, sans `Set-Cookie`
- [ ] **T7 (P1, human: ~30min / CC: ~5min)** — rest — `stream`/`download` en 302 brut (`setStatusCode` + `Location` absolue)
  - Surfaced by: Outside voice 3 — `genUrl()` mange une URL relative au protocole
  - Files: `src/apps/frontend/modules/rest/actions/actions.class.php`
  - Verify: `curl -sI` sur `/rest/stream.view?id=N` montre un `Location` absolu avec schéma
- [ ] **T8 (P1, human: ~2h / CC: ~20min)** — PostTable — `search3` sur filtres `LIKE` : trois ensembles, `query` vide = tout parcourir
  - Surfaced by: Outside voice 6 — `PostTable::search()` ne renvoie que des identifiants de posts
  - Files: `src/lib/model/doctrine/PostTable.class.php`, `src/apps/frontend/modules/rest/actions/actions.class.php`
  - Verify: `search3` renvoie des artistes et des morceaux, et une seule requête par ensemble
- [ ] **T9 (P1, human: ~1h / CC: ~10min)** — rest — Pagination `size`/`offset` plafonnée à 500
  - Surfaced by: Architecture A3 — aucune pagination sur un endpoint ouvert
  - Files: `src/apps/frontend/modules/rest/actions/actions.class.php`
  - Verify: `size=99999` est ramené à 500 ; `offset` au-delà de la fin renvoie une liste vide
- [ ] **T10 (P2, human: ~1h / CC: ~10min)** — rest — `getPlaylists`/`getPlaylist` avec `songCount` et `duration`, sans hydrater `body`
  - Surfaced by: Outside voice 8 — playlists non bornées, colonne `TEXT` chargée pour rien
  - Files: `src/lib/model/doctrine/PostTable.class.php`, `src/apps/frontend/modules/rest/actions/actions.class.php`
  - Verify: `getPlaylists` en une requête, `body` absent du SQL généré
- [ ] **T11 (P2, human: ~3h / CC: ~25min)** — model — `preSave` + tâche `scan-tracks` + dépendance getid3
  - Surfaced by: Décision de conception — durée et taille en base
  - Files: `src/lib/model/doctrine/Post.class.php`, `src/lib/task/musiqueapproximativeScanTracksTask.class.php`, `src/composer.json`
  - Verify: `php symfony musiqueapproximative:scan-tracks --limit=5` remplit cinq lignes
- [ ] **T12 (P1, human: ~4h / CC: ~30min)** — tests — Connexion `test:` + fixtures Doctrine YAML
  - Surfaced by: Revue de tests T1 — sans base seedée, la règle de visibilité est intestable
  - Files: `src/config/databases.yml-dist`, `src/data/fixtures/subsonic.yml`, `etc/*/.env-dist`
  - Verify: `php symfony doctrine:data-load` sur la base de test
- [ ] **T13 (P1, human: ~4h / CC: ~30min)** — tests — Quatre fichiers de test, dont `PostTrackUrlTest` en **régression critique**
  - Surfaced by: Revue de tests — 0/56 chemins couverts, 2 régressions introduites par T2
  - Files: `src/test/unit/helper/SubsonicResponseTest.php`, `src/test/unit/helper/SubsonicIdTest.php`, `src/test/unit/model/PostTrackUrlTest.php`, `src/test/functional/frontend/restActionsTest.php`
  - Verify: `php symfony test:all`
- [ ] **T14 (P2, human: ~2h / CC: ~20min)** — deploy — `composer install` dans la cible `deploy` + runbook d'ordonnancement
  - Surfaced by: Outside voice 2, 11 et 12 — `select('*')` fait tomber le site si le modèle part avant l'`ALTER`
  - Files: `Makefile`, cette spec
  - Verify: `make deploy` sur un profil de test aboutit avec un `src/vendor` peuplé
- [ ] **T15 (P2, human: ~1h / CC: ~10min)** — docs — `docs/API_SUBSONIC.md`
  - Surfaced by: Sortie requise — documentation
  - Files: `docs/API_SUBSONIC.md`
  - Verify: relecture

## Parallélisation

| Étape | Modules touchés | Dépend de |
| --- | --- | --- |
| T1, T8, T10 | `lib/model/doctrine/` | — |
| T2 | `lib/model/doctrine/`, `modules/post/` | — |
| T3, T11 | `config/doctrine/`, `lib/model/doctrine/`, `lib/task/` | — |
| T4, T5, T6, T7, T9 | `modules/rest/`, `lib/helper/`, `lib/filter/` | T1, T5 |
| T12, T13 | `test/`, `config/` | T1–T11 |
| T14, T15 | `Makefile`, `docs/` | — |

**Lane A :** T1 → T8 → T10 (séquentiel, `PostTable` partagé)
**Lane B :** T5 → T4 → T7 → T9 → T6 (séquentiel, module `rest` partagé ; démarre dès que T1 est fusionnée)
**Lane C :** T3 → T11 (séquentiel, `schema.yml` puis modèle)
**Lane D :** T14, T15 (indépendantes)

Ordre : lancer A, C et D en parallèle. Fusionner A, puis lancer B. T2 touche `lib/model/doctrine/` **et** `modules/post/` : la garder séquentielle après A pour éviter un conflit avec `Post.class.php` que T11 modifie aussi.

**Conflit signalé :** les lanes A, C et T2 écrivent toutes dans `src/lib/model/doctrine/`. Trois worktrees parallèles y produiront des conflits de fusion sur `Post.class.php` et `PostTable.class.php`. Soit les séquencer, soit accepter une résolution manuelle.

## GSTACK REVIEW REPORT

| Review | Trigger | Why | Runs | Status | Findings |
|--------|---------|-----|------|--------|----------|
| CEO Review | `/plan-ceo-review` | Scope & strategy | 0 | — | — |
| Codex Review | `/codex review` | Independent 2nd opinion | 0 | — | — |
| Eng Review | `/plan-eng-review` | Architecture & tests (required) | 1 | CLEAR (PLAN) | 20 issues, 0 critical gaps |
| Design Review | `/plan-design-review` | UI/UX gaps | 0 | — | — |
| DX Review | `/plan-devex-review` | Developer experience gaps | 0 | — | — |

**OUTSIDE VOICE:** Codex unavailable (`gpt-5.1-codex-mini` unsupported on a ChatGPT account); ran as a Claude subagent. 12 findings, 8 confirmed on verification, 3 downgraded, 1 declined as already-decided. All confirmed findings folded in.

**CROSS-MODEL:** One genuine tension, resolved against the review: the `track_author` index does not accelerate `getArtists`, since a `DISTINCT` on that column builds a temporary table regardless. The spec now says so explicitly.

**VERDICT:** ENG CLEARED — ready to implement.

NO UNRESOLVED DECISIONS
