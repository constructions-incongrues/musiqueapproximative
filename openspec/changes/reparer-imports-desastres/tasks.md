## 1. Correction des chemins d'import

- [x] 1.1 Dans `src/apps/frontend/config/desastres.yml`, corriger `desastres/regles/redirect.yml` en `desastres/regles/redirects.yml`
- [x] 1.2 Corriger `desastres/regles/slitouine.yml` en `desastres/regles/splitouine.yml`
- [x] 1.3 Corriger `desastres/recettes/slitouine.yml` en `desastres/recettes/splitouine.yml`
- [x] 1.4 Relire les onze autres chemins un à un contre le contenu de `regles/` et `recettes/` — trois erreurs sur quatorze suggèrent que la liste n'a jamais été confrontée aux fichiers
      — les onze sont bons. Confronté par script : **quatorze chemins déclarés, zéro non
      résolu.** Noter l'asymétrie qui a rendu la panne trompeuse : `recettes/redirect.yml`
      est correct au singulier, alors que `regles/redirects.yml` prend un `s`. Les deux
      fichiers ne portent pas le même nom, et ce n'est pas une faute — c'est ainsi.

## 2. Doublon `postillons_mort`

- [x] 2.1 Supprimer de `src/apps/frontend/config/desastres/regles/misc.yml` la règle dont la condition est `query.title ~ /.*(morte?s?|deaths?|dead).*/i`, en conservant celle de `postillons.yml`
- [x] 2.2 Vérifier qu'aucune autre règle n'est déclarée deux fois : comparer les couples condition/recettes des sept fichiers de `regles/`
      — aucun autre doublon. Confronté par script sur les sept fichiers : **dix-neuf règles
      chargées, dix-neuf couples condition/recettes distincts.** Vingt étaient déclarées
      avant ce changement, dont deux identiques.

## 3. Visibilité d'un import non résolu

- [x] 3.1 Dans `sfDesastreManager::processImports()`, retenir la liste des chemins déclarés qui ne se résolvent pas, au lieu de la perdre après l'`error_log`
      — propriété `$unresolvedImports`, réinitialisée à chaque `processImports()`, exposée
      par `getUnresolvedImports()`.
- [x] 3.2 Conserver l'`error_log` existant tel quel — c'est la trace durable, et rien ne justifie de la changer
      — les deux appels sont intacts, à la ligne près. Une seule ligne est ajoutée après
      chacun.
- [x] 3.3 Émettre un avertissement dans la console du navigateur nommant chaque chemin non résolu, en empruntant le chemin d'injection existant : `injectDesastreOptions()` produit déjà un `<script>` inline, et `sfDesastreFilter` l'insère avant `</head>` dans les réponses `text/html`
      — `injectUnresolvedImportsWarning()`, appelée depuis `applyToRequest()` **hors** du
      test `if (!empty($recettes))`. C'était le point à ne pas manquer : une configuration
      cassée doit se voir même quand aucune règle ne se déclenche, or `applyRecettesToResponse()`
      n'est appelée que lorsqu'une recette s'applique.
      — Le filtre lit un troisième attribut, `desastre_warnings_js`, à côté des deux
      existants.
- [x] 3.4 S'assurer qu'aucun avertissement n'est émis lorsque tous les imports se résolvent — ni script inline supplémentaire, ni ligne de journal
      — l'attribut est **toujours écrit**, à `null` quand il n'y a rien à dire. Les
      attributs utilisateur de Symfony 1.x persistent en session : sans cette écriture, un
      avertissement émis avant la correction d'un chemin survivrait à cette correction.
- [x] 3.5 S'assurer que l'avertissement ne s'émet pas sur les réponses qui ne sont pas du HTML : `/posts/feed`, `format=json`, `format=xspf`, `format=max`, `/oembed`
      — acquis par construction, sans code supplémentaire : `sfDesastreFilter` teste déjà
      le type de contenu et ne touche qu'aux réponses HTML. `apply_desastre()` n'est du
      reste appelée que depuis `executeShow()` et `executeList()`.
