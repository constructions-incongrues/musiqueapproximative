## 1. Relever l'état réel des routes

- [x] 1.1 Pour chacune des neuf routes du module `post` (`/`, `/post/{slug}`,
  `/post/md5/{md5sum}`, `/posts`, `/posts/feed`, `/posts/next`, `/posts/prev`,
  `/posts/random`, `/oembed`), relever le code de statut et le `Content-Type` servis, à
  vide et pour chaque valeur de `format` qu'elles acceptent. Consigner le relevé — c'est la
  matière du contrat, et il doit venir de la machine, pas du code lu.
- [x] 1.2 Relever les paramètres de requête réellement pris en compte : `format`, `q`, `c`,
  `play`, et l'`url` d'`/oembed`. Noter lesquels sont obligatoires.
- [x] 1.3 Confronter le relevé à `docs/API_CURRENT_STATE.md` et noter les points où le
  document existant se trompe. Ce sont eux qui justifient de le retirer.

## 2. Écrire le contrat

- [x] 2.1 Créer `src/web/openapi.yaml-dist` en OpenAPI 3.1, avec `info` (titre, version,
  licence) et `servers` portant `https://${APP_DOMAIN}`.
- [x] 2.2 Y déclarer les neuf routes relevées en 1.1, avec leurs paramètres, leurs codes de
  statut et leurs types de contenu — **tels qu'ils sont servis**, y compris
  `application/vnd.api+json` sur `/posts?format=json`, et sans inventer de paramètre de
  bornage.
- [x] 2.3 Décrire les schémas de réponse (morceau, liste) à partir de
  `openspec/specs/formats-de-sortie/spec.md`, et porter sur **chacun** un `description:`
  disant qu'il n'est pas confronté au site — seules les clés de premier niveau le sont
  (tâche 3.4). La note va dans le document, pas dans ce fichier : personne ne lit les
  tâches d'un change archivé, et le lecteur du contrat n'a aucun autre moyen de savoir où
  passe la ligne entre le vérifié et le déclaré.
- [x] 2.4 Ajouter `/src/web/openapi.yaml` à `.gitignore`, à côté de `app.yml` et
  `databases.yml`.
- [x] 2.5 Lancer `make configure` sur le profil local et vérifier que
  `src/web/openapi.yaml` est produit avec le bon domaine.

## 3. Vérifier le contrat par un test

- [x] 3.1 Écrire `src/test/functional/frontend/openapiContractTest.php` : il lit
  `src/web/openapi.yaml` depuis le disque avec l'analyseur YAML du socle, et échoue avec un
  message explicite si le fichier est absent (`make configure` pas encore passé).
- [x] 3.2 Le test itère sur les `paths` du document : pour chacun, il substitue les
  paramètres d'URL par une valeur issue des fixtures, demande la route et compare le code
  de statut à celui déclaré.
- [x] 3.3 Comparer aussi le `Content-Type` servi au type déclaré, **sur le type de média
  seul** — le `charset` ne doit pas faire échouer la comparaison.
- [x] 3.4 Pour les routes servant du JSON, décoder le corps et vérifier la **présence des
  clés de premier niveau** que le schéma déclare. Pas de validateur, pas de dépendance :
  décoder et constater. C'est ce qui sort les schémas du régime « déclaré et jamais
  vérifié » — voir le décompte dans `design.md`.
- [x] 3.5 Chaque échec nomme la route et l'écart constaté, pas seulement « faux ».
- [x] 3.6 Ne pas exercer `/rest/*` : le test n'itère que sur ce que le contrat déclare, et
  le contrat ne déclare pas Subsonic.

## 4. Retirer ce que le contrat remplace

- [x] 4.1 Supprimer `docs/API_CURRENT_STATE.md` et retirer les renvois vers lui —
  `CLAUDE.md` le cite dans la section « JSON API conventions », vérifier les autres.
- [x] 4.2 Remplacer ces renvois par un pointeur vers `src/web/openapi.yaml-dist`.

## 5. Vérification

- [x] 5.1 `docker-compose exec php php symfony test:all` — la suite passe, y compris le
  nouveau fichier. **Avant : 16 fichiers, 461 tests. Après : 17 fichiers, 503 tests.**
  Les 42 ajoutés sont ceux du contrat : 18 demandes, deux assertions chacune sauf la
  redirection qui n'en porte qu'une, plus six vérifications de champs JSON.
- [x] 5.2 Vérifier que le test **échoue** quand il doit : ajouter temporairement au contrat
  un `path` inexistant (`/posts/inexistant`), relancer, constater l'échec nommé, retirer.
  Un test de contrat qui ne peut pas échouer ne protège de rien.
- [x] 5.3 Vérifier de même qu'un type de contenu faussé dans le contrat fait échouer le
  test, puis rétablir.
- [x] 5.4 Vérifier de même qu'une clé de premier niveau renommée dans un schéma fait
  échouer le test, puis rétablir.
- [x] 5.5 Relire le document en lecteur, pas en auteur : chaque schéma porte-t-il la
  mention qui dit ce qui n'est pas vérifié ? Un lecteur qui ne connaît pas ce change
  doit pouvoir distinguer les deux régimes sans sortir du fichier.
- [x] 5.6 `find src -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;` — aucune
  erreur de syntaxe.
- [x] 5.7 Demander `/openapi.yaml` : servi tel quel, `200`, 14 944 octets (relevé sur
  `http://localhost:8001`, le serveur PHP intégré ; nginx n'était pas démarré).
  **Réserve** : ce serveur ne pose aucun `Content-Type` sur `.yaml`. À vérifier sur le
  serveur de production, dont la table MIME est ailleurs. Le test ne couvre pas ce point,
  par construction.
- [x] 5.8 Vérifier que `src/web/openapi.yaml` n'apparaît pas dans `git status`.
- [x] 5.9 Vérifier qu'aucune route ne s'est mise à répondre différemment : les réponses de
  `/posts`, `/post/:slug` et `/posts/feed` sont identiques à celles d'avant le changement.
- [x] 5.10 `openspec validate publier-le-contrat-openapi --type change --strict`.
