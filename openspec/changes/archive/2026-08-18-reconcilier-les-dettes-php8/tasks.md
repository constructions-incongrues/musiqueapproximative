# Tâches

Pas de `specs/` : `skip_specs` déclaré. Aucun comportement observable ne change — du code
mort disparaît. Pas de `design.md`.

## 1. Défaire une affirmation que j'avais écrite

- [x] 1.1 Reprendre le prétendu verrou `getid3`. L'occurrence est **dans un commentaire**,
  ligne 355. **Ce verrou n'a jamais existé.** Il avait été relevé au `grep`, sans vérifier
  s'il s'agissait de code vivant.
- [x] 1.2 Vérifier au passage que `getid3` est déjà à la dernière version de sa branche,
  `v1.9.25` — la montée qu'on croyait nécessaire ne l'était pas non plus.

## 2. Reprendre la mesure avec le bon outil

- [x] 2.1 Analyser les **jetons** (`token_get_all`) plutôt que le texte : commentaires et
  chaînes sont alors ignorés, ce qu'un `grep` ne sait pas faire. C'est la leçon de 1.1.
- [x] 2.2 Passer tout le code exécuté : `apps`, `lib`, `plugins`, `config`, `test`, `vendor`.
- [x] 2.3 Résultat : **0 accès de chaîne en accolades** partout. **3 constructeurs PHP 4**
  dans `src/lib/vendor/File/`, et 3 dans `src/vendor` tiré par Composer.

## 3. Établir que la bibliothèque est morte, avant de la retirer

- [x] 3.1 Chercher toute référence à `File_XSPF` dans le dépôt. **La seule est le
  commentaire qui explique qu'on l'a retirée** : « absente de l'image et dont le `require`
  échouait fatalement ». Le XSPF est produit par `_xspfPlaylist.xspf.php`.
- [x] 3.2 Constater que `src/lib/vendor` **est** sur l'`include_path` PEAR : le répertoire
  est atteignable, il n'est simplement jamais requis.
- [x] 3.3 Fait qui tranche : **`XSPF.php` ne compile pas sur le PHP 7.4 du projet.** Il
  emploie `return throw new …`, syntaxe valide à partir de PHP 8.0 seulement. Chargé, il
  provoquerait une erreur d'analyse. Ce n'est pas une bibliothèque en sommeil, c'est une
  bibliothèque cassée.
- [x] 3.4 Noter le piège de méthode : `php -l` sur le poste de développement, en PHP 8, dit
  « No syntax errors ». C'est le PHP 7.4 du conteneur qui refuse. **Vérifier une compatibilité
  sur la mauvaise version d'interpréteur ne prouve rien.**

## 4. Retirer

- [x] 4.1 Supprimer `src/lib/vendor/File/` — neuf fichiers, 128 Kio, présents depuis le
  commit « Legacy ».
- [x] 4.2 **`test:all` : 22 fichiers, 645 tests, verts.**
- [x] 4.3 Le XSPF est toujours servi : `/posts?format=xspf` répond `200` avec **8 098
  pistes** et un document bien formé ; un morceau isolé répond `200` lui aussi.

## 5. Réconcilier les dettes que les archives croient ouvertes

- [x] 5.1 `servir-le-contrat-sans-rendu` 5.1 — « le contrat n'est vérifié que contre
  l'instance de test ». **Fermée** par `verifier-le-contrat-en-production`.
- [x] 5.2 `servir-le-contrat-sans-rendu` 5.2 — « les autres `-dist` n'ont pas été mesurés ».
  **Fermée** par `auditer-les-fichiers-dist` : il n'en reste que deux, et rien n'a dérivé.
- [x] 5.3 `verifier-le-contrat-en-production` 5.5 — « `nightly.yml` reste rouge ». **Fermée**
  par sa suppression : le fichier ne portait plus qu'un job qui n'avait jamais été vert.
- [x] 5.4 `assainir-le-rendu-markdown` 5.1 — « second verrou PHP 8 dans getid3 ». **Fermée
  parce que fausse**, et remplacée par la mesure ci-dessus.
- [x] 5.5 Porter cette réconciliation au plan et non dans les archives : un `tasks.md`
  archivé est le relevé de ce qui a été fait à une date, pas un document à corriger après
  coup.

## 6. Ce qui reste ouvert, et ne bouge pas

- [ ] 6.1 Les **3 constructeurs de `src/vendor`**, tirés par Composer. Une correction sur
  place serait effacée au prochain `composer install`.
- [ ] 6.2 **L'audit PHP 8 lui-même.** Ce change retire un obstacle réel et en dissipe un
  imaginaire ; il ne dit rien des autres incompatibilités, qui dépassent de loin deux
  syntaxes.
