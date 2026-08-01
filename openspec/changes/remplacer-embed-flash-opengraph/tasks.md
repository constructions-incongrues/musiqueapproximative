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

> Non exécutée : ces tâches demandent un environnement Docker fonctionnel. Le démon était
> indisponible lors de l'implémentation, et l'est toujours à la reprise après fusion de la
> PR #89 — le blocage n'est donc pas levé. Ces tâches restent ouvertes.
>
> Le code, lui, est déjà dans `main` : la fusion est intervenue sans que ces vérifications
> aient eu lieu. Elles gardent tout leur objet, et notamment le contrôle de la carte de
> partage réelle, qui ne peut de toute façon se faire qu'après déploiement.
>
> Vérifications statiques déjà effectuées : `php -l` passe, et plus aucune occurrence de
> `.swf` ni de `application/x-shockwave-flash` ne subsiste dans le module `post`.

- [ ] 4.1 Démarrer l'environnement (`./start-dev.sh`) et vider le cache Symfony
- [ ] 4.2 Sur une page de morceau, vérifier dans le HTML servi qu'aucune métadonnée ne contient `.swf` ni `application/x-shockwave-flash`
- [ ] 4.3 Vérifier que l'URL déclarée par `og:video` répond bien et affiche le lecteur HTML5 (comparer avec le champ `html` de `/oembed?url=...`)
- [ ] 4.4 Vérifier que l'URL déclarée par `og:audio` sert bien le fichier audio
- [ ] 4.5 Contrôler le rendu de la carte de partage avec un validateur OpenGraph externe, ou à défaut en partageant l'URL de production après déploiement
- [ ] 4.6 Vérifier que le titre, la description et l'illustration (glitchée ou non) sont inchangés par rapport à avant le changement
