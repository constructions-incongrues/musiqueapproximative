## Why

Le bloc `context:` de `openspec/config.yaml` est **injecté dans les instructions de chaque
artefact OpenSpec**. Une erreur qui y figure ne reste pas à sa place : elle se propage à
tout ce que l'outil produit, proposition après proposition.

La story visait une phrase — « Contrat public à préserver — aucun test automatisé ne le
couvre aujourd'hui ». Elle était fausse quand la story a été écrite, et elle l'est
davantage aujourd'hui : la suite compte **21 fichiers et 625 assertions**, dont six
fichiers fonctionnels qui exercent précisément le contrat public.

**Trois autres affirmations se sont révélées fausses à la vérification**, alors que le
packet de la story déclarait le reste du contexte « vérifié exact » :

|                                | affirmé            | mesuré                                  |
| ------------------------------ | ------------------ | --------------------------------------- |
| Doctrine                       | 1.3                | `lexpress/doctrine1 v1.4.6`             |
| moteur de base                 | MySQL              | MariaDB 10.11, en `utf8mb4`             |
| JSON                           | « jsonapi.org »    | non conforme à JSON:API 1.0, sciemment  |

La dernière est la plus coûteuse : elle laisse croire qu'une convention est tenue, alors
que la migration vers JSON:API a été **écartée délibérément**. Un artefact rédigé sous ce
contexte partirait d'une prémisse fausse sur le contrat qu'il touche.

## What Changes

- La phrase sur la couverture de tests est remplacée par la mesure, avec les fichiers qui
  exercent le contrat nommés, et un renvoi au contrat OpenAPI pour ce qui n'est pas couvert.
- La pile est corrigée : Doctrine 1.4, MariaDB 10.11, `utf8mb4`. Un avertissement est ajouté
  sur `Doctrine_Core::VERSION`, qui vaut encore `1.2.4` dans le code du fork et induit en
  erreur qui va le lire.
- La mention « jsonapi.org » est remplacée par ce qui est vrai : le type de contenu est
  imposé par `JsonApiFilter`, la conformité ne l'est pas, et `src/web/openapi.yaml` décrit
  ce qui est réellement servi.

## Hors périmètre

- Le reste du `context:`, re-vérifié à cette occasion et exact : routes, applications,
  modèle, déploiement Plesk, dépréciation de `make deploy`, suppression de la banque de
  mémoire.
- Le bloc `rules:` et le bloc `operations:`, qui ne décrivent pas l'état du projet mais la
  façon d'écrire les artefacts.
- Rendre le JSON conforme à JSON:API. C'est écarté au plan, et ce change constate, il ne
  décide pas.
