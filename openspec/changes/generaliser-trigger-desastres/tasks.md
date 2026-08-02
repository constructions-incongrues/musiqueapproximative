## 1. Convention de nommage

- [x] 1.1 Arrêter la règle de nommage d'un déclencheur, et l'écrire dans `README-TRIGGER.adoc`. La proposition : le nom de la recette principale de la règle, ce qui rend le déclencheur déductible plutôt qu'inventé — `?kraftwerk`, `?danse`, `?spooky`
      — retenue telle quelle, et écrite en tête de `README-TRIGGER.adoc` avec ses trois
      dérogations et la liste des paramètres réservés.
- [x] 1.2 Traiter les deux cas où cette règle ne suffit pas : la règle `mangelettres` désigne deux recettes (`consonnard`, `voyelliste`), et `quickos` est désignée par deux règles distinctes — décembre, puis les 24 et 25. Deux déclencheurs sont donc nécessaires pour la seconde, faute de quoi un seul paramètre forcerait les deux règles et l'ambiguïté serait invérifiable
      — `mangelettres` prend le nom du **désastre** que ses deux recettes partagent ; les
      deux règles `quickos` deviennent `quickos` et `quickos_noel`.
      — Un troisième cas est apparu à l'usage : `splitouine_titles_matchduration`, dont le
      nom de recette est long et porte un détail d'implémentation. Il est conservé tel
      quel malgré tout — une convention sans exception vaut mieux qu'un nom joli, et le
      déclencheur reste déductible de la configuration.
- [x] 1.3 Vérifier qu'aucun nom retenu n'entre en collision avec un paramètre déjà interprété par l'application : `format`, `c`, `q`, `current`, `embed`, `url`, `count`, `contributor`
      — contrôlé par script sur les dix-neuf : **aucune collision**.

## 2. Déclaration des déclencheurs

- [x] 2.1 `regles/mamie.yml` — un déclencheur sur la règle `mamie_allo`
- [x] 2.2 `regles/mangelettres.yml` — un déclencheur sur la règle qui porte `consonnard` et `voyelliste`
- [x] 2.3 `regles/misc.yml` — neuf déclencheurs : `amour`, `robot`, `fish`, `postillons_mort`, `musique`, `bleu`, `noir`, `light`, `danse`. Une règle en moins si `reparer-imports-desastres` a été appliqué avant, ce qui est le cas nominal
      — **huit**, et non neuf : `reparer-imports-desastres` a bien été appliqué avant, et
      la copie de `postillons_mort` a disparu de ce fichier. C'était le cas nominal prévu.
- [x] 2.4 `regles/postillons.yml` — trois déclencheurs : `postillons_mort`, `kraftwerk`, `sale`
- [x] 2.5 `regles/redirects.yml` — trois déclencheurs : `spooky`, et deux distincts pour les deux règles `quickos`
- [x] 2.6 `regles/splitouine.yml` — un déclencheur sur la règle `catani`
      — `splitouine_titles_matchduration`. La règle se reconnaît à sa condition sur
      `catani`, mais le déclencheur suit la convention et porte le nom de la recette.
- [x] 2.7 `regles/tts.yml` — un déclencheur sur `tts_rapper` ; `tts_jinglist` conserve le sien, `jinglist`, qui ne doit pas être renommé
- [x] 2.8 Recompter : vingt règles déclarées, vingt déclencheurs, aucun doublon
      — **dix-neuf et dix-neuf**, le compte de vingt datant d'avant le retrait du doublon
      `postillons_mort`. Contrôlé par script : aucune règle sans déclencheur, aucun
      déclencheur en double, aucune collision avec un paramètre de l'application.

## 3. Schéma et documentation

- [x] 3.1 Décider si `trigger` devient obligatoire dans `src/apps/frontend/config/desastres/schemas/regles.schema.json`. Le rendre obligatoire fait porter au schéma l'exigence « aucune règle sans déclencheur », ce que rien ne vérifierait autrement
      — **rendu obligatoire.** C'était le seul moyen de faire porter l'exigence par autre
      chose qu'une intention. Un motif `^[a-z][a-z0-9_]*$` est ajouté, et la description
      du champ porte la convention et les paramètres réservés — le schéma est lu par
      l'éditeur, la documentation ne l'est qu'après coup.
