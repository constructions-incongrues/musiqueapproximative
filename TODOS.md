# TODOS

## Frontend

### L'API JSON sert aujourd'hui du JSON invalide

**What:** Supprimer l'appel à `html_entity_decode()` dans `listSuccess.json.php` (et le même motif dans `showSuccess.json.php` s'il y figure).

**Why:** `/posts?format=json` ne se parse pas. Vérifié sur la base de production : `json.loads()` échoue sur `Invalid \escape`. Tout consommateur de l'API qui valide sa réponse est cassé, aujourd'hui, silencieusement.

**Context:** `src/apps/frontend/modules/post/templates/listSuccess.json.php` applique `html_entity_decode()` au résultat de `$post->toJson()`, c'est-à-dire à un document **déjà sérialisé**. Toute entité HTML présente dans le corps rendu et se décodant en `\` ou en `"` atterrit alors brute à l'intérieur d'une chaîne JSON. Mécanisme reproduit isolément :

```php
$json = json_encode(['html' => 'Chaque &#92; epoque']);
// {"html":"Chaque &#92; epoque"}  -- valide
html_entity_decode($json);
// {"html":"Chaque \ epoque"}      -- json_decode() renvoie null
```

Cas réel : le post `hyacinthe-retour-d-emeute-piege`. C'est la même famille d'erreur que le double-encodage de `executeOembed` — post-traiter une sortie déjà encodée — et exactement ce que le sérialiseur `SubsonicResponse` de la branche Subsonic est conçu pour rendre impossible. Le `html_entity_decode` visait probablement à défaire l'échappement de sortie de Symfony 1 (`escaping_strategy: true`) ; la bonne correction est de récupérer les valeurs brutes via `$sf_data->getRaw()` en amont de `toJson()`, pas de nettoyer le JSON en aval.

**Effort:** S
**Priority:** P1
**Depends on:** None

### `/post/md5/<hash>` sert les posts programmés

**What:** Ajouter le filtre `publish_on` à `PostTable::getByMd5sum()`, et consolider les six méthodes historiques sur la constante `WHERE_ONLINE`.

**Why:** `getByMd5sum()` ne filtre que sur `is_online`. Un post daté dans le futur est donc servi par `/post/md5/<hash>` avant sa date de publication, avec tout son contenu. Portée réelle limitée — il faut connaître le md5 du fichier, qui n'est exposé par l'API que pour les posts déjà publiés — mais c'est bien une fuite, et elle contredit la règle appliquée partout ailleurs.

**Context:** `src/lib/model/doctrine/PostTable.class.php`. La branche Subsonic a introduit `WHERE_ONLINE`, la règle de visibilité complète (`is_online`, `publish_on <= now()+2h`, slug non vide) en un seul endroit, et y a routé toutes ses nouvelles requêtes. Les six méthodes antérieures — `getLastPost`, `getOnlinePostBySlug`, `getOnlinePostById`, `getNextPost`, `getPreviousPost`, `getRandomPost` — portent encore chacune leur copie du prédicat, et `getByMd5sum` une version incomplète. Les consolider est mécanique mais change le comportement de pages en production : `getByMd5sum` se mettrait à filtrer sur la date, et `getPreviousPost` gagnerait le filtre sur le slug. À faire d'un bloc, avec les tests de la base de test désormais disponible (`src/test/bootstrap/database.php`).

**Effort:** M
**Priority:** P2
**Depends on:** La branche Subsonic (constante `WHERE_ONLINE` et base de test)

### Corriger le double-encodage XML de `executeOembed`

**What:** Supprimer l'appel à `htmlentities()` avant `addChild()` dans `executeOembed`.

**Why:** `SimpleXMLElement::addChild()` échappe déjà sa valeur. Le pré-échappement produit `&amp;amp;` — la sortie oEmbed XML consommée par les sites qui embarquent des posts est double-encodée aujourd'hui.

**Context:** `src/apps/frontend/modules/post/actions/actions.class.php:262`, boucle `foreach ($data as $key => $value) { $xml->addChild($key, htmlentities($value)); }`. Même famille de bug que celui traité dans le sérialiseur Subsonic (`docs/superpowers/specs/2026-08-07-subsonic-api-support-design.md`, section Sérialisation), sur un endpoint que la branche Subsonic ne touche pas. Le correctif est de passer `$value` brut. Vérifier avec un titre contenant `&`.

**Effort:** S
**Priority:** P2
**Depends on:** None

### Réparer la génération d'avatars dans `Post::postSave`

**What:** Réactiver la génération d'avatars et corriger la parenthèse mal placée du test d'existence.

**Why:** Les avatars n'existent pour ainsi dire pour aucun post. L'API Subsonic exposera donc le logo du thème comme pochette de tous les albums, ce qui donne une grille d'albums entièrement identique dans les clients.

**Context:** `src/lib/model/doctrine/Post.class.php`, méthode `postSave`. Deux problèmes : `$process->run()` est commenté, et le garde-fou au-dessus s'écrit `file_exists(sprintf('%s/avatars/%s.png'), $webDir, $postId)` — les arguments partent dans `file_exists` au lieu de `sprintf`, donc le test ne teste rien. Prérequis à établir avant de commencer : l'outil `bndrimg` est-il encore installé sur l'hôte de production, et `convert` (ImageMagick) l'est-il aussi ? Si non, la tâche devient « choisir un autre générateur », pas « décommenter une ligne ».

