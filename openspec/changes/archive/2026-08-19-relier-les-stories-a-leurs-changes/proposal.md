## Why

Le 2026-08-18, trois stories livrées sont restées décochées : leur change avait été nommé
autrement que leur packet — `corriger-le-contexte-projet` pour `corriger-le-contexte-openspec`,
et ainsi de suite. **Le plan comptait dix-neuf stories livrées en croyant en compter seize.**

Ce n'est pas du rangement. Un plan qui affiche une story ouverte alors qu'elle est faite
envoie quelqu'un refaire le travail ; un plan qu'il faut auditer à la main pour être cru ne
sert plus à décider.

## Ce que la mesure a montré, et qui déplace le problème

Le packet supposait qu'il fallait inventer un lien. **Il existe déjà** : les packets portent
une ligne `**Change** : <nom>`. Relevé sur le plan tel qu'il est :

| | |
| --- | --- |
| stories cochées dont le change **résout** | **12** |
| stories cochées **sans lien exploitable** | **9** |
| stories cochées dont la ligne **ne résout pas** | **0** |

L'implémentation a affiné ce relevé, et l'affinage compte : sur les neuf, **quatre** n'ont
aucune ligne, et **cinq** en portent une restée à sa valeur de gabarit — `_pas encore
proposé_` — des jours après la livraison. Le défaut n'est donc pas seulement une ligne
absente, c'est **une ligne jamais mise à jour**, ce qui est plus difficile à voir : le packet
a l'air complet.

Dans les deux cas, **la convention n'est pas tenue une fois sur deux**. Et elle ne l'est pas par l'assistant, qui a
écrit la plupart de ces packets — dont ceux des stories livrées ce jour même.

Une convention que rien ne vérifie est une convention qu'on applique quand on y pense.

## What Changes

- Un contrôle échoue lorsqu'une story cochée ne déclare pas de change, ou déclare un change
  qui n'existe ni parmi les actifs ni parmi les archives.
- Il **nomme la story** en cause. Un écart global se contourne ; un écart nommé se corrige.
- Les neuf déclarations manquantes sont ajoutées, sans quoi le contrôle échouerait dès sa
  première exécution.

## L'asymétrie du contrôle, et pourquoi elle est délibérée

Le contrôle **n'exige pas** qu'un change archivé soit référencé par une story. La mesure dit
pourquoi : **20 des 50 archives ne sont citées nulle part**, et toutes sont antérieures au
plan — datées des 1er et 7 août, quand le plan date du 18. Exiger la réciproque produirait
vingt fausses alertes permanentes, c'est-à-dire un contrôle qu'on désactive.

Du travail hors plan est par ailleurs légitime, et il est déjà nommé dans le Change Log.

## Hors périmètre

- **Deviner la correspondance quand les noms diffèrent.** Le packet déclare son change ; on
  ne l'infère pas. Une heuristique de rapprochement se tromperait en silence, ce qui est
  exactement le défaut qu'on corrige.
- **Détecter un packet dont les chiffres ont vieilli.** C'est l'autre défaut de la journée —
  un « 3,1 s » de sept mois a promu une story en Must alors que la mesure disait l'inverse,
  et trois autres packets se sont révélés faux sur leur cause. **Aucun outil ne rattrape
  ça** : un chiffre périmé est syntaxiquement identique à un chiffre juste. Ce change ne
  prétend pas y toucher, et le dire est la moitié de son honnêteté.
