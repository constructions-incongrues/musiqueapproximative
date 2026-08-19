## Why

Le packet de cette story demandait de mesurer avant de décider, et prévenait : « si la
mesure dit que c'est rare, la bonne conclusion est de ne rien faire, et de l'écrire ».

**La mesure a été faite. Elle dit de ne pas outiller — et elle a trouvé autre chose.**

### La fréquence

Les dix-neuf recettes sont apparues sur **deux jours**, les 9 et 10 novembre 2025. Pas par
un import massif : par pull requests distinctes et nommées — `SPOOOOOKYYYYY (#18)`,
`Desastres-plomberie (#20)`, `Consonnards et Voyellistes (#23)`, `Plomberie (#27)`. Elles
ont bien été écrites une à une, en rafale.

Depuis : 27 commits sur les désastres en novembre 2025, 3 en janvier 2026, 4 en août 2026.
**Aucun désastre neuf en neuf mois.**

Construire un générateur pour une tâche qui n'a pas été faite une seule fois depuis neuf
mois coûterait plus qu'il ne rendrait. C'est le verdict que le packet appelait.

### Le coût réel, et ce qu'il n'est pas

Écrire un désastre complet demande aujourd'hui cinq fichiers dans quatre répertoires, plus
deux lignes d'import à déclarer et deux schémas JSON à respecter. Ce n'est pas beaucoup.

**Le coût n'est pas là.** Le persona de cette story est « quelqu'un qui a une idée et pas
la carte du dépôt ». Or il y a deux cartes, et l'une est fausse :

| document | lignes | décrit |
| --- | --- | --- |
| `docs/modules/ROOT/pages/desastres.adoc` | 598 | la structure **actuelle** — imports, `regles/`, `recettes/`, avec un pas-à-pas |
| `src/plugins/sfDesastrePlugin/README.adoc` | 564 | la structure **d'avant** — un fichier `desastres.yml` unique |

Le README du plugin ne contient **aucune** occurrence de `recettes/`, `regles/` ni
`imports:`. La modularisation date du 2025-11-10 ; il n'a jamais suivi. Sa dernière
modification, en janvier 2026, était une conversion de format.

**Correction apportée à cette proposal après vérification** : une première rédaction le
disait « faux ». Il ne l'est pas. La forme monolithique qu'il documente **fonctionne
toujours** — vérifié en construisant un `sfDesastreManager` sur une configuration sans
imports : une recette lue, une règle lue, déclenchement obtenu. Le plugin accepte les deux
formes et les fusionne.

Il est donc **incomplet**, pas faux : il omet le mécanisme d'imports, qui est celui que ce
projet emploie, et c'est lui qu'on trouve d'abord en ouvrant le code puisqu'il y est posé à
côté. Quelqu'un qui le suit écrira une configuration qui marche, mais pas celle du projet —
et ne trouvera pas ses dix-neuf recettes là où le README dit qu'elles sont.

## What Changes

- **Écrire le verdict : on n'outille pas.** Pas de générateur, pas de squelette, pas de
  tâche `symfony`. La mesure ne le justifie pas, et l'écrire évite que la question soit
  reposée dans six mois sans les chiffres.

- **Désambiguïser les deux cartes, sans en supprimer le contenu unique.** La comparaison
  section par section a montré que le README porte ce que la page n'a pas : la référence
  d'API PHP — `sfDesastreManager`, `sfDesastreRuleEngine` — et la façon d'appeler le
  système depuis du code. Sept méthodes documentées, **deux écarts seulement** avec le
  code, tous deux un paramètre `sfContext` ajouté après coup.
  Le README garde donc ce qui lui est propre, corrige ses deux signatures, et cesse de
  décrire la configuration : il renvoie à la page pour cela, en disant que ce projet emploie
  la forme à imports.

- **Consigner le coût mesuré** — cinq fichiers, quatre répertoires, deux imports, deux
  schémas — pour que la prochaine personne qui posera la question parte d'un chiffre.

**Hors périmètre** : construire un générateur ; réécrire la page de documentation, qui est
juste ; écrire de nouveaux désastres, qui est la story 33.

## Capabilities

### Modified Capabilities

- `documentation-publiee` : une seule description de la façon d'écrire un désastre, et
  c'est celle qui est juste.

## Impact

- `src/plugins/sfDesastrePlugin/README.adoc` — réduit à un renvoi
- `docs/modules/ROOT/pages/desastres.adoc` — reste la référence, complétée du verdict
- Aucun code touché, aucun comportement modifié
