## Context

`JsonApiFilter` a été écrit pour une migration vers JSON:API 1.0 qui n'a pas eu lieu et qui
a depuis été écartée sciemment. Il en reste l'en-tête, sans le corps qui l'aurait justifié.

Deux choses de ce changement ne vont pas de soi et sont écrites ici plutôt que laissées
implicites : la contrainte d'ordre qui a produit le filtre tel qu'il est, et le fait que
son retrait rompt un contrat public sans qu'aucun destinataire ne puisse s'y opposer.

## Goals / Non-Goals

**Goals :** que le type de contenu servi soit celui que la spécification exige, y compris
depuis le cache ; que le contrat OpenAPI soit amendé dans le même geste ; que les deux
surfaces exemptées ne bougent pas.

**Non-Goals :** la forme des corps JSON, `ApiResponse`, et tout autre écart consigné au
contrat.

## Decisions

### Retirer le filtre plutôt que d'y changer une chaîne

Remplacer `application/vnd.api+json` par `application/json` dans `JsonApiFilter` laisserait
en place un filtre qui, sur chaque réponse, réécrit un type par lui-même. Les actions
posent déjà le bon type — `setFormats()` pour `/posts` et `/post/{slug}`, `executeMd5()` et
`renderJsonPost()` pour les autres. Le filtre n'a jamais servi qu'à l'écraser.

Le retrait supprime aussi les deux exemptions qu'il portait : oEmbed et le module `rest` ne
sont plus des cas particuliers puisqu'il n'y a plus de règle générale à laquelle échapper.

### La contrainte d'ordre disparaît avec ce qu'elle contraignait

`filters.yml` déclare `json_api` **sous** `cache`, avec ce commentaire : « Le Content-Type
doit etre reecrit avant que `sfCacheFilter` n'ecrive l'entree, sinon la reponse mise en
cache porte le type d'origine. » La chaîne de filtres se déroule vers l'intérieur puis
remonte : au retour, un filtre déclaré plus bas s'exécute avant un filtre déclaré plus
haut. C'est un bug déjà corrigé une fois, sur `desastre`, qui est déclaré sous `cache` pour
la même raison.

Retirer le filtre supprime le besoin — mais **ne dispense pas de le démontrer**. Le type
est désormais posé par l'action, donc avant que `sfCacheFilter` n'écrive quoi que ce soit ;
c'est un raisonnement, pas une observation. Depuis `2026-08-18-activer-le-cache-en-test`,
l'environnement de test met en cache : la démonstration est possible, et elle est due.
`desastreCacheTest.php` en donne la forme — vérifier `sf_cache`, demander deux fois,
comparer.

### Ce que ce changement promulgue

<!-- incongru-voix: lessig — le type de contenu de six routes JSON, régulé par l'architecture seule — recours: aucun ; un signal devient possible, pas un recours -->

*Analyse tenue depuis une position réformiste déclarée : elle ne conteste pas qu'on puisse
changer ce type, elle demande par quelle voie un tiers pourrait s'y opposer.*

```
CONTRAINTE : six routes cessent de servir application/vnd.api+json
             — pour tout appelant qui filtre dessus, aucun identifié

  loi           rien. Aucune condition d'utilisation, aucune mention légale,
                aucun contrat d'usage. L'AGPLv3 régit le code, pas le service.

  norme         presque rien, et c'est là que quelque chose vient de changer.
                CHANGELOG.adoc s'adresse aux contributeurs du dépôt. Mais
                release-please est configuré, et `info.version` du contrat suit
                `src/VERSION` : un footer BREAKING CHANGE porterait le contrat
                de 1.11.0 à 2.0.0. La convention existe donc — sauf qu'elle
                n'a JAMAIS été exercée : zéro commit rupteur dans toute
                l'histoire du dépôt. Un tiers n'a pas pu l'apprendre.

  prix          il vient de baisser. Le plan de release écrivait « le coût de la
                rétro-ingénierie, et seulement pour qui s'aperçoit qu'il y a
                quelque chose à contourner ; il n'existe aucune documentation
                d'API publiée ». Ce n'est plus vrai depuis la veille : le
                contrat est publié, versionné, diffable. Constater la rupture
                coûte désormais une lecture, non une rétro-ingénierie.

  architecture  totale. Le filtre part, le type change, l'appelant ne négocie
                rien. Et le déploiement est automatique à la fusion sur `main` :
                la règle entre en vigueur sans même le délai que d'autres
                projets obtiennent par accident entre la fusion et la mise en
                ligne.

  RECOURS       aucun. Pas de version d'API négociée, pas d'en-tête de
                dépréciation, pas de délai, pas d'adresse à qui écrire.
```

### Un incrément majeur est un signal, pas un recours

C'est la distinction qui décide, et il ne faut pas la laisser se brouiller. Un recours est
une voie pour **contester** ; un signal dit seulement que la règle a changé, après qu'elle a
changé, et à condition de regarder. Porter le contrat à 2.0.0 ne rend à personne le droit
d'objecter, ne diffère rien et n'ouvre aucune exception.

Ce n'est pas rien pour autant : c'est ce qui sépare une rupture *constatable* d'une rupture
invisible. Et une convention de version est exactement la matière dont les normes sont
faites — une attente que rien n'oblige, que rien ne sanctionne, et que les gens tiennent
quand même.

