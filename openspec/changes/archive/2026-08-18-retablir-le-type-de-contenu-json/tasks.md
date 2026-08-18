## 1. Relever l'état de départ

- [x] 1.1 Relever le type de contenu servi aujourd'hui sur les six routes JSON
  (`/post/{slug}?format=json`, `/post/md5/{md5sum}`, `/posts?format=json`, `/posts/next`,
  `/posts/prev`, `/posts/random`), plus `/oembed` et `/rest/ping`. C'est l'état contre
  lequel on comparera.
- [x] 1.2 Recenser **toutes** les assertions qui verrouillent `vnd.api+json`. Le packet de
  la story annonçait six sur deux fichiers ; le relevé en donne **dix sur trois** :
  huit dans `unit/filter/JsonApiFilterTest.php`, une dans
  `functional/frontend/postActionsTest.php`, une dans
  `functional/frontend/restActionsTest.php`. Vérifier ce compte avant de toucher quoi que
  ce soit.

## 2. Retirer le filtre

- [x] 2.1 Supprimer l'entrée `json_api` de `src/apps/frontend/config/filters.yml`, ainsi
  que le commentaire qui explique sa position sous `cache` — il n'a plus d'objet. **Ne pas
  toucher au commentaire de `desastre`**, qui documente la même contrainte pour un filtre
  qui reste.
- [x] 2.2 Supprimer `src/lib/filter/JsonApiFilter.class.php`.
- [x] 2.3 Vider le cache (`php symfony cache:clear`) et vérifier qu'aucune configuration
  compilée ne référence encore `JsonApiFilter`.

## 3. Amender le contrat

- [x] 3.1 Dans `src/web/openapi.yaml-dist`, remplacer les six déclarations
  `application/vnd.api+json` par `application/json`.
- [x] 3.2 Retirer de l'en-tête `info.description` la mention de l'écart sur le type de
  contenu : il n'existe plus. **Laisser** la mention de l'objet nu de `/post/md5/`, qui
  subsiste jusqu'à la story 4.
- [x] 3.3 Ajouter à `info.description` la **convention de version** : un incrément majeur
  de `info.version` signale une rupture des représentations décrites ici. Sans cette ligne,
  le numéro de version est du bruit — il suit `src/VERSION`, que release-please incrémente
  à chaque publication, y compris pour des travaux sans rapport avec l'API. Voir le tableau
  des modalités dans `design.md`.
- [x] 3.4 `make configure`, puis vérifier que le contrat rendu porte bien
  `application/json` et a conservé ses `$ref`.

## 4. Reporter ce que les tests protégeaient

- [x] 4.1 Supprimer `src/test/unit/filter/JsonApiFilterTest.php`.
- [x] 4.2 Ses trois `unlike` protégeaient trois comportements qui doivent rester vrais
  sans lui : `/oembed` ne sert pas `vnd.api+json`, le format HTML n'est pas réécrit, et
  une requête sans module attribuable ne l'est pas non plus. Les deux premiers sont
  désormais couverts par le contrat et son test. Vérifier que c'est bien le cas ; si l'un
  ne l'est pas, l'ajouter au test fonctionnel plutôt que de le laisser tomber.
- [x] 4.3 Dans `functional/frontend/postActionsTest.php`, corriger l'assertion
  « la liste JSON garde le type JSON:API » : elle doit désormais exiger `application/json`.
  Corriger aussi son libellé, qui affirme le contraire de ce qu'on veut.
- [x] 4.4 Dans `functional/frontend/restActionsTest.php`, l'assertion reste vraie et
  utile — le protocole conserve son type. Reformuler son seul libellé, qui nomme
  `JsonApiFilter`, une classe qui n'existe plus.

## 5. Démontrer que le type survit au cache

- [x] 5.1 Ajouter au test fonctionnel une vérification sur le modèle de
  `desastreCacheTest.php` : constater que `sf_cache` est vrai — sans quoi le test ne
  démontre rien —, demander une route JSON deux fois, et vérifier que les deux réponses
  portent `application/json`.
- [x] 5.2 Vérifier que la seconde réponse vient bien du cache, et non d'un second calcul :
  sans cela le test passe pour de mauvaises raisons. `desastreCacheTest.php` montre
  comment inspecter le répertoire de cache des gabarits.

## 6. Vérification

- [x] 6.1 `docker-compose exec php php symfony test:all` — la suite passe.
  **Avant : 17 fichiers, 503 tests. Après : 17 fichiers, 507 tests.** Le compte de fichiers
  ne bouge pas : `JsonApiFilterTest` (8 assertions) part,
  `jsonContentTypeCacheTest` (12 assertions) arrive. 503 − 8 + 12 = 507.
- [x] 6.2 Le test de contrat passe **sans modification de sa part** : c'est le contrat
  amendé qui le fait passer, pas un ajustement du test. Si le test a dû être touché, c'est
  que quelque chose d'autre a changé — l'expliquer.
- [x] 6.3 Vérifier que le test de contrat **échouerait** si le contrat n'avait pas été
  amendé : rétablir temporairement une déclaration `vnd.api+json` dans le contrat rendu,
  relancer, constater l'échec nommé, rétablir.
- [x] 6.4 Comparer avec le relevé de la tâche 1.1 : les six routes JSON servent maintenant
  `application/json`, `/oembed` sert toujours `application/json+oembed`, et `/rest/ping`
  sert toujours `text/xml`.
  **Écart constaté et accepté** : le paramètre `charset=utf-8` disparaît des six réponses
  JSON. Il venait de la chaîne codée en dur du filtre ; le socle ne l'ajoute qu'aux types
  `text/*`, `*xml` et `*javascript`. Conforme à la RFC 8259, qui ne définit aucun `charset`
  pour `application/json`, et à la spécification du projet. HTML et XSPF le conservent.
- [x] 6.5 Vérifier qu'aucun corps de réponse n'a changé : demander `/posts?format=json` et
  `/post/{slug}?format=json`, comparer les corps à ceux relevés en 1.1. Seul l'en-tête doit
  différer.
- [x] 6.6 `find src -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;` — aucune
  erreur de syntaxe.
- [x] 6.7 `openspec validate retablir-le-type-de-contenu-json --type change --strict`.

## 7. Livraison

- [x] 7.1 **Décision de l'auteur : pas de bascule majeure.** Le changement est livré sans
  footer `BREAKING CHANGE:` ; `src/VERSION` reste sur sa trajectoire ordinaire.
- [x] 7.2 **Conséquence appliquée au contrat.** La convention de version écrite en tâche 3.3
  annonçait qu'un incrément majeur signale une rupture. Cette décision la rend fausse dès sa
  première occasion : la ligne est donc remplacée par ce qui est vrai — `info.version` suit
  les publications du site et **ne dit rien** de cette API ; ce qui dit ce qui a changé est
  le diff de ce document. Un lecteur averti qu'il ne peut pas se fier au numéro est mieux
  servi qu'un lecteur à qui l'on promet un signal qu'on n'émet pas.
- [x] 7.3 **Ce que la ligne RECOURS du tableau devient.** Elle était vide ; elle le reste, et
  le seul signal envisagé n'est pas émis. Le canal se réduit au diff du contrat, qui demande
  au consommateur d'aller regarder de lui-même. C'est consigné dans le contrat, à l'endroit
  où il lit.