- [x] 3.6 Exercer la logique hors Symfony, le vendor n'étant pas installé
      — banc d'essai avec bouchons pour `sfYaml`, `sfContext` et l'utilisateur, appelant
      `processImports()` et `injectUnresolvedImportsWarning()` par réflexion. Quatre cas,
      tous verts : configuration réelle sans faute, deux chemins fautifs relevés tous les
      deux, attribut à `null` quand tout résout, `console.warn` nommant les deux chemins
      sinon. `php -l` passe sur les deux fichiers modifiés.
      — Le banc n'est **pas versé au dépôt** : il vaut comme preuve d'exécution ici, pas
      comme suite de tests. Lui donner une place durable dépasse ce changement — et
      mériterait le sien, `src/test/` existant déjà sans être utilisé.

## 4. Vérification manuelle

> **Aucune de ces vérifications n'a pu être menée, et toutes les cases restent ouvertes.**
> Le conteneur où ce changement a été implémenté n'a ni instance du site qui tourne, ni
> base de données, ni le vendor Symfony installé. Les tâches 4.3 et 4.5 supposent en outre
> l'existence de morceaux précis au catalogue — un artiste contenant `catani`, un titre
> exactement égal à `Spooky Mix` — ce qui ne se sait pas d'ici.
>
> Ce qui a été vérifié à la place, et qui ne remplace rien : la logique modifiée, exercée
> hors Symfony par un banc d'essai à bouchons (tâche 3.6), et la résolution des quatorze
> chemins d'import confrontée aux fichiers (tâche 1.4).
>
> Le désastre `quickos` reste par ailleurs invérifiable avant décembre : sa condition porte
> sur `context.date.month == '12'`, et rien ne permet aujourd'hui de forcer une règle —
> c'est précisément ce qu'apporte `generaliser-trigger-desastres`. Son échéance est réelle.

- [ ] 4.1 Sur une page de morceau quelconque, ouvrir la console du navigateur et vérifier qu'**aucun** avertissement de désastre n'apparaît — les quatorze imports se résolvent désormais
- [ ] 4.2 Introduire volontairement une faute dans un chemin d'import, recharger une page de morceau, et vérifier que la console nomme le chemin fautif **et** que la page reste servie normalement. Rétablir le chemin ensuite
- [ ] 4.3 Trouver un morceau dont l'artiste contient `catani`, demander `/post/:slug`, et vérifier dans le HTML servi que les ressources du désastre `splitouine` sont injectées avant `</head>`. La règle déclare `probability: 1` : elle doit se déclencher à chaque fois, sans exception. C'est la preuve que `regles/splitouine.yml` et `recettes/splitouine.yml` se chargent enfin. Si aucun morceau ne correspond, le noter ici plutôt que cocher
- [ ] 4.4 **Bloquée jusqu'en décembre 2026.** Vérifier qu'un morceau demandé un 24 ou un 25 décembre redirige vers `quickoschantenoel.musiqueapproximative.net` au bout de trois secondes, une fois sur deux. À rouvrir à cette date, ou plus tôt si `generaliser-trigger-desastres` aboutit d'ici là
- [ ] 4.5 Chercher un morceau dont le titre est exactement `Spooky Mix`, demander `/post/:slug`, et vérifier la redirection vers `sos.musiqueapproximative.net` — `probability: 1` là aussi. Si ce morceau n'existe pas au catalogue, le noter : la règle serait alors inerte pour une seconde raison, indépendante de celle que ce changement corrige
- [ ] 4.6 Sur un morceau dont le titre contient `mort`, `death` ou `dead`, recharger une dizaine de fois et constater que le désastre `postillons_mort` ne se déclenche plus systématiquement. La vérification est statistique et donc faible : elle ne distingue pas 0,7 de 0,91 sur dix tirages. Elle ne vaut que comme contrôle grossier de non-régression
- [ ] 4.7 Demander `/posts/feed`, `/post/:slug?format=json` et `/oembed?url=...`, et vérifier qu'aucun `<script>` de désastre n'apparaît dans ces réponses