**Mais le signal, tel quel, ne porte aucune information.** `info.version` suit `src/VERSION`,
que release-please incrémente à chaque publication — 1.11.0 vient de travaux sur les
désastres, pas de l'API. Un consommateur qui voit la version bouger ne peut pas distinguer
« l'API a changé » de « le site a publié quelque chose ». Le canal existe et il est
saturé de bruit.

Deux gestes, et le second est ce qui rend le premier utile :

1. **Livrer ce changement avec un footer `BREAKING CHANGE:`.** Le contrat passe à 2.0.0.
   Coût : une ligne de message de commit.
2. **Écrire la convention dans le contrat lui-même**, où le consommateur lit — un
   incrément majeur de `info.version` signale une rupture des représentations décrites.
   Coût : trois lignes.

Sans le second, le premier est du bruit de plus. Avec les deux, un tiers peut se doter
d'une attente vérifiable. C'est tout ce que ce projet peut lui offrir.

**Décision de l'auteur, à l'implémentation : pas de bascule majeure.** Le premier geste
n'est donc pas posé. Le second l'a été puis retiré, parce qu'annoncer une convention qu'on
n'applique pas dès sa première occasion vaut moins que de ne rien annoncer. Ce que le
contrat dit désormais est ce qui est vrai : `info.version` ne signale pas les changements
de cette API, et le seul canal est le diff du document.

Le résultat net de cette analyse est donc : **aucun signal, aucun recours**. Elle aura servi
à ne pas inscrire dans le contrat une promesse que le projet ne tient pas — ce qui est un
résultat modeste, et c'est le seul qu'elle pouvait produire une fois la décision prise.

### Le point d'inconfort

Cette analyse aboutit à quatre lignes à écrire, pour un coût nul, et elle laisse intacte la
décision de rompre. C'est-à-dire qu'elle est confortable pour celui qui détient déjà le
pouvoir de décider — elle transforme une décision unilatérale en une décision unilatérale
mieux étiquetée. La finesse du tableau ne doit pas masquer que la ligne RECOURS reste vide,
et qu'aucun des deux gestes proposés ne la remplit.

La question qui suit toujours, formulée comme une règle plutôt que comme une tâche :

> « À compter de la prochaine poussée, tout appelant de `/posts?format=json` qui filtre sur
> `application/vnd.api+json` cessera de reconnaître les réponses, sans préavis et sans
> moyen de s'en plaindre — mais il pourra, s'il y pense, constater après coup que le
> numéro de version majeur a bougé. »

L'aurait-on votée ainsi ? On ne le sait pas, parce que personne n'a été mis en position de
la voter. Ce qui ne rend pas le statu quo innocent : servir un type de contenu que la
spécification interdit est aussi une règle imposée sans consentement.

### La rupture est assumée et rendue lisible, faute de pouvoir être négociée

Un appelant qui filtre sur `application/vnd.api+json` cesse de reconnaître les réponses. Il
n'existe aucun canal vers lui : pas de conditions d'utilisation, pas de version d'API, pas
d'en-tête de dépréciation, et le déploiement est automatique à la fusion sur `main`. La
règle entre en vigueur sans préavis et sans le délai que d'autres projets obtiennent par
accident entre la fusion et la mise en ligne.

Ce qui existe depuis la veille, et qui est le seul recours dont ce projet dispose : **le
diff du contrat**. Six déclarations passent de `vnd.api+json` à `json` dans un document
versionné, daté et vérifié. La rupture passe d'invisible à coûteuse à ignorer. Ce n'est pas
un recours ; c'est un progrès sur l'absence de trace.

Aucun consommateur du JSON n'a jamais été identifié — question ouverte n°3 du plan de
release. Cela ne rend pas la rupture innocente, cela rend son coût inconnu.

### Le contrat est amendé dans le même changement, pas après

Le test de contrat échoue à la seconde où le filtre disparaît si le document n'est pas
corrigé. C'est le dispositif fonctionnant comme prévu : il rend l'amendement obligatoire
plutôt que facultatif. La prose du contrat qui annonce l'écart doit disparaître avec
l'écart — un document qui continuerait de signaler un écart corrigé serait faux dans
l'autre sens.

## Risks / Trade-offs

- **Un consommateur non identifié casse** → aucune atténuation possible, seulement la
  lisibilité du diff. C'est la conséquence assumée d'une API sans contrat pendant des
  années ; elle est nommée plutôt que masquée.
- **Le type ne survit pas au cache, contre toute attente** → c'est exactement ce que la
  vérification est là pour attraper, et pourquoi elle n'est pas facultative.
- **`restActionsTest.php` porte une assertion dont le message nomme un filtre disparu** →
  l'assertion reste vraie et utile (le protocole conserve son type) ; seul son libellé doit
  cesser de désigner une classe qui n'existe plus.

## Migration Plan

Aucune migration technique. Le retour en arrière consiste à restaurer trois fichiers et une
entrée de configuration.

## Open Questions

Aucune. La question ouverte n°3 du plan — qui consomme le JSON — reste sans réponse, et ce
changement est pris en sachant qu'elle l'est.
