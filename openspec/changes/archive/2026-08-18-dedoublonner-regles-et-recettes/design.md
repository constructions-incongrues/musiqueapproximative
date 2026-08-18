## Context

Deux dédoublonnages distincts, à deux niveaux différents, avec un symptôme commun. Les
traiter comme un seul problème conduirait à corriger le mauvais endroit.

```
  fichiers importés ──▶ processImports() ──▶ config['regles']
                             ▲                     │
                    dédoublonnage 1                │
                    (règles identiques)            ▼
                                            findRecettes()
                                                   ▲
                                          dédoublonnage 2
                                          (recettes désignées
                                           par plusieurs règles)
                                                   │
                                                   ▼
                                    applyRecettesToResponse()
```

Le premier porte sur la déclaration, le second sur la sélection. Une règle dupliquée
produit aussi une recette dupliquée, mais l'inverse est faux : deux règles **différentes**
peuvent légitimement désigner la même recette, et c'est le cas nominal quand deux
conditions se recouvrent.

## Goals / Non-Goals

**Buts.** Qu'une règle identique déclarée deux fois ne soit évaluée qu'une fois. Qu'une
recette désignée plusieurs fois n'enrichisse la réponse qu'une fois.

**Non-buts.** Introduire un identifiant de règle. Réexaminer l'ordre d'évaluation. Toucher
au cache ou au tirage.

## Decisions

### « Règle identique » se compare sur condition, probabilité, recettes **et déclencheur**

**Décision révisée pendant l'implémentation.** Elle disait d'abord l'inverse : exclure
`trigger`, au motif que deux déclarations de la même règle avec des déclencheurs différents
restent la même règle du point de vue du tirage. Le raisonnement était juste et la
conséquence mauvaise.

Le test l'a montré sur une configuration à trois règles, dont deux ne différaient que par
leur déclencheur :

```
avant                                    après, trigger exclu
règle 0 : premiere, trigger AUCUN        règle 0 : premiere, trigger AUCUN
règle 1 : premiere, trigger partage      ✗ absorbée
règle 2 : seconde,  trigger partage      règle 1 : seconde,  trigger partage
```

La survivante est celle qui **n'a pas** de déclencheur. Le dédoublonnage supprimait donc un
déclencheur, en violation directe de l'exigence « Couverture des déclencheurs », qui veut
qu'aucun désastre ne soit observable seulement par tirage. Il rendait la configuration
moins conforme qu'avant de passer.

**Retenu** : `trigger` fait partie de la signature. Le cas réaliste de duplication — un
bloc de règle copié tel quel d'un fichier à l'autre, déclencheur compris — reste attrapé.

**Coût assumé** : une règle dupliquée portant deux déclencheurs *différents* garde son
cumul de probabilité. Cas de figure improbable, et déjà signalé par l'exigence d'unicité
des déclencheurs.

**Écarté** : faire survivre le déclencheur de n'importe quelle occurrence qui en portait
un. `trigger` est un scalaire ; deux occurrences aux déclencheurs différents obligeraient à
en choisir un arbitrairement, ou à changer la forme du champ.

**Écarté** : comparer les règles par sérialisation complète. Cela ferait dépendre l'unicité
de l'ordre des clés YAML, donc de la façon d'écrire le fichier plutôt que de son sens.

### Le dédoublonnage des règles a lieu au chargement, pas à l'évaluation

`processImports()` est le seul endroit qui voit les deux fichiers. Dédoublonner à
l'évaluation obligerait `findRecettes()` à reconnaître qu'une règle déjà vue est la même,
à chaque requête, pour un résultat identique et un coût répété.

### La première occurrence gagne, à son rang

L'exigence existante dit que « l'ordre de déclaration détermine l'ordre d'évaluation ».
Retenir la première occurrence à son rang d'origine est la seule lecture compatible : la
seconde disparaît sans décaler ce qui la précède. Le nouveau scénario « Rang de la recette
retenue » fige ce choix.

### Le dédoublonnage des recettes a lieu dans `findRecettes()`, pas à l'injection

`applyRecettesToResponse()` reçoit une liste déjà arrêtée ; y dédoublonner masquerait le
problème sans le corriger, et laisserait tout autre appelant de `findRecettes()` — dont les
tests — voir des doublons. La liste retournée doit être juste.

## Risks / Trade-offs

- **Un désastre pouvait dépendre de sa double application.** Aucune recette livrée n'est
  dans ce cas à première vue, mais la vérification est portée en tâche plutôt que
  supposée.
- **Le dédoublonnage rend une classe de faute silencieuse.** Une règle recopiée par erreur
  dans deux fichiers cessera de se voir. L'exigence « Couverture des déclencheurs » couvre
  déjà la conformité de la configuration ; l'étendre à la duplication déborderait de ce
  changement.

## Open Questions

Aucune. Les deux comportements attendus sont écrits dans la spec, et le test qui les
constate absents existe déjà.
