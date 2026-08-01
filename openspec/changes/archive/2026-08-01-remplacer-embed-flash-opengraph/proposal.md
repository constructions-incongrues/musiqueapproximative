## Why

Les métadonnées OpenGraph des pages de morceau déclarent encore un lecteur Flash
(`player.swf`, `og:video:type: application/x-shockwave-flash`). Flash n'est exécuté
par aucun navigateur ni aucune plateforme sociale depuis janvier 2021 : tout partage
d'un post sur Mastodon, Facebook, Discord, Slack ou Bluesky produit une carte sans
lecteur, alors que le site dispose déjà d'un embed HTML5 fonctionnel servi par
`/post/:slug?embed` et exposé via `/oembed`.

Le correctif consiste à faire pointer les métadonnées vers ce qui existe déjà. Il n'y
a rien à construire, seulement une déclaration à remettre en cohérence.

## What Changes

- Les métadonnées `og:video` et `og:video:secure_url` cessent de pointer vers
  `player.swf` et pointent vers l'embed HTML5 existant (`/post/:slug?embed`).
- `og:video:type` passe de `application/x-shockwave-flash` à `text/html`.
- `og:video:height` / `og:video:width` passent de 476×476 aux dimensions réelles de
  l'embed (220×510), déjà déclarées par la réponse oEmbed.
- Ajout de `og:audio`, `og:audio:secure_url` et `og:audio:type` pointant directement
  sur le fichier MP3 : c'est la métadonnée que consomment les plateformes qui
  n'acceptent pas d'iframe tierce.
- `og:type` passe de `video` à `music.song`, plus juste pour le contenu servi et
  reconnu par le vocabulaire OpenGraph.
- **BREAKING** pour tout consommateur qui téléchargerait `player.swf` depuis les
  métadonnées OpenGraph. Aucun consommateur connu : le fichier n'est plus exécutable.

### Hors périmètre

- La carte `twitter:player` (aucune métadonnée `twitter:*` n'existe aujourd'hui sur
  le site) : c'est un ajout de fonctionnalité, pas une remise en cohérence.
- Le lien de découverte `<link rel="alternate" type="application/json+oembed">` dans
  le layout.
- Le lecteur jPlayer de la page de morceau et son `swfPath` résiduel : le lecteur
  fonctionne en HTML5, son nettoyage est un autre changement.
- Le fichier `player.swf` lui-même et le répertoire `src/web/swf/` : leur suppression
  demande de vérifier qu'aucune URL externe historique n'en dépend.
- L'apparence et les dimensions du gabarit d'embed (`showEmbed.php`), inchangées.

## Capacités

### Nouvelles capacités

- `metadonnees-partage`: les métadonnées OpenGraph exposées par une page de morceau
  pour permettre aux plateformes tierces de restituer un titre, une illustration et
  un lecteur audio jouable.

### Capacités modifiées

Aucune : le dépôt ne contient pas encore de specs principales (`openspec/specs/` est
vide, ce changement est le premier à en produire).

## Impact

- `src/apps/frontend/modules/post/actions/actions.class.php`, méthode `executeShow()`
  (lignes 71-92) : bloc de déclaration des métadonnées OpenGraph.
- Contrat public **touché** : métadonnées OpenGraph de `/post/:slug`. Les routes,
  les formats (`json`, `xspf`, `max`, `rss`, `oembed`) et le corps des réponses sont
  inchangés.
- L'URL d'embed (`/post/:slug?embed`) et la réponse `/oembed` sont consommées, jamais
  modifiées.
- Aucune dépendance ajoutée, aucune migration de base, aucun changement de
  configuration.
- Vérification manuelle nécessaire : aucun test automatisé ne couvre les métadonnées.
