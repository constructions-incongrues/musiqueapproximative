# Support de l'API Subsonic

> Spec de conception — 2026-08-07

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
clair**, ce que sfGuard ne permet pas (hashs salés). Un compte partagé aurait
imposé de stocker un mot de passe en clair en configuration, un compte par
contributeur d'en stocker un par utilisateur en base.

Conséquence directe : ce design n'ajoute **aucune variable d'environnement ni
aucun fichier `-dist`** à la configuration de déploiement.

### Structure de bibliothèque : albums chronologiques

Dans Subsonic, tout morceau appartient à un album et tout album à un artiste.
`Post` étant un flux plat, il faut fabriquer des albums.

Un album = **un mois de publication**, intitulé `Musique Approximative —
2024-06`. Le vrai artiste reste porté par chaque morceau.

Ce choix suit la nature réelle du corpus — un flux quotidien, pas une
discothèque — et produit des albums d'une trentaine de morceaux.
`getAlbumList2?type=newest` affiche alors le mois en cours en tête, ce qui est
exactement l'usage visé. Les vrais artistes restent listés par `getArtists` et
retrouvables par `search3` ; ouvrir un artiste affiche les mois où il est
passé.

L'alternative « un album par artiste » aurait produit plusieurs milliers
d'albums d'un seul morceau sur quinze ans de posts quotidiens, rendant la vue
albums inexploitable. L'alternative « album unique » aurait mis toute
l'archive dans un seul objet, que la plupart des clients tronquent ou peinent
à afficher.

### Métadonnées : durée et taille stockées en base

`Post` ne porte ni durée, ni taille, ni bitrate. Sans durée, la barre de
progression et le seek sont inopérants dans les clients — ce qui vide de son
sens l'objectif poursuivi. Deux colonnes sont donc ajoutées et rattrapées par
une tâche batch (détail plus bas).

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

### Champs d'un morceau

| Champ Subsonic | Source                                            |
| -------------- | ------------------------------------------------- |
| `id`           | `post.id`                                         |
| `title`        | `track_title`                                     |
| `artist`       | `track_author`                                    |
| `artistId`     | `ar-<base64url(track_author)>`                    |
| `album`        | `Musique Approximative — YYYY-MM`                 |
| `albumId`      | `al-YYYY-MM`                                      |
| `track`        | rang du post dans son mois (par `publish_on`)     |
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

## Périmètre des méthodes

### Méthodes réelles

`ping`, `getLicense`, `getMusicFolders`, `getArtists`, `getArtist`, `getAlbum`,
`getAlbumList2`, `getSong`, `search3`, `getPlaylists`, `getPlaylist`,
`getCoverArt`, `stream`, `download`.

`stream` et `download` partagent la même implémentation.

`getLicense` annonce `valid="true"` sans date d'expiration. `getMusicFolders`
renvoie un dossier unique, `id="0"`, nommé `Musique Approximative`.

`getArtists` regroupe les artistes par lettre initiale dans des éléments
`index` (`ignoredArticles` vide) ; les initiales non alphabétiques tombent
dans un index `#`.

`getAlbumList2` gère `type` = `newest` (défaut), `alphabeticalByName`, `random`,
`byYear`, ainsi que `frequent` et `recent` qui retombent sur `newest` faute de
statistiques d'écoute.

`getPlaylists` liste un élément par contributeur ayant au moins un post en
ligne ; `getPlaylist` renvoie ses morceaux, réutilisant
`PostTable::getOnlinePosts($username)` déjà présent.

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
renvoient l'erreur Subsonic `50` (« opération non autorisée »). C'est le
comportement standard d'un serveur en lecture seule, correctement interprété
par les clients.

### Hors périmètre pour l'instant

`getIndexes` et `getMusicDirectory` — la navigation legacy par répertoires
qu'utilisent les clients anciens (DSub notamment). Tous les clients modernes
passent par la navigation ID3 (`getArtists` / `getArtist` / `getAlbum`). À
ajouter uniquement si un client réellement utilisé le réclame.

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
inconnue → erreur `70`.

Chaque gestionnaire renvoie un tableau PHP. Il ne touche ni à la réponse, ni à
la sérialisation, ni aux en-têtes.

Les requêtes vivent dans `PostTable`, pas dans l'action : `getMonths()`,
`getPostsByMonth()`, `getDistinctArtists()`, `getMonthsByArtist()`,
`getPostsByArtist()`, `getContributors()`.

### Sérialisation

