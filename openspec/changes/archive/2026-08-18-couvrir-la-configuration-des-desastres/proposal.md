## Why

La spec `desastres` est la plus grosse du dépôt — 30 scénarios, 80 étapes — et elle
n'a aucun test. C'est aussi la zone où les deux derniers bugs de production ont eu lieu :
`2026-08-07-reparer-injection-des-options` et le correctif du `Content-Type` mis en cache.

Ces 30 scénarios ne sont pas également testables, et l'exploration l'a chiffré :

| groupe                              | scénarios | ce qu'il faut pour le tester           |
| ----------------------------------- | --------: | -------------------------------------- |
| configuration, imports, unicité     |         9 | rien de plus qu'aujourd'hui            |
| forçage par paramètre               |        12 | une page servie                        |
| invariance sur le cache             |         6 | `cache: true`, absent de `test:`        |
| statistique (« tend vers p »)       |         2 | un grand nombre de productions de page |
| clauses « peut »                    |   2 clauses | rien : elles ne sont pas falsifiables |

Ce changement ne prend que le premier groupe. C'est le seul qui soit couvrable sans
toucher à la configuration des environnements, sans second langage d'exécution, et sans
convertir quoi que ce soit. `sfDesastreManager` expose déjà les coutures nécessaires :
son constructeur accepte une configuration, `loadConfig()` un chemin, et
`getUnresolvedImports()` rend constatable exactement ce que la spec demande de constater.

## What Changes

- Ajoute `src/test/unit/plugins/DesastreConfigTest.php`, qui couvre les neuf scénarios de
  configuration de la spec `desastres`.
- Ajoute les fixtures YAML minimales que ces tests exigent, sous
  `src/test/fixtures/desastres/` : une configuration dont un import ne se résout pas, une
  dont tous se résolvent, une portant une règle déclarée deux fois, une portant une règle
  sans paramètre de déclenchement, et une portant deux règles au même déclencheur.
- Aucun comportement observable ne change. Le changement pose donc `skip_specs: true`.

Un point de conception à respecter, que l'exploration a mis au jour : le scénario
« Fichier de configuration manquant » n'est **pas** garanti par `sfDesastreManager`, dont
`loadConfig()` lève une exception. La garantie vit dans `apply_desastre()`, qui teste
`file_exists()` avant de construire le manager. Ce scénario doit donc viser le helper. Un
test qui viserait le manager constaterait l'exception et conclurait à tort.

## Hors périmètre

- **Les 21 autres scénarios de `desastres`.** Forçage, invariance sur le cache et
  statistique demandent chacun un dispositif différent. Les mélanger ici rendrait le
  changement inévaluable.
- **`cache: false` dans l'environnement de test.** C'est la découverte la plus lourde de
  l'exploration : les scénarios d'invariance décrivent un comportement qui n'a pas lieu là
  où les tests s'exécutent, et les deux bugs archivés de cette zone sont précisément de
  cette classe. Le corriger touche `settings.yml` et le comportement de toute la suite.
  Cela mérite son propre changement.
- **La clarification des deux clauses non falsifiables.** « l'effet servi peut différer »,
  « le rendu peut néanmoins différer » : on ne peut pas écrire de test qui échoue sur un
  « peut ». Acter qu'elles sont documentaires est une modification de la spec `desastres`,
  pas un ajout de tests.
- **Toute conversion vers Gherkin.** Le corpus ne réutilise que 12 % de ses étapes, et
  `desastres` 2 %. Cette question reste ouverte et n'est pas tranchée ici.

## Capabilities

### New Capabilities

Aucune. Ce changement ajoute des tests sur un comportement déjà spécifié par
`openspec/specs/desastres/spec.md`.

### Modified Capabilities

Aucune. Aucune exigence ne change, aucun comportement observable ne bouge.

## Impact

- **Ajouté** : `src/test/unit/plugins/DesastreConfigTest.php` et les fixtures sous
  `src/test/fixtures/desastres/`.
- **Lu, jamais modifié** : `src/plugins/sfDesastrePlugin/lib/sfDesastreManager.class.php`
  et `src/plugins/sfDesastrePlugin/lib/helper/DesastreHelper.php`.
- **Contrat public** : pas concerné. Rien de ce que servent les routes publiques ne change.
- **Exécution** : `php symfony test:unit`, dans le conteneur existant. Aucune dépendance
  nouvelle, aucun second langage d'exécution.
- **Chargement des classes** : `src/config/autoload.yml` n'existe pas, donc les classes du
  plugin ne sont pas autochargées dans un test unitaire. Le test les charge par
  `require_once`, comme le font déjà `JsonApiFilterTest` et `SubsonicIdTest`.
