## 1. Réglage

- [x] 1.1 Passer `cache: false` à `cache: true` dans le bloc `test:` de
      `src/apps/frontend/config/settings.yml`

## 2. Test de non-régression

- [x] 2.1 Créer `src/test/functional/frontend/desastreCacheTest.php`, sur le modèle de
      `postActionsTest.php` — bootstrap fonctionnel, puis `bootstrap/database.php`
- [x] 2.2 Demander une adresse en forçant une règle de désastre par son déclencheur, et
      vérifier que la réponse porte les ressources du désastre **et** son bloc
      `window.DesastreOptions`
- [x] 2.3 Demander la même adresse une seconde fois et vérifier que la réponse porte
      toujours les options. C'est le bug de `2026-08-07-reparer-injection-des-options`,
      qui ne se voyait qu'à partir du deuxième visiteur
- [x] 2.4 Vérifier que la seconde réponse vient bien du cache, faute de quoi le test ne
      démontre rien : constater la présence de l'entrée sous
      `cache/frontend/test/template/`

## 3. Documentation

- [x] 3.1 Dire dans `docs/modules/ROOT/pages/developpement/tests.adoc` que l'environnement
      de test met en cache, que le bootstrap vide le cache à chaque fichier, et que deux
      demandes de la même adresse dans un même fichier renvoient la même réponse

## 4. Vérification manuelle

- [x] 4.1 Lancer `docker compose run --rm --no-deps php php symfony test:all` et constater
      14 scripts, le seul échec attendu étant `getID3`
- [x] 4.2 Contrôle de mutation : remettre `cache: false`, relancer le nouveau test, et
      vérifier qu'il échoue à l'assertion de la tâche 2.4. Rétablir
- [x] 4.3 Vérifier qu'aucun fichier applicatif hors `settings.yml` n'a été modifié
- [x] 4.4 Si une vérification n'a pas pu être menée, laisser sa case décochée et le dire en
      clair ici. Une case cochée signifie vérifiée

## Résultats

- Suite complète : **15 scripts, 449 assertions**, contre 14 et 441 avant ce changement.
  Le seul échec, `unit/model/PostMetadataTest` sur `Class 'getID3' not found`, est
  préexistant et étranger à ce sujet : c'est une dépendance absente de l'environnement.
- `functional/frontend/desastreCacheTest` : 8 assertions, toutes vertes.
- Contrôle de mutation : remettre `cache: false` fait échouer deux assertions —
  « sf_cache est vrai » et « la réponse a bien été mise en cache ». Le test ne peut donc
  pas passer par accident si le réglage disparaît.
- Aucun fichier applicatif modifié hors `settings.yml`, vérifié par `git status`.

## Ce que l'implémentation a appris

**Le levier n'est pas là où on le croit.** La première intention était de forcer
`sfConfig::set('sf_cache', true)` dans le bootstrap fonctionnel, sans toucher à
`settings.yml`. Une sonde a d'abord semblé confirmer que cela marchait : le gestionnaire de
cache de vue était bien créé. C'était trompeur — il existait, mais ne cachait rien.
`sfBrowser::getContext()` reconstruit la configuration depuis `settings.yml` à chaque
requête, puis prend son instantané : la valeur forcée est écrasée avant d'être capturée.
Vérifier l'existence d'un objet ne vaut pas vérifier son effet.

**Un environnement dédié coûte trois duplications.** `test_cache` fonctionne, jusqu'à
l'écriture du fichier de cache, mais réclame un bloc dans `settings.yml`, un dans
`factories.yml` pour `sfSessionTestStorage`, et un dans `databases.yml-dist` sans quoi le
garde-fou des fixtures refuse la connexion. `factories.yml` n'ayant pas d'héritage entre
environnements, ces blocs divergeront.

## Note d'environnement, hors périmètre

Faire tourner les tests fonctionnels en local demandait deux gestes que rien ne
documentait : régénérer `src/config/databases.yml` depuis son `-dist`, qui porte une
section `test:` absente du fichier généré s'il date, et créer la base
`musiqueapproximative_test` puis y construire le schéma par
`php symfony doctrine:insert-sql --env=test`. Sans cela, le garde-fou des fixtures refuse
de démarrer — à juste titre, puisqu'elles commencent par des `TRUNCATE`. Trois scripts
échouaient pour cette seule raison. Documenter cette mise en route mériterait son propre
changement.