Un helper `SubsonicResponse` (dans `src/lib/helper/`, aux côtés de
`ApiResponse`) enveloppe le tableau dans `subsonic-response` — attributs
`status`, `version="1.16.1"`, `type="musiqueapproximative"` — et sérialise en
XML ou en JSON selon `f=`, avec JSONP si `callback` est fourni.

Une convention unique, qui décrit exactement la forme des documents Subsonic :

- valeur scalaire → attribut XML / clé JSON
- tableau associatif → élément enfant unique
- tableau indexé → éléments répétés en XML, tableau en JSON

C'est ce qui permet d'écrire chaque gestionnaire une seule fois pour les deux
formats, sans annotation ni classe par entité.

### Filtres

`JsonApiFilter` réécrit le `Content-Type` de toute réponse contenant « json »
en `application/vnd.api+json`, ce qui casserait les clients Subsonic. Il reçoit
une garde : sortie immédiate si le module courant est `rest`.

`sfDesastreFilter` est global mais inoffensif ici — il n'agit que sur du
`text/html` et seulement si l'action a appelé `apply_desastre`, ce que le
module `rest` ne fait pas. Aucune modification nécessaire.

### Streaming

`stream` et `download` répondent par une redirection **302** vers
`app_urls_tracks/<track_filename>` — le fichier statique déjà servi par Nginx.
Aucun octet ne transite par PHP, le cache Cloudflare est préservé, et le
support des requêtes `Range` est acquis gratuitement.

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

## Schéma et remplissage des métadonnées

Deux colonnes nullables ajoutées à `Post` dans `src/config/doctrine/schema.yml` :

```yaml
    track_duration: integer   # secondes
    track_size:     integer   # octets
```

Suivi de `doctrine:build-model`.

Elles sont nullables volontairement : le rattrapage peut être progressif, et
l'API omet l'attribut correspondant tant que la valeur manque.

Le remplissage se fait à deux endroits :

- **`Post::preSave`** — si le fichier est lisible et les colonnes vides. Pas de
  seconde écriture, et un fichier arrivant après la création du post ne bloque
  rien.
- **Tâche `musiqueapproximative:scan-tracks`** — calquée sur
  `musiqueapproximativeRebuildMd5Task`. Parcourt les posts à
  `track_duration IS NULL`, options `--force` (recalcul intégral) et `--limit`.
  Un fichier manquant est signalé et compté, jamais fatal.

Lecture de la durée via `james-heinrich/getid3` (`playtime_seconds`), qui gère
les en-têtes Xing et le VBR. Taille via `filesize()`. C'est la seule dépendance
nouvelle du projet ; `ffmpeg` n'est pas présent dans l'image Docker et un
parseur MP3 maison ne traiterait pas correctement le VBR.

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

En Lime, suivant l'organisation existante.

`test/unit/SubsonicResponseTest.php`
: la même structure sérialisée en XML et en JSON, listes vides, échappement des
caractères spéciaux, enveloppe JSONP, forme des réponses d'erreur.

`test/unit/SubsonicIdTest.php`
: aller-retour des identifiants, avec les cas qui font mal — artistes contenant
accents, espaces, `/` ou `+` ; `al-YYYY-MM` ; `pl-<username>`.

`test/functional/frontend/restActionsTest.php`
: `ping` dans les deux formats, méthode inconnue → `70`, `getAlbumList2`,
`getAlbum`, `search3`, `getPlaylists`, `stream` → 302, `getCoverArt` sans
avatar → repli logo, `star` → `50`.

## Documentation

Une page `docs/API_SUBSONIC.md` listant les méthodes supportées et la
configuration d'un client : URL du serveur, identifiant et mot de passe
quelconques.

## Points assumés, hors périmètre

**Pas de cache.** `getArtists` fait un `SELECT DISTINCT` sur plusieurs milliers
de lignes à chaque appel. La requête est indexable, MySQL s'en sort, et les
clients l'appellent rarement. Pas de cache tant que la lenteur n'est pas
mesurée.

**Bug de `Post::postSave` non corrigé.** Outre la génération d'avatar
commentée, le test d'existence juste au-dessus a une parenthèse mal placée —
`file_exists(sprintf('%s/avatars/%s.png'), $webDir, $postId)` passe les
arguments à `file_exists` au lieu de `sprintf`. Réel, mais étranger à ce
travail : signalé, pas corrigé ici.

**Pas de transcodage.** Voir la section Streaming.
