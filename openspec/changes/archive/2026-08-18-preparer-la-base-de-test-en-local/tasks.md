## 1. Variable manquante

- [x] 1.1 Ajouter `DATABASE_NAME_TEST=musiqueapproximative_test` au `.env` que
      `start-dev.sh` génère, aux côtés des quatre variables existantes

## 2. Préparation de la base

- [x] 2.1 Ajouter une cible `test-init` au `Makefile` racine, avec sa ligne d'aide `##`
      comme les autres, qui crée `musiqueapproximative_test` si elle manque
- [x] 2.2 La même cible construit le schéma par `php symfony doctrine:insert-sql --env=test`
- [x] 2.3 Vérifier qu'elle est rejouable : deux exécutions de suite doivent aboutir

## 3. Documentation

- [x] 3.1 Décrire la mise en route de la base de test dans
      `docs/modules/ROOT/pages/developpement/environnement.adoc`, en disant ce que le
      garde-fou refuse et pourquoi
- [x] 3.2 Renvoyer vers cette section depuis
      `docs/modules/ROOT/pages/developpement/tests.adoc`, à l'endroit où l'on cherche à
      lancer les tests

## 4. Vérification manuelle

- [x] 4.1 Simuler un poste neuf : supprimer la base de test, lancer `make test-init`, puis
      `php symfony test:all`, et constater qu'aucun script n'échoue sur le garde-fou des
      fixtures
- [x] 4.2 Relancer `make test-init` une seconde fois et constater qu'elle aboutit sans
      erreur
- [x] 4.3 Vérifier que le `.env` généré par `start-dev.sh` produit un `databases.yml` dont
      la section `test:` porte un vrai nom de base, et non le littéral
      `${DATABASE_NAME_TEST}`
- [x] 4.4 Si une vérification n'a pas pu être menée, laisser sa case décochée et le dire en
      clair ici. Une case cochée signifie vérifiée

## Ce que l'implémentation a trouvé

**Le `Makefile` vise la production par défaut.** Il fait `include ./etc/$(PROFILE)/.env`
avec `PROFILE := www.musiqueapproximative.net`. Une première version de la cible utilisait
`$(DATABASE_USER)` sans y penser : elle a tenté de se connecter avec l'utilisateur de
production, que MySQL a tronqué à seize caractères — `musiqueapproxima`. Le symptôme était
opaque, la cause structurelle.

La cible refuse désormais de s'exécuter si `DATABASE_HOST` n'est pas le conteneur local, et
nomme le profil fautif :

```
test-init vise l'environnement Docker local, or PROFILE=www.musiqueapproximative.net pointe sur 127.0.0.1.
Relancer avec : make test-init PROFILE=musiqueapproximative.localhost
```

Ce garde-fou n'était pas prévu au design. Il l'aurait dû : le piège attend quiconque
ajoute une cible touchant à une base.

## Résultats

- Base de test supprimée, puis `make test-init PROFILE=musiqueapproximative.localhost` :
  la suite passe à **16 fichiers, 461 tests, aucun échec**.
- La cible rejouée une seconde fois aboutit sans erreur.
- Le `.env` que `start-dev.sh` génère produit désormais
  `dsn: mysql:host=db;dbname=musiqueapproximative_test`, là où il laissait le littéral.
- Le garde-fou de profil mord avec le profil par défaut et laisse passer avec le bon.

## Deux affirmations fausses, corrigées

**`getID3` n'était pas une dépendance absente.** Trois fois au cours de ce travail,
`unit/model/PostMetadataTest` a été déclaré en échec « préexistant et étranger au sujet »,
sur `Class 'getID3' not found`. C'était faux. La cause était le cache d'autochargement
`src/cache/project_autoload.cache`, généré hors conteneur et portant donc des chemins de
l'hôte — `/Users/…` — introuvables sous `/usr/local/src`. Une fois ce cache supprimé et
régénéré, le test passe, par `docker compose exec` comme par `run --rm`.

La suite ne comptait donc aucun échec réel. Elle en paraissait avoir trois, puis un, pour
deux raisons d'environnement qui n'avaient rien à voir avec le code : une base de test
absente, et un cache périmé.

**Le port 8001 était tenu par un conteneur de worktree abandonné.**
`git-pull-origin-main-7e2e0d-php-1`, debout depuis dix jours, empêchait le conteneur `php`
du projet de démarrer. L'auteur l'a arrêté, ce qui a permis d'exécuter `make test-init` de
bout en bout et de cocher la tâche 4.2.

Ces deux obstacles suggèrent une hygiène qui manque : rien ne signale un cache
d'autochargement construit ailleurs, ni un conteneur de worktree resté debout. Hors
périmètre ici, mais réels.
