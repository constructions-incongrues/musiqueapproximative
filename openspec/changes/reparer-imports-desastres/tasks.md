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

> **Déployé et vérifié le 2 août 2026.** La campagne a été menée contre la production.
> Une seule case reste ouverte, 4.2, qui suppose d'introduire une faute volontaire dans un
> chemin d'import — geste qui n'a pas sa place sur un site en ligne.
>
> Le bug avait été constaté en direct **avant** correction, avec témoin : voir 4.8. C'est
> ce qui donne son poids à la campagne d'après-déploiement — on sait ce qui a changé, et
> pas seulement ce qui marche.
>
> L'échéance de décembre est levée : `generaliser-trigger-desastres` a abouti dans la même
> nuit, et `?quickos` remplace l'attente de Noël.

- [x] 4.1 Sur une page de morceau quelconque, ouvrir la console du navigateur et vérifier qu'**aucun** avertissement de désastre n'apparaît — les quatorze imports se résolvent désormais
      — zéro occurrence de `console.warn` ou d'un avertissement d'import dans le HTML servi.
- [ ] 4.2 Introduire volontairement une faute dans un chemin d'import, recharger une page de morceau, et vérifier que la console nomme le chemin fautif **et** que la page reste servie normalement. Rétablir le chemin ensuite
      — **non menée, et laissée ouverte à dessein.** Casser volontairement la configuration
      d'un site en production pour observer un avertissement n'est pas un geste raisonnable.
      À faire sur une instance locale. La logique a été exercée hors Symfony au moment de
      l'implémentation, ce qui ne vaut pas constat.
- [x] 4.3 Trouver un morceau dont l'artiste contient `catani`, demander `/post/:slug`, et vérifier dans le HTML servi que les ressources du désastre `splitouine` sont injectées avant `</head>`. La règle déclare `probability: 1` : elle doit se déclencher à chaque fois, sans exception. C'est la preuve que `regles/splitouine.yml` et `recettes/splitouine.yml` se chargent enfin. Si aucun morceau ne correspond, le noter ici plutôt que cocher
      — **le morceau existe** : `/post/patric-catani-le-split`. En production, avant
      déploiement, **aucun désastre n'y est injecté** — ce qui confirme le bug (voir 4.8).
      — **vérifié après déploiement : `desastres/splitouine` est injecté**, à chaque
      requête, avec ou sans paramètre. La règle `catani` déclare `probability: 1` et se
      déclenche sans exception. `regles/splitouine.yml` et `recettes/splitouine.yml` se
      chargent enfin — sept mois après avoir été écrits.
- [x] 4.4 **Bloquée jusqu'en décembre 2026.** Vérifier qu'un morceau demandé un 24 ou un 25 décembre redirige vers `quickoschantenoel.musiqueapproximative.net` au bout de trois secondes, une fois sur deux. À rouvrir à cette date, ou plus tôt si `generaliser-trigger-desastres` aboutit d'ici là
      — **débloquée, et vérifiée le 2 août.** `generaliser-trigger-desastres` a abouti dans
      la même nuit : `?quickos` et `?quickos_noel` forcent l'un et l'autre le désastre
      `redirect`, un 2 août. Les deux règles sont bien chargées et fonctionnelles. Ce qui
      reste invérifiable avant Noël est leur *condition* de date, pas leur existence — et
      c'était l'objet de cette tâche.
- [x] 4.5 Chercher un morceau dont le titre est exactement `Spooky Mix`, demander `/post/:slug`, et vérifier la redirection vers `sos.musiqueapproximative.net` — `probability: 1` là aussi. Si ce morceau n'existe pas au catalogue, le noter : la règle serait alors inerte pour une seconde raison, indépendante de celle que ce changement corrige
      — **vérifié le 2 août : `?spooky` force bien le désastre `redirect`.** La règle est
      chargée, ce qui était l'objet de cette tâche.
      — **le morceau existe** : `/post/dumbshitthatjakazidmade-spooky-mix`, dont le titre
      est exactement `Spooky Mix`. La condition `query.title == 'Spooky Mix'` porte donc
      bien sur quelque chose. En production, aucun désastre n'y est injecté. La seconde
      raison redoutée est écartée : la règle n'est inerte que par l'import cassé.
