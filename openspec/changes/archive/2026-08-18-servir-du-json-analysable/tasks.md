# Tâches

Pas de `design.md` : un appel retiré dans deux gabarits, une lecture au brut, deux
garde-fous de test. Ni schéma, ni module, ni dépendance. La règle du dépôt réserve cet
artefact aux changements structurants.

## 1. Mesurer avant de corriger

- [x] 1.1 Constater le défaut sur la production : `/posts?format=json` répond 200, sert
  8,4 Mo, et `json.load` échoue sur `Invalid \escape: line 1 column 3705351`.
- [x] 1.2 Dénombrer les occurrences plutôt que les supposer : **deux** sur 8 098 morceaux.
  `hyacinthe-retour-d-emeute-piege` (`\ Chaque époque a son petit diable //`) et le morceau
  au dessin ASCII qui porte `\<`.
- [x] 1.3 Établir la chaîne complète, sans l'inférer : `&#92;` produit par le rendu Markdown,
  laissé tel quel par `json_encode()`, ramené à un `\` nu par le `html_entity_decode()` des
  gabarits. Reproduit hors du site, en trois lignes de PHP, sur la séquence exacte.
- [x] 1.4 Identifier ce que ce décodage compensait réellement : **l'échappement de vue**, et
  non le rendu Markdown. `showSuccess.json.php` recevait la chaîne JSON déjà encodée avec
  `&quot;` partout ; `listSuccess.json.php`, qui lit déjà `posts` au brut, n'avait rien à
  compenser.
- [x] 1.5 Reproduire le défaut en local sur le corpus de production complet : mêmes 8 098
  morceaux, même `Invalid \escape`.

## 2. Corriger

- [x] 2.1 `showSuccess.json.php` : lire `$sf_data->getRaw('post')` et servir le document tel
  quel. Le `html_entity_decode()` disparaît avec sa cause.
- [x] 2.2 `listSuccess.json.php` : retirer le `html_entity_decode()`, qui n'y compensait rien.
- [x] 2.3 `executeMd5` : mettre à jour le commentaire qui décrivait l'écart entre cette route
  et les deux gabarits. L'écart n'existe plus.

## 3. Garde-fous

- [x] 3.1 `test/functional/frontend/jsonEchappementTest.php` : poser un morceau dont le corps
  porte un antislash, une esperluette et un guillemet, et exiger des trois routes JSON un
  document analysable. Le morceau est inséré par le test lui-même, et non versé dans
  `data/fixtures/`, pour la raison qu'`unicodeTest.php` documente déjà — un morceau de plus
  dans le fichier partagé fait basculer les suites qui comptent des morceaux.
- [x] 3.2 Insertion par paramètres liés, et non par concaténation : `quote()` ajoute un
  niveau d'échappement que MySQL retire, et les antislashs n'arrivaient pas en base tels
  qu'ils sont écrits.
- [x] 3.3 Vérifier que le garde-fou mord : `git stash` du correctif, la suite échoue sur
  `GET /posts?format=json : le document est du JSON analysable (Syntax error)`.
- [x] 3.4 `openapiContractTest.php` : exiger que toute réponse annoncée JSON s'analyse, avant
  de regarder ses clés. Le contrat déclarait conforme un document que `json_decode()`
  rendait `null`.
- [x] 3.5 Suite complète verte : `Files=22, Tests=645`.

## 4. Vérification manuelle

Elle porte sur ce que le correctif change pour un consommateur, et elle a été menée en local
sur **le corpus de production** (la base de développement porte le dump, 8 216 morceaux dont
8 098 en ligne). L'environnement de dev de ce dépôt écoute sur `http://localhost:8001` ;
substituer le port si un autre a été retenu.

Vider le cache de gabarits avant chaque mesure — sans quoi la réponse précédente est
resservie et la mesure ne dit rien :

```
docker-compose exec php rm -rf /usr/local/src/cache/frontend/prod/template
```

- [x] 4.1 `curl -s "http://localhost:8001/posts?format=json" | python3 -c "import json,sys; json.load(sys.stdin)"`
  ne lève rien. Avant le correctif, sur le même corpus : `Invalid \escape: line 1 column 3475416`.
- [x] 4.2 `GET /post/hyacinthe-retour-d-emeute-piege?format=json` s'analyse, et son
  `body.html` vaut `<p>&#92; Chaque époque a son petit diable //</p>` — l'antislash y figure
  échappé en entité, ce qu'un navigateur restitue par `\`.
- [x] 4.3 `GET /post/md5/9fe6d55dac9bf4a0af1870e1ccf9026b` sert le même morceau sous la même
  enveloppe, et s'analyse.
- [x] 4.4 `GET /posts?c=<contributeur>&format=json` s'analyse aussi : la liste filtrée passe
  par le même gabarit.

Après mise en ligne (PR #187 fusionnée le 18 août 2026) :

- [x] 4.5 `curl -s "https://www.musiqueapproximative.net/posts?format=json" | python3 -c "import json,sys; json.load(sys.stdin)"`
  ne lève rien : 8 098 morceaux, 8,4 Mo. Six sondes, six documents analysables. Le corps
  d'`hyacinthe-retour-d-emeute-piege` vaut `<p>&#92; Chaque époque a son petit diable //</p>`.
- [x] 4.6 `GET /post/hyacinthe-retour-d-emeute-piege?format=json` en production : douze
  sondes analysables sur douze.

**Défaut d'environnement observé au passage, hors périmètre et non résolu** : une première
sonde sur la route du morceau a échoué (`Expecting property name enclosed in double quotes:
line 2 column 3`) avant que douze appels consécutifs passent. C'est la signature déjà
consignée — PHP-Markdown émet des `E_DEPRECATED` sur le premier rendu Markdown d'un
processus (`src/lib/vendor/PHP-Markdown/markdown.php:910`, syntaxe `{0}` dépréciée depuis
PHP 7.4), et ils atterrissent dans le corps de la réponse. C'est ce qui oblige chaque test
JSON à une requête de chauffe. Indépendant de ce change, mais réel : il rend invalide une
réponse sur N, selon le recyclage des processus PHP-FPM. Mérite son propre change.

## 5. Documentation

- [x] 5.1 `src/web/openapi.yaml` : décrire ce que portent `body.markdown` et `body.html`.
  Le contrat déclarait `html: { type: string }` sans dire ce que la chaîne contient, ce qui
  laissait sans réponse la question même que ce change tranche. Contrat revérifié après
  l'ajout : `openapiContractTest` reste vert.
- [x] 5.2 Aucune page d'`docs/` ne décrit la représentation JSON : le contrat est le seul
  document concerné, et il est à jour.
