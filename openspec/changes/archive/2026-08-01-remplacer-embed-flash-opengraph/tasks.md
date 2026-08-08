## 1. Métadonnées du lecteur embarquable

- [x] 1.1 Dans `executeShow()` (`src/apps/frontend/modules/post/actions/actions.class.php`), construire l'URL absolue d'embed du morceau (`/post/:slug?embed`) à partir de la route `@post_show`, en HTTPS
- [x] 1.2 Remplacer les valeurs `og:video` et `og:video:secure_url` par cette URL d'embed, en supprimant les deux `sprintf` construisant l'URL `player.swf`
- [x] 1.3 Passer `og:video:type` à `text/html`
- [x] 1.4 Aligner `og:video:width` sur `510` et `og:video:height` sur `220`, valeurs déjà servies par `executeOembed()`

## 2. Accès direct au fichier audio

- [x] 2.1 Ajouter `og:audio` et `og:audio:secure_url` pointant vers l'URL absolue HTTPS du fichier audio (`app_urls_tracks` + `track_filename`, encodée)
- [x] 2.2 Ajouter `og:audio:type` avec le type MIME du fichier servi

## 3. Typage du contenu

- [x] 3.1 Passer `og:type` de `video` à `music.song`

## 4. Vérification manuelle

> Exécutée par le mainteneur, sur un environnement dont l'agent ne disposait pas. Aucun
> écart constaté : le comportement observé correspond à ce que les specs décrivent.
>
> Le code avait été fusionné dans `main` par la PR #89 avant que ces contrôles aient lieu.
> Ils sont désormais faits, et la carte de partage a été vue telle que la restituent les
> plateformes.
>
> Vérifications statiques déjà effectuées auparavant : `php -l` passe, et plus aucune
> occurrence de `.swf` ni de `application/x-shockwave-flash` ne subsiste dans le module
> `post`.

- [x] 4.1 Démarrer l'environnement (`./start-dev.sh`) et vider le cache Symfony
- [x] 4.2 Sur une page de morceau, vérifier dans le HTML servi qu'aucune métadonnée ne contient `.swf` ni `application/x-shockwave-flash`
- [x] 4.3 Vérifier que l'URL déclarée par `og:video` répond bien et affiche le lecteur HTML5 (comparer avec le champ `html` de `/oembed?url=...`)
- [x] 4.4 Vérifier que l'URL déclarée par `og:audio` sert bien le fichier audio
- [x] 4.5 Contrôler le rendu de la carte de partage avec un validateur OpenGraph externe, ou à défaut en partageant l'URL de production après déploiement
- [x] 4.6 Vérifier que le titre, la description et l'illustration (glitchée ou non) sont inchangés par rapport à avant le changement