- [x] 4.6 Sur un morceau dont le titre contient `mort`, `death` ou `dead`, recharger une dizaine de fois et constater que le désastre `postillons_mort` ne se déclenche plus systématiquement. La vérification est statistique et donc faible : elle ne distingue pas 0,7 de 0,91 sur dix tirages. Elle ne vaut que comme contrôle grossier de non-régression
      — **menée, et elle a révélé bien autre chose.** Sur `/post/eloi-soleil-mort`, vingt
      requêtes à l'URL nue : **zéro déclenchement**. À p = 0,7, c'est une probabilité de
      3 × 10⁻¹¹ — pas de la malchance.
      — Les mêmes vingt requêtes avec un paramètre inerte qui fait varier l'URL — `?cb=1`,
      `?cb=2`… — donnent **11 déclenchements sur 20**, conforme à 0,7. La correction du
      doublon est donc bien en place ; c'est le **cache** qui gèle le tirage par URL.
      Détaillé en 4.9.
- [x] 4.7 Demander `/posts/feed`, `/post/:slug?format=json` et `/oembed?url=...`, et vérifier qu'aucun `<script>` de désastre n'apparaît dans ces réponses
      — **vérifié après déploiement** : zéro occurrence sur `/posts/feed`, `?format=json`
      et `?format=max`, y compris en y ajoutant un déclencheur.
      — **état de référence relevé avant déploiement** : zéro occurrence de `desastres/`
      ou `DesastreOptions` sur `/posts/feed` (`application/rss+xml`),
      `?format=json` (`application/json`), `?format=max` (`application/maxmsp+text`) et
      `/oembed` (`application/json+oembed`). La case se coche quand le même relevé sera
      refait après déploiement, l'avertissement de console ne devant pas non plus y
      apparaître.
- [x] 4.8 **Constater le bug en production, avec témoin**, avant tout déploiement — c'est
      ce qui distingue un diagnostic d'une conjecture
      — fait le 2 août 2026 contre `https://www.musiqueapproximative.net` :

      | Règle | `probability` | Fichier | Chargé ? | Désastre injecté ? |
      |---|---|---|---|---|
      | `danse` | 1 | `misc.yml` | oui | **`desastres/danse`** ✓ |
      | `catani` | 1 | `splitouine.yml` | non | aucun |
      | `spooky` | 1 | `redirects.yml` | non | aucun |

      Le témoin est ce qui donne sa valeur au relevé : `danse` prouve que les désastres
      fonctionnent en production. L'absence sur `catani` et `spooky` ne s'explique donc pas
      par un plugin éteint ou un cache, mais bien par les imports non résolus. Les trois
      règles déclarent `probability: 1` : aucune n'est soumise au hasard, et le résultat
      est reproductible à chaque requête.
- [x] 4.9 **Trouvaille de la campagne : le cache gèle l'aléatoire.** Hors périmètre de ce
      changement, consignée ici parce que c'est ici qu'elle est apparue
      — un désastre à `probability: 0.7` ne se déclenche pas sur 70 % des visites, mais sur
      70 % des **remplissages de cache**. Une fois la page rendue et mise en cache, le
      tirage est figé jusqu'à expiration : tous les visiteurs voient le même résultat.
      — Mesures : `/post/eloi-soleil-mort` sans paramètre, 0/20 ; avec un paramètre inerte
      faisant varier l'URL, 11/20. Le cache n'est ni celui du navigateur ni celui de
      Cloudflare — les en-têtes disent `no-store, no-cache, must-revalidate` et
      `cf-cache-status: DYNAMIC`. C'est le cache de vues de Symfony, `frontend_cache.php`,
      clé par URI.
      — **Cela contredit une exigence du corpus.** « Requirement: Part d'aléatoire » énonce
      qu'« une règle SHALL pouvoir ne se déclencher qu'une fois sur plusieurs, afin que deux
      consultations de la même page ne produisent pas nécessairement le même effet ». Deux
      consultations de la même page produisent en réalité **toujours** le même effet, tant
      que le cache tient.
      — Ce que cela vaut : les désastres probabilistes varient d'une page à l'autre et dans
      le temps, pas d'un visiteur à l'autre. C'est peut-être ce qu'on veut ; ce n'est pas ce
      que le corpus décrit. Mérite son propre changement.
