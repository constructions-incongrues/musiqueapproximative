> Pas de `design.md`. Trois gabarits alignés sur une méthode du modèle qui existe déjà et
> qui est la référence — aucune architecture, aucune dépendance, aucune migration. La seule
> contrainte à connaître est écrite dans la proposition : en production le domaine configuré
> et l'hôte de la requête coïncident, ce qui a masqué l'écart.

## 1. Relever l'état de départ

- [x] 1.1 Pour un morceau dont le nom de fichier **contient un espace**, relever l'adresse
  servie dans chacune des cinq représentations : JSON, `max` liste, `max` isolé, XSPF liste,
  XSPF isolé. C'est le tableau que la correction doit rendre uniforme.
- [x] 1.2 Relever la valeur de `app_urls_tracks` et l'hôte de la requête, pour établir
  qu'ils diffèrent dans l'environnement de vérification — sans quoi la correction ne se
  démontre pas.

## 2. Aligner les gabarits

- [x] 2.1 `showSuccess.max.php` : remplacer la construction à la main par
  `$post->getTrackUrl(...)`, comme le fait `listSuccess.max.php`. C'est le seul des trois
  qui n'encode pas.
- [x] 2.2 `_xspfPlaylist.xspf.php` : construire l'adresse par le modèle plutôt que depuis
  `$baseUrl`. Le partiel étant hermétique, vérifier qu'il a bien accès à ce qu'il faut —
  son en-tête documente cette contrainte.
- [x] 2.3 Retirer `baseUrl` de ce que lui passent `listSuccess.xspf.php` et
  `showSuccess.xspf.php` **si et seulement si** plus rien d'autre ne s'en sert. Le partiel
  l'utilise aussi ailleurs : vérifier avant de retirer.
- [x] 2.4 Ne toucher ni à `getTrackUrl()` ni à `buildTrackUrl()` : elles font déjà ce qu'il
  faut et servent de référence.

## 3. Vérifier

- [x] 3.1 Reprendre le tableau de 1.1 : les cinq représentations servent la **même** adresse
  pour le même morceau, avec le domaine configuré et le nom de fichier encodé.
- [x] 3.2 Vérifier spécifiquement le morceau au nom de fichier espacé : plus aucun espace
  brut nulle part.
- [x] 3.3 Compléter le test `representationsAlternativesTest.php` : l'assertion sur
  l'adresse du fichier, aujourd'hui retirée avec la mention de l'écart, est rétablie — et
  la mention avec elle.
- [x] 3.4 Ajouter une assertion couvrant les quatre nouveaux scénarios, dont celle qui
  compare l'adresse d'une représentation à l'autre.
- [x] 3.5 Voir chaque nouvelle assertion échouer avant de l'accepter : rétablir
  temporairement l'ancienne construction, constater l'échec nommé, corriger à nouveau.
- [x] 3.6 `docker-compose exec php php symfony test:all` — la suite passe. Relever avant et
  après.
- [x] 3.7 Vérifier que le **flux de syndication** et **oEmbed** servent toujours la même
  adresse qu'avant : ils passaient déjà par le modèle et ne doivent pas bouger.
- [x] 3.8 Vérifier que la page d'un morceau sert toujours la même adresse au lecteur audio
  et au lien de téléchargement — ils appellent déjà `getTrackUrl()`.
- [x] 3.9 `find src -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;`.
- [x] 3.10 `openspec validate unifier-l-adresse-du-fichier-audio --type change --strict`.

### Mesures relevées le 2026-08-18

Morceau `sigur-ros-rock-roll`, fichier `un titre.mp3`, `app_urls_tracks = //localhost:8080/tracks`
alors que l'hôte de la requête est `localhost` — les deux diffèrent, sans quoi la correction
ne se démontrerait pas.

| représentation | avant | après |
| --- | --- | --- |
| JSON `track.href` | `http://localhost:8080/tracks/un%20titre.mp3` | inchangé |
| `max` liste | `http://localhost:8080/…` | inchangé |
| **`max` isolé** | `http://localhost/tracks/un titre.mp3` | `http://localhost:8080/tracks/un%20titre.mp3` |
| **XSPF liste** | `http://localhost/…` | `http://localhost:8080/…` |
| **XSPF isolé** | `http://localhost/…` | `http://localhost:8080/…` |

Les cinq représentations servent désormais la même adresse. L'espace brut a disparu.

Surfaces vérifiées inchangées : pièce jointe du flux, `window.trackUrl` du lecteur et lien
de téléchargement — toutes trois appelaient déjà `getTrackUrl()`.

**Faillibilité** : les six assertions ajoutées ont été vues échouer. Ancien code rétabli
temporairement, la suite en signale six ; corrigé, les 36 passent.

Suite : **523 → 559 tests**, 17 → 18 fichiers.

### Ce que `$baseUrl` est devenu

Le partiel XSPF ne s'en servait que pour l'adresse du fichier. Il reçoit désormais
`$trackScheme` — le partiel étant hermétique, il ne peut pas savoir seul si la requête est
sécurisée, et c'est la seule chose qu'il lui manque une fois l'adresse construite par le
modèle.
