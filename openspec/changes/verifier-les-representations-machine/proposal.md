## Why

Le plan de release portait cette story sous le nom « spécifier les routes JSON non
couvertes ». **Cette moitié est faite** : `/post/md5/`, `/posts/next|prev|random` et le
comportement en erreur ont tous leurs scénarios, écrits en passant par les stories 1, 4 et
5 qui les ont traitées. La pagination n'en a pas, mais la story 2 est en attente.

L'audit du jour déplace donc le besoin. Les deux capacités de cet axe portent **59
scénarios**. Ce qui les vérifie : quinze assertions dans `postActionsTest.php`, dix sur le
type de contenu et le cache, et le test de contrat — qui ne regarde que le statut, le type
et la présence des champs de premier niveau.

Le mainteneur n'a donc pas un problème de description : **il a un problème de preuve.** Une
spécification que rien n'exerce est du même bois que la banque de mémoire supprimée — elle
dit vrai jusqu'au jour où elle ne le dit plus, et personne ne l'apprend.

## What Changes

- Un test fonctionnel par requirement non couvert des deux capacités de cet axe :
  - `formats-de-sortie` — la représentation XSPF d'une liste et son titre, la
    représentation Max/MSP et l'assainissement de ses champs, les champs jamais exposés,
    les formats annoncés sur une page de liste et de morceau.
  - `catalogue-morceaux` — l'ordre du catalogue, le filtrage par contributeur, la
    recherche par termes et l'exclusion des non-publiables, la navigation séquentielle et
    sa restriction à un contributeur, la forme complète de la réponse par empreinte.
- Chaque test nomme le scénario qu'il exerce, pour qu'un lecteur puisse aller de l'un à
  l'autre.
- Si un test **contredit** un scénario, l'écart est consigné et un change distinct est
  ouvert pour trancher. Ce changement déclare `skip_specs` : il ne peut pas amender une
  spécification, et c'est voulu — découvrir un désaccord et le trancher sont deux gestes,
  et le second mérite sa propre décision.

Le contrat public n'est pas concerné : aucune ligne de `src/apps` ni de `src/lib` n'est
touchée. Ce changement n'ajoute que des tests, et corrige les specs qu'ils démentiraient.

## Capabilities

### New Capabilities

Aucune.

### Modified Capabilities

Aucune, et le changement déclare `skip_specs` en conséquence — comme les deux changes de
couverture qui l'ont précédé sur `desastres`. Il n'ajoute que des tests d'un comportement
déjà spécifié et déjà en production.

## Hors périmètre

- **Les capacités tenues ailleurs** : `embarquement-oembed`, `desastres`,
  `flux-syndication`, `metadonnees-partage`, `ressources-statiques`, et le protocole
  d'écoute tierce, qui a ses 119 assertions.
- **La pagination**, dont la story est en attente : il n'y a rien à vérifier.
- **Toute correction de code.** Si un test révèle un défaut, il est consigné et laissé —
  le corriger est un autre changement, avec sa propre décision.
- **La couverture exhaustive des 59 scénarios.** Certains décrivent des invariants qu'un
  test fonctionnel n'atteint pas ; ceux-là seront nommés plutôt que simulés.

## Impact

- **Ajouté** : des fichiers de test sous `src/test/functional/frontend/`.
- **Non modifié** : tout le code applicatif, et les spécifications — `skip_specs` est
  déclaré.
- **Dépendances** : aucune. Les stories 1 à 5 sont livrées.
