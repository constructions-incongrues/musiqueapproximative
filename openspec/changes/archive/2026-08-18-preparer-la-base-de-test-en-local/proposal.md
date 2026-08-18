## Why

Faire tourner la suite de tests sur un poste neuf demande trois gestes que rien ne
documente et que rien n'automatise. Tant qu'ils manquent, trois scripts échouent sur un
garde-fou du bootstrap :

> « Refus de charger les fixtures : la connexion pointe sur « musiqueapproximative », qui
> n'est pas une base de test. Les fixtures commencent par des TRUNCATE. »

Le garde-fou a raison. Ce qui manque est en amont.

**Une variable absente du modèle.** `start-dev.sh` écrit le `.env` d'un poste neuf avec
quatre variables. `src/config/databases.yml-dist` en attend une cinquième,
`DATABASE_NAME_TEST`, pour sa section `test:`. Sans elle, `envsubst` laisse le littéral :

```yaml
dsn:      mysql:host=db;dbname=${DATABASE_NAME_TEST}
```

Les trois profils versionnés sous `etc/` portent bien la variable — seul le générateur est
désynchronisé, donc seul un clone neuf est touché. C'est la pire des situations : le défaut
ne se voit pas chez ceux qui pourraient le corriger.

**Une base qui n'existe pas.** Rien ne crée `musiqueapproximative_test`, et rien n'y
construit le schéma.

Le coût de cette lacune s'est mesuré aujourd'hui : trois scripts de test tenus pour cassés
pendant des heures, alors qu'ils attendaient seulement une base.

## What Changes

- `start-dev.sh` écrit `DATABASE_NAME_TEST` dans le `.env` qu'il génère.
- Une cible `make test-init` crée la base de test si elle manque et y construit le schéma.
  Idempotente : la relancer ne casse rien.
- `docs/developpement/environnement.adoc` décrit la mise en route de la base de test, et
  `docs/developpement/tests.adoc` y renvoie depuis l'endroit où l'on cherche à lancer les
  tests.

## Hors périmètre

- **Le chargement des fixtures.** `test/bootstrap/database.php` s'en charge déjà à chaque
  fichier de test. `make test-init` ne fait que préparer la structure.
- **La dépendance `getID3`**, absente de l'environnement, qui fait échouer
  `unit/model/PostMetadataTest`. C'est un autre manque, d'une autre nature.
- **Les profils de déploiement.** Leurs `.env` portent déjà la variable ; ils ne sont pas
  touchés.
- **La CI.** Elle crée déjà sa base et construit son schéma ; ce changement ne fait que
  rapprocher le poste local de ce qu'elle fait.

## Capabilities

### New Capabilities

Aucune.

### Modified Capabilities

Aucune. Rien de ce que servent les routes publiques ne change.

## Impact

- **Modifié** : `start-dev.sh`, `Makefile`, `docs/modules/ROOT/pages/developpement/environnement.adoc`,
  `docs/modules/ROOT/pages/developpement/tests.adoc`.
- **Contrat public** : pas concerné.
- **Effet attendu** : sur un poste dont la base de test manque, `make test-init` puis
  `php symfony test:all` doit passer de trois scripts en échec à zéro — hors `getID3`,
  laissé hors périmètre.
