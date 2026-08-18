> Pas de `design.md`. Un jeu de caractères de base de test, un paramètre de DSN et des
> fixtures : aucune architecture, aucune dépendance. La seule contrainte à connaître est dans
> la proposition — les fixtures sont partagées avec la suite Subsonic, les ajouts doivent
> être additifs.

## 1. Établir l'état de départ

- [x] 1.1 Relever le jeu de caractères et la collation de la base de test, et confirmer que
  le DSN ne porte aucun `charset`.
- [x] 1.2 **Démontrer le défaut avant de le corriger.** Mesuré : en base de test `utf8`,
  `Paweł Zadrożniak` et `Сергей 坂本龍一` passent intacts ; `🎵` lève une **erreur MySQL
  1366**. En production `latin1`, les trois sont remplacés par `?` en silence.
  Conclusion qui recadre la story : la base de test tient déjà le plan multilingue de base,
  le manque est que **rien ne l'exerce**. `utf8mb4` ne gagne que les emoji. Et `utf8` refuse
  bruyamment là où `latin1` détruit sans rien dire — c'est le silence qui fait le dommage.

## 2. Porter la base de test

- [x] 2.1 `Makefile`, cible `test-init` : créer la base en `utf8mb4` /
  `utf8mb4_unicode_ci`. **`utf8` ne suffit pas** — trois octets, pas d'emoji.
- [x] 2.2 **Pas dans le DSN : `encoding: utf8mb4`.** Le `charset=` du DSN n'a aucun effet —
  Doctrine 1 analyse le DSN lui-même et ignore ce qu'il n'y connaît pas. Constaté à
  l'implémentation : les fixtures continuaient d'échouer en 1366 avec le DSN modifié. Le
  paramètre qui produit le `SET NAMES` est `encoding`, appliqué par
  `sfDoctrineConnectionListener`.
- [x] 2.3 Ne pas toucher au DSN de production ni à sa base : c'est la story 19.
- [x] 2.4 Recréer la base de test et vérifier que les tables la portent bien — créer la base
  ne suffit pas si les tables héritent d'autre chose.

## 3. Des fixtures qui exercent la frontière

- [x] 3.1 Ajouter des morceaux couvrant les quatre familles de la frontière cp1252 :
  cyrillique, idéogrammes, latines étendues (`ł`, `ż`, `ğ`), et un emoji. Prendre des cas
  **réels du corpus détruit** plutôt que des chaînes inventées — `Paweł Zadrożniak`,
  `Somnoroase Păsărele`, `Özdemir Erdoğan` sont dans la base de production, mutilés.
- [x] 3.2 **Ajouts retirés du fichier partagé, et voici pourquoi.** « Additif » ne suffit
  pas : quatre morceaux de plus, avec des identifiants neufs et sans toucher aux existants,
  ont fait basculer **42 assertions** dans quatre fichiers — `restActionsTest` (20),
  `PostTableSubsonicTest` (19), `FixturesTest` (2), `catalogueEtNavigationTest` (1). Ces
  suites comptent des morceaux, des artistes et des albums : toute croissance des fixtures
  les casse.
  Ces comptes sont fragiles par construction — ils encodent « il y a exactement cinq
  morceaux » plutôt qu'un comportement — mais les corriger est un autre travail, et ce
  change ne touche que ce qu'il annonce. **Le test Unicode pose donc ses propres morceaux**,
  dans la même base et servis par le même code, sans peser sur le décompte des autres.
- [x] 3.3 Relancer la suite complète après l'ajout, avant d'écrire le moindre test : si les
  fixtures cassent quelque chose, il faut le savoir tout de suite.

## 4. Le test qui dit ce que le site doit faire

- [x] 4.1 Vérifier que chaque morceau ajouté est restitué **identique** à ce qui a été saisi,
  dans la page HTML, le JSON, le XSPF, le `max` et le flux.
- [x] 4.2 Comparer aux valeurs de la base, non à des chaînes recopiées dans le test : une
  constante recopiée peut porter la même faute que le code.
- [x] 4.3 Vérifier explicitement l'absence de `?` de substitution dans les valeurs
  restituées — c'est la forme exacte que prend le dégât.

## 5. Vérification

- [x] 5.1 Reprendre la mesure de 1.2 : ce qui revenait mutilé revient intact.
- [x] 5.2 **Voir le test échouer** : recréer la base de test en `utf8` comme avant, relancer,
  constater l'échec nommé, puis rétablir. Sans cette épreuve, le test ne prouve pas qu'il
  protège de quoi que ce soit.
- [x] 5.3 `docker-compose exec php php symfony test:all` — la suite passe. Relever avant et
  après.
- [x] 5.4 Vérifier que la suite Subsonic n'a pas bougé : ses 119 assertions comptent des
  morceaux et des artistes, et les fixtures viennent de changer.
- [x] 5.5 Vérifier que le test de contrat passe toujours, ses valeurs venant des fixtures.
- [x] 5.6 `openspec validate porter-la-base-de-test-en-utf8mb4 --type change --strict`.

### Ce que cette story ne démontre pas

- [x] 5.7 Consigner : le test passe en environnement de test **pendant que la production
  reste cassée**. Il ne prouve rien sur la production tant que la story 19 n'a pas eu lieu.
  C'est voulu, et le dire évite qu'on lise le vert de la suite comme un état du site.

### Mesures relevées le 2026-08-18

| | avant | après |
| --- | --- | --- |
| base de test | `utf8` / `utf8_general_ci` | **`utf8mb4` / `utf8mb4_unicode_ci`** |
| encodage de connexion | non déclaré | **`encoding: utf8mb4`** |
| `Paweł Zadrożniak` en base de test | passait déjà | passe |
| `Сергей 坂本龍一` | passait déjà | passe |
| `🎵` | **erreur MySQL 1366** | passe |
| suite | 20 fichiers, 602 tests | **21 fichiers, 624 tests** |

**Faillibilité vérifiée** : base de test rétablie en `utf8`, le test échoue — d'abord sur
l'assertion de contexte, puis à l'insertion de l'emoji, qui lève une exception. Rétabli en
`utf8mb4`, les 22 assertions passent, stables sur cinq exécutions.

### Trois choses apprises en implémentant

**1. `charset=` dans le DSN ne sert à rien.** Doctrine 1 analyse le DSN lui-même. Le levier
est `encoding`, qui produit un `SET NAMES`. Une heure perdue à croire le contraire.

**2. « Additif » ne veut pas dire « sans effet ».** Quatre morceaux ajoutés au fichier de
fixtures partagé, avec des identifiants neufs et sans toucher aux existants, ont fait
basculer **42 assertions** dans quatre fichiers qui comptent des morceaux et des artistes.
Les fixtures Unicode sont donc posées par le test lui-même.

**3. La requête de chauffe doit rendre du Markdown.** Une page de liste ne suffit pas —
elle n'affiche que l'artiste et le titre, jamais le corps. Il faut une page de morceau. Ce
détail a coûté quatre exécutions à comprendre, et il vaut pour les deux autres fichiers qui
portent la même chauffe.

### Ce que cette story ne démontre pas

Le test passe en environnement de test **pendant que la production reste cassée**. Il ne
prouve rien sur la production tant que la story 19 n'a pas eu lieu. Le vert de la suite ne
doit pas être lu comme un état du site.
