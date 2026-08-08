# API Subsonic

Musique Approximative expose son archive via l'API Subsonic 1.16.1, en lecture
seule. N'importe quel client Subsonic peut donc parcourir et écouter le
catalogue.

## Configuration d'un client

| Champ | Valeur |
| --- | --- |
| Adresse du serveur | `https://www.musiqueapproximative.net` |
| Nom d'utilisateur | n'importe lequel |
| Mot de passe | n'importe lequel |

L'authentification est ouverte : les fichiers audio sont déjà servis
publiquement en statique depuis `/tracks/`, une authentification ne
protégerait rien. Les clients qui exigent un mot de passe non vide accepteront
n'importe quelle valeur, et les deux modes du protocole — mot de passe en clair
et jeton `t`/`s` — fonctionnent indifféremment.

## Structure de la bibliothèque

Musique Approximative est un flux quotidien, pas une discothèque. La
bibliothèque est donc organisée chronologiquement :

- **un album = un mois de publication**, nommé `Musique Approximative — 2024-06` ;
- l'artiste de chaque morceau reste le véritable artiste ;
- l'artiste d'un album est `Various Artists`, un mois contenant une trentaine
  d'artistes différents ;
- **une playlist = un contributeur**, avec l'ensemble de ses morceaux.

`getAlbumList2?type=newest` affiche donc le mois en cours en tête, ce qui est
l'usage attendu : on ouvre l'application, les derniers morceaux sont là.

## Méthodes supportées

Navigation et lecture : `ping`, `getLicense`, `getMusicFolders`, `getArtists`,
`getArtist`, `getAlbum`, `getAlbumList`, `getAlbumList2`, `getSong`,
`getRandomSongs`, `search2`, `search3`, `getPlaylists`, `getPlaylist`,
`getCoverArt`, `stream`, `download`.

Répondent vide, pour ne pas déclencher d'erreur au démarrage des clients :
`getUser`, `getStarred`, `getStarred2`, `getGenres`, `getNowPlaying`,
`getVideos`, `scrobble`.

Renvoient l'erreur 50 (serveur en lecture seule) : `star`, `unstar`,
`createPlaylist`, `updatePlaylist`, `deletePlaylist`.

Non implémentées : `getIndexes` et `getMusicDirectory`, la navigation legacy
par répertoires. Tous les clients modernes passent par la navigation ID3. À
ajouter si un client réellement utilisé les réclame.

Conformément au protocole, **toutes les réponses sont en HTTP 200**, y compris
les erreurs : le statut est dans le corps. Les formats `f=xml` (défaut),
`f=json` et `f=jsonp` sont supportés.

## Limites connues

- **Pas de transcodage.** `maxBitRate` et `format` sont ignorés, le fichier
  d'origine est toujours servi. Il n'y a pas de ffmpeg sur l'hôte.
- **Pas de favoris ni de scrobbling.** Les écoutes ne sont pas enregistrées, et
  `getAlbumList2?type=frequent` ou `recent` retombent donc sur `newest`.
- **La recherche d'albums est toujours vide.** Un album chronologique nommé
  `2024-06` n'a rien de pertinent à faire correspondre à une requête textuelle.
  Les recherches d'artistes et de morceaux fonctionnent normalement.
- **Les durées n'apparaissent qu'après le rattrapage.** Tant que
  `musiqueapproximative:scan-tracks` n'a pas tourné sur l'hôte, les morceaux
  n'ont ni durée ni taille, donc pas de barre de progression ni de seek.
- **Les pochettes retombent sur le logo du site**, la génération d'avatars
  étant désactivée dans `Post::postSave()`.
- **`getArtists` n'est pas paginé** — le protocole ne le prévoit pas. Sur les
  5 100 artistes actuels la réponse pèse environ 360 Ko, téléchargés à chaque
  rafraîchissement de bibliothèque.

## Exemples

```bash
curl 'https://www.musiqueapproximative.net/rest/ping.view?f=json&u=x&p=x&c=test&v=1.16.1'
curl 'https://www.musiqueapproximative.net/rest/getAlbumList2.view?f=json&type=newest&size=5'
curl 'https://www.musiqueapproximative.net/rest/search3.view?f=json&query=bowie'
```

## Déploiement

L'ordre compte. `PostTable::buildOnlinePostsQuery()` utilise `select('*')`, que
Doctrine 1 développe en la liste des colonnes **déclarées par le modèle** :
déployer le nouveau `BasePost` avant d'avoir exécuté l'`ALTER TABLE` fait
tomber tout le site, pas seulement l'API, chaque page levant
`Unknown column 'p.track_duration'`.

1. Sur la base de production, d'abord :

   ```sql
   ALTER TABLE post
     ADD COLUMN track_duration INT NULL COMMENT 'Duree du morceau en secondes',
     ADD COLUMN track_size INT NULL COMMENT 'Taille du fichier en octets',
     ADD INDEX online_publish_idx (is_online, publish_on),
     ADD INDEX track_author_idx (track_author(191));
   ```

2. `make deploy RSYNC_PARAMETERS=` — la cible construit désormais `src/vendor`
   avant de synchroniser, donc getid3 part avec.
3. `php symfony cache:clear` sur l'hôte.
4. `php symfony musiqueapproximative:scan-tracks --limit=200` sur l'hôte pour
   mesurer, puis sans limite pour les ~7 000 fichiers. Tant que ce n'est pas
   terminé, aucun morceau n'a de durée.

Vérifications après déploiement :

- deux requêtes `getAlbum` sur des identifiants différents renvoient bien deux
  albums différents (le cache de pages applicatif est neutralisé pour le module
  `rest`, mais cela se vérifie) ;
- les réponses `/rest` portent `Cache-Control: no-store` ;
- `/rest/ping.view` répond, et pas seulement `/rest/ping` — la forme avec
  suffixe est celle qu'utilisent les clients historiques.