- [x] 3.2 Réécrire `src/plugins/sfDesastrePlugin/README-TRIGGER.adoc` : le document décrit une fonctionnalité ponctuelle, il doit décrire une propriété de la configuration — toute règle est forçable, et voici la convention
      — fait, et complété d'un **tableau des dix-neuf déclencheurs en service**, avec leurs
      recettes, leur probabilité et leur fichier.
      — Ce tableau est le neuvième document de ce dépôt qui décrit sans contraindre, et il
      dérivera comme les autres. Sa correspondance avec la configuration a été contrôlée
      par script à l'écriture — dix-neuf documentés, dix-neuf configurés, zéro écart — mais
      rien ne le refera. Le seul garde-fou durable est le schéma, qui exige la présence
      d'un déclencheur sans rien dire de son nom.
- [x] 3.3 Documenter que les déclencheurs sont publics et pourquoi, avec la contrepartie : un désastre qui lirait une donnée ou modifierait un état ne pourrait plus être forcé publiquement

## 4. Vérification manuelle

> Chaque vérification suit le même geste : demander une page de morceau avec le paramètre,
> et regarder ce qui est injecté avant `</head>` dans le HTML servi. Les ressources d'un
> désastre `X` se reconnaissent aux chemins `/desastres/X/javascript/*.js` et
> `/desastres/X/stylesheets/*.css`, et aux options dans `window.DesastreOptions`.
>
> **Aucune n'a pu être menée : le correctif n'est pas déployé.** Ce changement ne modifie
> que de la configuration et de la documentation — aucun code — et le mécanisme qu'il
> généralise est déjà en service sur `tts_jinglist` depuis des mois. Ce qui a été contrôlé
> ici, par script, tient en trois lignes : dix-neuf règles, dix-neuf déclencheurs, aucun
> doublon, aucune collision avec un paramètre de l'application, et un tableau de
> documentation qui correspond exactement à la configuration.
>
> Cela ne dit rien de ce qui compte : qu'un déclencheur force effectivement sa règle sur
> une page servie. C'est l'objet de tout ce qui suit.

- [ ] 4.1 Demander `/post/:slug?danse` sur un morceau dont le titre ne contient pas `danse`, et vérifier que le désastre est appliqué malgré la condition non satisfaite
- [ ] 4.2 Demander `/post/:slug?kraftwerk` sur un morceau dont l'artiste n'est pas Kraftwerk — même attente
- [ ] 4.3 Demander la même page **sans** paramètre, plusieurs fois, et vérifier que le désastre n'apparaît que selon sa probabilité. C'est le contrôle de non-régression qui compte : le forçage ne doit rien changer en son absence
- [ ] 4.4 Demander `/post/:slug?quickos` un jour qui n'est ni le 24 ni le 25 décembre, et vérifier la redirection. C'est la vérification qui débloque la tâche 4.4 de `reparer-imports-desastres`, restée ouverte faute de pouvoir attendre décembre
- [ ] 4.5 Demander `/post/:slug?bleu&noir` et vérifier que **les deux** désastres sont appliqués
- [ ] 4.6 Demander un déclencheur avec une valeur — `?danse=1`, `?danse=true`, `?danse=nimportequoi` — et vérifier que les trois se comportent comme `?danse` seul
- [ ] 4.7 Marquer temporairement une recette `enabled: false`, forcer sa règle, et vérifier qu'elle n'est **pas** appliquée : le forçage porte sur la sélection de la règle, jamais sur l'activation d'une recette. Rétablir ensuite
- [ ] 4.8 Parcourir les **dix-neuf** déclencheurs un à un et cocher cette tâche seulement quand les dix-neuf ont été constatés. Si l'un d'eux ne produit rien, l'écrire ici plutôt que cocher — c'est exactement le genre de panne silencieuse que ce changement existe pour rendre visible
- [ ] 4.9 Demander `/posts/feed?danse`, `/post/:slug?format=json&danse` et `/oembed?url=...&danse`, et vérifier qu'aucun désastre n'est injecté : les recettes ne s'appliquent qu'aux réponses `text/html`
