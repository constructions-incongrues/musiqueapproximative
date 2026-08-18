## Context

Les deux capacités de cet axe portent 59 scénarios. Cinq stories livrées les ont écrits ou
complétés ; aucune n'avait pour objet de les vérifier, et chacune n'a couvert que ce qu'elle
changeait. Le résultat est une spécification dense adossée à une preuve mince.

Le dépôt a déjà supprimé une documentation qui avait dérivé jusqu'à décrire cinq routes
inexistantes. Une spécification que rien n'exerce court exactement ce risque, avec une
aggravation : elle est validée par l'outil, ce qui lui donne l'air d'être tenue.

## Goals / Non-Goals

**Goals :** qu'un scénario faux fasse échouer la suite ; qu'un lecteur puisse aller du
scénario au test qui l'exerce ; nommer ce qui reste hors d'atteinte plutôt que le simuler.

**Non-Goals :** corriger le code, amender les specs, couvrir les capacités tenues ailleurs.

## Decisions

### Un test par requirement, nommé d'après lui

Le découpage suit les requirements de la spécification, pas les routes ni les fichiers de
code. C'est ce qui permet à un lecteur de vérifier la couverture en comparant deux listes,
au lieu de la déduire.

Chaque assertion porte dans son libellé le nom du scénario qu'elle exerce. Un échec dit
alors quelle promesse a cessé d'être tenue, et non seulement quelle ligne a bougé.

### Ce qu'un test fonctionnel n'atteint pas est nommé, non simulé

Certains scénarios décrivent des invariants qu'une requête ne peut pas observer — « aucune
dépendance absente de l'environnement ne peut le faire échouer silencieusement » en est un.
Les couvrir demanderait de fabriquer un environnement dégradé, c'est-à-dire de tester le
harnais plutôt que le site.

Ceux-là sont **listés dans les tâches, avec leur raison**. Une couverture qui prétend être
complète en simulant ce qu'elle n'atteint pas est pire qu'une couverture partielle honnête :
la première ment sur son étendue, la seconde la déclare.

### Un désaccord entre test et spécification ne se tranche pas ici

Le comportement décrit est en production depuis des années. Si un test le dément, deux
lectures s'ouvrent : la spécification a mal décrit, ou le code a dérivé sans que personne
s'en aperçoive. Choisir demande de savoir ce qui était voulu — une question de produit, pas
de test.

`skip_specs` est donc déclaré, et il n'est pas un raccourci : il **empêche** ce changement
de refermer un désaccord qu'il vient d'ouvrir. L'écart est consigné, un change distinct le
tranche.

### Les fixtures doivent porter les cas décrits

Les scénarios parlent de morceaux hors ligne, à publication future, sans identifiant d'URL,
de plusieurs contributeurs, de champs contenant des guillemets et des retours à la ligne. Un
test ne peut vérifier que ce que les fixtures contiennent.

Là où elles ne portent pas le cas, deux issues : l'enrichir, ou déclarer le scénario non
couvert. **Enrichir les fixtures ne doit pas casser les tests existants** — la suite Subsonic
en dépend, et ses 119 assertions comptent des morceaux et des artistes.

## Risks / Trade-offs

- **La suite s'allonge sensiblement** → elle passe en une minute environ aujourd'hui ; le
  coût est acceptable et le rendement direct, puisque c'est le seul filet du contrat public.
- **Un test mal écrit fige un défaut au lieu de le révéler** → chaque test doit être vu
  échouer avant d'être accepté, sur une spécification qu'on fausse temporairement.
- **Enrichir les fixtures casse la suite Subsonic** → les modifications de fixtures sont
  additives, et la suite complète est relancée après chacune.
- **Le désaccord découvert n'est jamais tranché** → risque réel. Il est atténué en le
  consignant dans le plan de release, non seulement dans les tâches du change.

## Migration Plan

Aucune. Le changement n'ajoute que des tests.

## Open Questions

Aucune à ce stade. Les désaccords éventuels entre test et spécification en produiront, et
c'est leur intérêt.
