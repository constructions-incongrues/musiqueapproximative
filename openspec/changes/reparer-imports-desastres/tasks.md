## 1. Correction des chemins d'import

- [ ] 1.1 Dans `src/apps/frontend/config/desastres.yml`, corriger `desastres/regles/redirect.yml` en `desastres/regles/redirects.yml`
- [ ] 1.2 Corriger `desastres/regles/slitouine.yml` en `desastres/regles/splitouine.yml`
- [ ] 1.3 Corriger `desastres/recettes/slitouine.yml` en `desastres/recettes/splitouine.yml`
- [ ] 1.4 Relire les onze autres chemins un à un contre le contenu de `regles/` et `recettes/` — trois erreurs sur quatorze suggèrent que la liste n'a jamais été confrontée aux fichiers

## 2. Doublon `postillons_mort`

- [ ] 2.1 Supprimer de `src/apps/frontend/config/desastres/regles/misc.yml` la règle dont la condition est `query.title ~ /.*(morte?s?|deaths?|dead).*/i`, en conservant celle de `postillons.yml`
- [ ] 2.2 Vérifier qu'aucune autre règle n'est déclarée deux fois : comparer les couples condition/recettes des sept fichiers de `regles/`

## 3. Visibilité d'un import non résolu

- [ ] 3.1 Dans `sfDesastreManager::processImports()`, retenir la liste des chemins déclarés qui ne se résolvent pas, au lieu de la perdre après l'`error_log`
- [ ] 3.2 Conserver l'`error_log` existant tel quel — c'est la trace durable, et rien ne justifie de la changer
- [ ] 3.3 Émettre un avertissement dans la console du navigateur nommant chaque chemin non résolu, en empruntant le chemin d'injection existant : `injectDesastreOptions()` produit déjà un `<script>` inline, et `sfDesastreFilter` l'insère avant `</head>` dans les réponses `text/html`
- [ ] 3.4 S'assurer qu'aucun avertissement n'est émis lorsque tous les imports se résolvent — ni script inline supplémentaire, ni ligne de journal
- [ ] 3.5 S'assurer que l'avertissement ne s'émet pas sur les réponses qui ne sont pas du HTML : `/posts/feed`, `format=json`, `format=xspf`, `format=max`, `/oembed`

## 4. Vérification manuelle

> Le désastre `quickos` ne peut pas être vérifié avant décembre : sa condition porte sur
> `context.date.month == '12'`, et rien ne permet aujourd'hui de forcer une règle — c'est
> précisément ce qu'apporte `generaliser-trigger-desastres`. La tâche 4.4 reste donc
> ouverte, et son échéance est réelle.

- [ ] 4.1 Sur une page de morceau quelconque, ouvrir la console du navigateur et vérifier qu'**aucun** avertissement de désastre n'apparaît — les quatorze imports se résolvent désormais
- [ ] 4.2 Introduire volontairement une faute dans un chemin d'import, recharger une page de morceau, et vérifier que la console nomme le chemin fautif **et** que la page reste servie normalement. Rétablir le chemin ensuite
- [ ] 4.3 Trouver un morceau dont l'artiste contient `catani`, demander `/post/:slug`, et vérifier dans le HTML servi que les ressources du désastre `splitouine` sont injectées avant `</head>`. La règle déclare `probability: 1` : elle doit se déclencher à chaque fois, sans exception. C'est la preuve que `regles/splitouine.yml` et `recettes/splitouine.yml` se chargent enfin. Si aucun morceau ne correspond, le noter ici plutôt que cocher
- [ ] 4.4 **Bloquée jusqu'en décembre 2026.** Vérifier qu'un morceau demandé un 24 ou un 25 décembre redirige vers `quickoschantenoel.musiqueapproximative.net` au bout de trois secondes, une fois sur deux. À rouvrir à cette date, ou plus tôt si `generaliser-trigger-desastres` aboutit d'ici là
- [ ] 4.5 Chercher un morceau dont le titre est exactement `Spooky Mix`, demander `/post/:slug`, et vérifier la redirection vers `sos.musiqueapproximative.net` — `probability: 1` là aussi. Si ce morceau n'existe pas au catalogue, le noter : la règle serait alors inerte pour une seconde raison, indépendante de celle que ce changement corrige
- [ ] 4.6 Sur un morceau dont le titre contient `mort`, `death` ou `dead`, recharger une dizaine de fois et constater que le désastre `postillons_mort` ne se déclenche plus systématiquement. La vérification est statistique et donc faible : elle ne distingue pas 0,7 de 0,91 sur dix tirages. Elle ne vaut que comme contrôle grossier de non-régression
- [ ] 4.7 Demander `/posts/feed`, `/post/:slug?format=json` et `/oembed?url=...`, et vérifier qu'aucun `<script>` de désastre n'apparaît dans ces réponses
