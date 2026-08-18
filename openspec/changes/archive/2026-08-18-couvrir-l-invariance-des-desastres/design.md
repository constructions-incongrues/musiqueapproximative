## Context

Tester une propriété d'invariance sur un système aléatoire pose un problème simple : si
rien ne se déclenche, l'assertion « les deux visites portent les mêmes recettes » est vraie
à vide. Le test passerait sans rien démontrer.

## Goals / Non-Goals

**But.** Couvrir les quatre scénarios d'invariance observables depuis une réponse HTTP.

**Non-buts.** Le tirage côté navigateur, les propriétés statistiques, l'instrumentation du
moteur de règles.

## Decisions

### La non-vacuité vient du forçage, pas d'une fixture ajoutée

Sur les fixtures actuelles, un désastre ne se déclenche qu'environ une fois sur six sans
forçage — les six règles à `probability: 1` visent `danse`, `kraftwerk`, `sale`, `TTS`,
`Spooky Mix` et `catani`, qu'aucun morceau de fixture ne porte.

Deux façons d'obtenir du déterminisme :

- ajouter un morceau de fixture dont le titre déclenche une règle certaine. Écarté :
  `data/fixtures/subsonic.sql` est partagé, et `PostTableSubsonicTest` affirme des
  identifiants et des comptes précis. Ajouter une ligne casserait des tests sans rapport ;
- forcer une règle par son déclencheur. Retenu. Le forçage ignore condition et probabilité,
  et le déclencheur fait partie de la clé de cache — ce que le test vérifie aussi.

Le fichier ouvre donc sur une visite forcée, dont il vérifie qu'elle porte bien des
recettes. Si cette assertion tombe, toutes les suivantes deviennent suspectes, et le test
le dit.

### L'invariance s'affirme sur l'égalité, jamais sur la présence

Les assertions comparent l'ensemble des recettes de deux réponses. Elles ne demandent pas
qu'un désastre précis se déclenche : c'est ce qui rend le test robuste au tirage, et à une
évolution de la configuration livrée.

L'ensemble est extrait des chemins `/desastres/<nom>/` présents dans le HTML servi, trié
puis dédoublonné. C'est ce que le visiteur reçoit, pas un détail d'implémentation.

### « Deux visiteurs » se teste par deux `sfBrowser`, et par le compte d'entrées

Deux instances de `sfBrowser` ont des sessions distinctes mais partagent le répertoire de
cache. Le test vérifie deux choses : que les recettes servies sont les mêmes, et
qu'**aucune entrée de cache nouvelle** n'a été écrite. La seconde est la plus forte : elle
prouve que la réponse vient bien du cache, et pas d'une seconde production qui aurait tiré
au sort la même chose.

### « Une même adresse dans le temps » se simule en vidant le cache

La durée de vie déclarée est de 86 400 secondes. Attendre n'est pas une option, et
manipuler l'horloge non plus. Vider le répertoire de cache place le système dans l'état
qu'il aurait après expiration : la page est reproduite, une entrée est réécrite. La clause
« l'effet servi peut différer » n'est pas testée — c'est une permission, pas une
obligation.

## Risks / Trade-offs

- **Le test dépend du réglage de cache d'un autre changement.** S'il disparaît, ce fichier
  échoue. C'est voulu : sa première assertion vérifie `sf_cache` et le dit en clair.
- **L'extraction par expression régulière sur le HTML est fragile.** Si l'arborescence
  web des désastres change, l'extraction cesse de trouver quoi que ce soit et les
  assertions d'égalité deviennent vraies à vide. La première assertion, qui exige des
  recettes non vides sur la visite forcée, est le garde-fou contre ce cas.

## Open Questions

Aucune.
