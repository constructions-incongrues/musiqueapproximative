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

**Il est faux depuis neuf mois — exactement la période sans un seul désastre neuf.** Et
c'est celui-là que trouve d'abord quelqu'un qui ouvre le code : il est posé à côté.

## What Changes

- **Écrire le verdict : on n'outille pas.** Pas de générateur, pas de squelette, pas de
  tâche `symfony`. La mesure ne le justifie pas, et l'écrire évite que la question soit
  reposée dans six mois sans les chiffres.

- **Supprimer la carte fausse plutôt que la réparer.** Deux documents de 1 162 lignes qui
  se contredisent coûtent plus que zéro. Le README du plugin devient un renvoi court vers
  la page de documentation, qui est juste, versionnée et publiée.

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
