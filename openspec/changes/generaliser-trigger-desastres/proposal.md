## Why

`desastres` est la seule capacité du corpus dont le comportement ne se vérifie pas de
l'extérieur. Sur les 78 scénarios spécifiés, environ 68 s'assèrent par une simple requête
HTTP ; ceux de `desastres` échappent au procédé, parce que quatorze règles sur vingt sont
probabilistes et que rien ne permet de les forcer.

Or **le mécanisme qui les forcerait existe déjà.** Le paramètre `trigger`, documenté dans
`README-TRIGGER.adoc`, déclenche une règle depuis l'URL en court-circuitant *et* sa
condition *et* sa probabilité :

```php
if ($triggerMatch) {
  // Trigger present : application systematique
  $shouldApply = true;
}
```

Il n'a pas été conçu pour tester — il permettait de déclencher le jinglist à la demande —
mais c'est exactement le geste requis. **Il est branché sur une règle sur vingt.**

La démonstration de ce que coûte son absence vient d'être faite : trois chemins d'import
invalides ont privé le site de quatre règles pendant sept mois, sans que rien ne le
signale. Un `trigger` par règle aurait transformé ce diagnostic en une requête.

## What Changes

- **Chaque règle de désastre déclare un `trigger`.** Dix-neuf règles en reçoivent un ;
  `tts_jinglist` conserve le sien.
- Une convention de nommage est fixée, pour que le nom du déclencheur se déduise de la
  règle plutôt que de s'inventer au cas par cas.
- `README-TRIGGER.adoc` cesse de décrire une fonctionnalité ponctuelle pour décrire une
  propriété de la configuration : toute règle est forçable.
- **Aucun changement de comportement en production.** Un `trigger` n'a d'effet que si son
  paramètre figure dans l'URL demandée. Sans lui, la règle s'évalue comme aujourd'hui :
  même condition, même probabilité, même résultat.

### Ce que cela rend possible

```
  GET /post/:slug?kraftwerk   →  la recette kraftwerk est-elle injectée avant </head> ?
  GET /post/:slug?quickos     →  et celle-ci, un 3 août ?
  GET /post/:slug             →  aucune des deux, sauf tirage
```

Le filtre `sfDesastreFilter` injecte avant `</head>` dans les réponses `text/html`. Le
résultat est donc lisible dans le corps de la réponse, sans rien instrumenter, sans base
de test, sans framework — ce qui compte sur un socle Symfony 1.5 dépourvu de suite de
tests.

La capacité `desastres` rejoint ainsi les cinq autres : vérifiable par requête. C'est le
dernier obstacle à ce que le corpus de specs devienne un filet exécutable pour la
migration du socle, qui est sa raison d'être.

### Approche

Le choix de généraliser un mécanisme existant plutôt que d'en introduire un autre —
variable d'environnement, mode de test, graine `mt_srand()` fixée — tient au socle.
Symfony 1.5 n'offre pas d'injection de dépendances exploitable ici, et `mt_rand()` est
appelé directement dans `sfDesastreManager::findRecettes()`. Rendre cette ligne
paramétrable supposerait de toucher au moteur ; `trigger` la contourne sans y toucher, et
il est déjà écrit, déjà documenté, déjà éprouvé sur une règle.

La contrepartie est assumée : les déclencheurs sont **publics**. N'importe quel visiteur
peut forcer n'importe quel désastre en devinant un paramètre d'URL. Sur un site dont la
raison d'être est le glitch et l'anarchie, ce n'est pas une faille — c'est au pire un jeu,
et sans doute une bonne chose. Aucun désastre n'expose de donnée ni ne modifie d'état ;
le pire cas est une redirection que le visiteur a lui-même demandée.

## Hors périmètre

- **L'écriture de la suite de vérification elle-même.** Ce changement rend `desastres`
  vérifiable ; il ne construit pas l'outil qui la vérifie. Cet outil concernerait les six
  capacités et non celle-ci seule, et mérite son propre changement.
- Le moteur `sfDesastreRuleEngine`, qui n'est pas modifié. Il reste une fonction pure de
  `query` et `context`, directement testable sans framework — c'est d'ailleurs ce qui
  couvre les scénarios que `trigger` n'atteint pas.
- Les probabilités, les conditions et les recettes, qui ne bougent pas d'un cheveu.
- La correction des trois imports invalides et le doublon `postillons_mort`, traités par
  `reparer-imports-desastres`. **Ce changement suppose ce correctif appliqué** : ajouter un
  `trigger` à une règle qui ne se charge pas ne produirait rien.
- Le rendu des désastres côté client — JavaScript, CSS, effets. `trigger` porte sur la
  sélection des recettes, pas sur leur exécution dans le navigateur.

## Capacités

### Nouvelles capacités

Aucune.

### Capacités modifiées

- `desastres` — le corpus décrit aujourd'hui le déclenchement conditionnel et la part
  d'aléatoire (« Requirement: Déclenchement conditionnel », « Requirement: Part
  d'aléatoire ») sans mentionner qu'une règle puisse être forcée. Une exigence est ajoutée
  sur le forçage par paramètre d'URL, et la garantie que ce forçage n'altère rien en son
  absence.

## Impact

- Les sept fichiers de `src/apps/frontend/config/desastres/regles/` : un champ `trigger`
  par règle.
- `src/apps/frontend/config/desastres/schemas/regles.schema.json`, si le champ doit y
  devenir obligatoire — arbitrage laissé à l'implémentation.
- `src/plugins/sfDesastrePlugin/README-TRIGGER.adoc` : portée du document.
- **Contrat public inchangé en l'absence des paramètres.** Aucune route, aucun format,
  aucune métadonnée. Une page demandée sans paramètre de déclenchement se comporte
  exactement comme aujourd'hui.
- Le dépôt gagne vingt paramètres d'URL non documentés côté visiteur, qui forcent chacun un
  effet. Assumé, et cohérent avec l'esprit du site.
