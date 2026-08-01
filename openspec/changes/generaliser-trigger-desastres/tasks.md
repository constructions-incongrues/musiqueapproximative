## 1. Convention de nommage

- [ ] 1.1 Arrêter la règle de nommage d'un déclencheur, et l'écrire dans `README-TRIGGER.adoc`. La proposition : le nom de la recette principale de la règle, ce qui rend le déclencheur déductible plutôt qu'inventé — `?kraftwerk`, `?danse`, `?spooky`
- [ ] 1.2 Traiter les deux cas où cette règle ne suffit pas : la règle `mangelettres` désigne deux recettes (`consonnard`, `voyelliste`), et `quickos` est désignée par deux règles distinctes — décembre, puis les 24 et 25. Deux déclencheurs sont donc nécessaires pour la seconde, faute de quoi un seul paramètre forcerait les deux règles et l'ambiguïté serait invérifiable
- [ ] 1.3 Vérifier qu'aucun nom retenu n'entre en collision avec un paramètre déjà interprété par l'application : `format`, `c`, `q`, `current`, `embed`, `url`, `count`, `contributor`

## 2. Déclaration des déclencheurs

- [ ] 2.1 `regles/mamie.yml` — un déclencheur sur la règle `mamie_allo`
- [ ] 2.2 `regles/mangelettres.yml` — un déclencheur sur la règle qui porte `consonnard` et `voyelliste`
- [ ] 2.3 `regles/misc.yml` — neuf déclencheurs : `amour`, `robot`, `fish`, `postillons_mort`, `musique`, `bleu`, `noir`, `light`, `danse`. Une règle en moins si `reparer-imports-desastres` a été appliqué avant, ce qui est le cas nominal
- [ ] 2.4 `regles/postillons.yml` — trois déclencheurs : `postillons_mort`, `kraftwerk`, `sale`
- [ ] 2.5 `regles/redirects.yml` — trois déclencheurs : `spooky`, et deux distincts pour les deux règles `quickos`
- [ ] 2.6 `regles/splitouine.yml` — un déclencheur sur la règle `catani`
- [ ] 2.7 `regles/tts.yml` — un déclencheur sur `tts_rapper` ; `tts_jinglist` conserve le sien, `jinglist`, qui ne doit pas être renommé
- [ ] 2.8 Recompter : vingt règles déclarées, vingt déclencheurs, aucun doublon

## 3. Schéma et documentation

- [ ] 3.1 Décider si `trigger` devient obligatoire dans `src/apps/frontend/config/desastres/schemas/regles.schema.json`. Le rendre obligatoire fait porter au schéma l'exigence « aucune règle sans déclencheur », ce que rien ne vérifierait autrement
- [ ] 3.2 Réécrire `src/plugins/sfDesastrePlugin/README-TRIGGER.adoc` : le document décrit une fonctionnalité ponctuelle, il doit décrire une propriété de la configuration — toute règle est forçable, et voici la convention
- [ ] 3.3 Documenter que les déclencheurs sont publics et pourquoi, avec la contrepartie : un désastre qui lirait une donnée ou modifierait un état ne pourrait plus être forcé publiquement

## 4. Vérification manuelle

> Chaque vérification suit le même geste : demander une page de morceau avec le paramètre,
> et regarder ce qui est injecté avant `</head>` dans le HTML servi. Les ressources d'un
> désastre `X` se reconnaissent aux chemins `/desastres/X/javascript/*.js` et
> `/desastres/X/stylesheets/*.css`, et aux options dans `window.DesastreOptions`.

- [ ] 4.1 Demander `/post/:slug?danse` sur un morceau dont le titre ne contient pas `danse`, et vérifier que le désastre est appliqué malgré la condition non satisfaite
- [ ] 4.2 Demander `/post/:slug?kraftwerk` sur un morceau dont l'artiste n'est pas Kraftwerk — même attente
- [ ] 4.3 Demander la même page **sans** paramètre, plusieurs fois, et vérifier que le désastre n'apparaît que selon sa probabilité. C'est le contrôle de non-régression qui compte : le forçage ne doit rien changer en son absence
- [ ] 4.4 Demander `/post/:slug?quickos` un jour qui n'est ni le 24 ni le 25 décembre, et vérifier la redirection. C'est la vérification qui débloque la tâche 4.4 de `reparer-imports-desastres`, restée ouverte faute de pouvoir attendre décembre
- [ ] 4.5 Demander `/post/:slug?bleu&noir` et vérifier que **les deux** désastres sont appliqués
- [ ] 4.6 Demander un déclencheur avec une valeur — `?danse=1`, `?danse=true`, `?danse=nimportequoi` — et vérifier que les trois se comportent comme `?danse` seul
- [ ] 4.7 Marquer temporairement une recette `enabled: false`, forcer sa règle, et vérifier qu'elle n'est **pas** appliquée : le forçage porte sur la sélection de la règle, jamais sur l'activation d'une recette. Rétablir ensuite
- [ ] 4.8 Parcourir les vingt déclencheurs un à un et cocher cette tâche seulement quand les vingt ont été constatés. Si l'un d'eux ne produit rien, l'écrire ici plutôt que cocher — c'est exactement le genre de panne silencieuse que ce changement existe pour rendre visible
- [ ] 4.9 Demander `/posts/feed?danse`, `/post/:slug?format=json&danse` et `/oembed?url=...&danse`, et vérifier qu'aucun désastre n'est injecté : les recettes ne s'appliquent qu'aux réponses `text/html`