**Effort:** M
**Priority:** P2
**Depends on:** Confirmer la présence de `bndrimg` et d'ImageMagick sur l'hôte de production

### Supprimer ou réécrire `postActionsTest.php`

**What:** Le stub de test fonctionnel généré interroge une route inexistante.

**Why:** C'est le seul test fonctionnel du dépôt et il ne teste rien. Une suite qui paraît peuplée alors qu'elle est vide est pire qu'une suite vide : elle décourage d'en écrire.

**Context:** `src/test/functional/frontend/postActionsTest.php` fait `get('/post/index')` et vérifie `module=post, action=index`. Aucune route `post/index` n'existe dans `src/apps/frontend/config/routing.yml`. Soit le supprimer, soit le réécrire contre `@post_show` ou `@post_list`. La branche Subsonic ajoute une vraie infrastructure de test (connexion `test:` et fixtures Doctrine YAML) qui rend la réécriture peu coûteuse.

**Effort:** S
**Priority:** P3
**Depends on:** None

### Porter le correctif N+1 de `search3` vers la recherche du site

**What:** Remplacer la boucle « une requête par résultat » de `PostTable::search()` par un unique `WHERE id IN (…)`.

**Why:** `/posts?q=` déclenche aujourd'hui une requête par résultat, sans borne. La branche Subsonic contourne le problème pour son propre `search3` mais laisse la recherche du site intacte.

**Context:** `src/lib/model/doctrine/PostTable.class.php:158-172`. `parent::search()` renvoie des identifiants classés, puis chaque identifiant déclenche un `getOnlinePostById()`. Le patron de remplacement est écrit et éprouvé dans le code Subsonic : collecter les identifiants, découper à la fenêtre demandée, charger en une requête via `buildOnlinePostsQuery()`. À faire tant que c'est frais.

**Effort:** S
**Priority:** P2
**Depends on:** La branche Subsonic (`buildOnlinePostsQuery()` publique et paramétrable)

## Infrastructure

### L'image ghcr publiée n'a pas de dépendances

**What:** Ajouter `RUN composer install --no-dev` au `Dockerfile`.

**Why:** L'image publiée à chaque tag ne peut pas faire tourner l'application : elle n'embarque aucun vendor. L'image n'est donc pas un chemin de déploiement viable, ce que rien n'indique.

**Context:** Le `Dockerfile` copie `./src` puis s'arrête ; `composer install` n'est lancé que par la commande de démarrage de `docker-compose`, jamais à la construction. `/src/vendor` est gitignoré, donc le `COPY` n'emporte rien. Le déploiement réel passe par `make deploy` (rsync), ce qui masque le problème. Antérieur à la branche Subsonic et sans rapport avec elle. Attention à l'ordre : `composer install` doit venir après le `COPY` des sources et avant le passage à l'utilisateur non privilégié.

**Effort:** S
**Priority:** P2
**Depends on:** None

### `getRandomPost` trie la table entière

**What:** Remplacer `orderBy('rand()')` par un tirage borné.

**Why:** MySQL matérialise et trie toutes les lignes pour un `ORDER BY RAND()`. Sans effet perceptible à quelques milliers de lignes, mais la table ne fait que croître, et `getAlbumList2?type=random` de l'API Subsonic hérite du même chemin.

**Context:** `src/lib/model/doctrine/PostTable.class.php:188`. Utilisé par `/posts/random` et, après la branche Subsonic, par `getAlbumList2?type=random` et `getRandomSongs`. Approche habituelle : tirer un décalage aléatoire sur le nombre de lignes en ligne, puis `LIMIT 1 OFFSET n`. Honnêtement : ce n'est pas lent aujourd'hui, c'est noté pour ne pas le redécouvrir.

**Effort:** S
**Priority:** P4
**Depends on:** None

### Une session est ouverte à chaque requête, y compris sur `/rest`

**What:** Envisager une application `api` dédiée (sans `sfDesastreFilter` ni session) si le volume de fichiers de session devient un problème.

**Why:** `sfDesastreFilter` lit `$this->context->getUser()->getAttribute(...)` avant même de vérifier le format de la réponse, ce qui instancie `sfUser` et démarre une session sur _toute_ requête — y compris `/rest/ping`, appelé en boucle par les clients Subsonic (polling, `getNowPlaying`, etc.). Vérifié : `/posts?format=json` et `/rest/ping` posent tous les deux un `Set-Cookie: symfony=...`. Ce n'est pas une régression de la branche Subsonic — le comportement est identique sur `/posts` — mais le nouveau module `rest` en hérite et en amplifie l'usage (clients qui interrogent le serveur bien plus souvent qu'un navigateur).

**Context:** Symfony 1 n'a pas de bascule de session par module. Le correctif propre serait une application `frontend`-bis dédiée à `/rest`, sans `sfDesastreFilter` dans `filters.yml` et avec `use_database`/session désactivés si possible. Non prioritaire tant que le volume de fichiers de session reste gérable.

**Effort:** M
**Priority:** P4
**Depends on:** None
