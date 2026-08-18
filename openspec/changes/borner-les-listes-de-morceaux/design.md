## Context

`executeList` sert quatre représentations à partir d'une seule requête non bornée. La
limite existe déjà dans `buildOnlinePostsQuery` — `max(0, (int) $count)`, puis `->limit()`
si positif — et n'est jamais passée par cette action. Le décalage, lui, n'existe pas.

Trois particularités du code décident de la forme de ce changement, et aucune n'était
prévue au plan de release.

## Goals / Non-Goals

**Goals :** que la réponse par défaut soit bornée à 50 dans toutes les représentations ;
qu'un consommateur puisse demander une autre tranche et connaître le total ; que le
catalogue entier reste atteignable ; que les libellés cessent de compter ce qu'ils servent
au lieu de ce qui existe.

**Non-Goals :** l'interface de pagination, le XSPF (story 3), l'index de recherche, la
forme des corps.

## Decisions

### La recherche et le catalogue ne se bornent pas par le même mécanisme

`PostTable::search()` ne renvoie pas une requête mais un **tableau PHP** : elle interroge
l'index, puis rappelle la base morceau par morceau et empile les publiables. Une limite SQL
ne s'y applique pas — il faut découper le tableau.

Conséquence à ne pas manquer : borner ce tableau **ne réduit pas le travail**, seulement la
réponse. Les morceaux sont déjà tous chargés quand on les coupe. Le gain sur le chemin de
recherche est donc en octets transmis, pas en temps de calcul, à l'inverse du chemin du
catalogue où la limite descend jusqu'au SQL.

C'est une asymétrie réelle, et elle n'est pas corrigée ici : la corriger demanderait de
borner dans l'index, ce qui est un changement de modèle. On la nomme plutôt que de laisser
croire que le bornage rend partout le même service.

### Les libellés doivent compter le total, pas la tranche

`executeList` écrit « %d résultat(s) pour la recherche » et « %s a posté %d morceau(x) à ce
jour » à partir de `count($posts)`. Bornés à 50, ces titres annonceraient « 50 résultats »
là où il y en a 993, et « a posté 50 morceaux à ce jour » à un contributeur qui en a posté
993. Ce serait un mensonge produit par le correctif — le bornage n'a pas à changer ce que
le site affirme sur ce qu'il contient.

`countOnlinePosts()` existe déjà et n'est appelé que par `executeShow`. C'est un second
appel à la base, à assumer : compter et servir une tranche sont deux questions.

### Le défaut s'applique aussi au HTML, et l'interface ne suit pas

Le paramètre se pose dans `executeList`, qui sert **tous** les formats. Exclure la page HTML
reviendrait à ne pas brancher un paramètre là où il est déjà écrit — et c'est la page HTML
qui porte la douleur du DJ de soirée, 3,7 Mo sur le réseau d'une salle.

Mais l'interface de pagination est en « Could ». Entre ce changement et elle, **le visiteur
qui veut voir au-delà du cinquantième morceau n'a aucun bouton pour le faire** : il lui faut
connaître `offset`. C'est un demi-service, assumé, et le plan de release le consigne comme
argument pour promouvoir l'interface juste après.

L'alternative — borner le JSON et laisser le HTML entier — a été écartée en révision du plan
pour une raison qui tient toujours : c'est la même action et le même paramètre.

### Une demande inintelligible retombe sur le défaut, elle n'échoue pas

`buildOnlinePostsQuery` traite déjà un compte négatif par `max(0, ...)`, c'est-à-dire par
l'absence de limite. Ce comportement ne peut pas être conservé tel quel : un `limit=-1`
servirait alors les 8 097 morceaux, et le bornage se contournerait par accident.

Une valeur négative, non numérique ou absurde SHALL donc ramener au défaut, non à l'absence
de limite. C'est la seule lecture qui rende le défaut tenable.

## Ce que ce changement promulgue, et pour qui

<!-- incongru-voix: lessig — 8 047 morceaux retirés de la page /posts, régulé par l'architecture seule — recours: aucun ; notification: aucune pour le visiteur, un diff pour l'intégrateur -->

*Analyse tenue depuis une position réformiste déclarée : elle ne conteste pas le bornage,
elle demande si celui qui le subit peut seulement savoir qu'une règle lui a été appliquée.*

Le plan de release a rempli une table pour « l'appelant qui ne demande rien ». Il n'en
avait qu'un en tête : l'intégrateur. Cette story en crée un second, que cette table ne
couvre pas — **le visiteur de la page HTML**. Les deux ne reçoivent pas la même chose.

```
CONTRAINTE : /posts cesse de montrer 8 047 des 8 097 morceaux

                  l'intégrateur                    le visiteur HTML

  loi             rien                             rien
                  aucune CGU, aucun contrat        idem

  norme           rien                             rien
                  aucun canal d'annonce            idem

  prix            lire ~400 lignes de YAML         infini — il n'existe aucun
                  le contrat décrit sa route       document décrivant la page HTML,
                  et déclarera `limit`/`offset`    et le contrat ne la couvre pas

  architecture    totale                           totale
                  `executeList` décide             idem

  RECOURS         aucun                            aucun
  NOTIFICATION    le diff du contrat               AUCUNE
                  s'il pense à le consulter        il ne peut pas savoir
```

### La ligne qui compte est celle que le plan n'avait pas

Ce n'est pas la ligne RECOURS — elle est vide pour les deux, et elle le restera. C'est la
ligne **NOTIFICATION**, et l'écart y est total.

L'intégrateur reçoit un corps fini qu'il peut compter, et un document versionné qui déclare
`limit` avec son défaut. Il peut établir qu'une règle existe, même sans pouvoir la
contester.

Le visiteur reçoit une page de cinquante liens. **Rien ne la distingue d'un catalogue
complet de cinquante morceaux.** Il ne conclura pas « on m'a borné », il conclura « c'est
tout ce qu'il y a » — ou, s'il sait qu'il y en a plus, « le site est cassé ». C'est la
propriété qui définit la régulation par l'architecture : elle s'applique avant, sans procès
et sans notification, et le régulé n'apprend pas qu'une règle vient de le viser.

Un défaut est la loi la plus puissante qui soit, parce que personne ne le lit et que presque
personne ne le change. Ici il est pire que d'habitude : le visiteur ne peut même pas
apprendre qu'il existe un défaut à changer.

### Ce que ça décide

**La page doit dire au visiteur qu'il ne voit qu'une partie, et de quoi.** Pas une interface
de pagination — elle reste en « Could », et ce changement ne la fait pas. Simplement le fait
que la page annonce le total dont ces cinquante morceaux sont extraits.

Le coût est nul : le total est **déjà calculé** par ce changement, puisque les tâches 4.1 et
4.2 l'exigent pour que les titres cessent de mentir. L'afficher est une ligne de gabarit.

Ce n'est pas un recours. C'est une notification, c'est-à-dire la condition de possibilité de
tout recours — sans elle, il n'y a personne pour se plaindre, faute de savoir qu'il y a
matière. Elle devient une exigence de spécification plutôt qu'une case à cocher : une
notification qu'on peut oublier au moment de livrer n'en est pas une.

### Le point d'inconfort

Cette analyse conclut à une ligne de gabarit, pour un coût nul, et laisse intacte la
décision de borner et celle de reporter l'interface de pagination. C'est-à-dire qu'elle est
confortable pour qui décide déjà — elle rend la contrainte visible sans la rendre
contestable, et « au moins on l'a dit » est le réconfort le moins cher du droit.

La conclusion inconfortable est ailleurs et elle n'est pas la mienne à prendre : **si le
visiteur ne peut pas atteindre le catalogue au-delà de cinquante morceaux, l'interface de
pagination n'est pas un « Could », c'est la seconde moitié de ce changement.** Le plan la
sépare pour de bonnes raisons de découpage ; la question est de savoir si ces raisons sont
bonnes pour le visiteur ou seulement pour celui qui découpe.

Et il faut dire ce qui empêche cette analyse d'être un plaidoyer pour le statu quo : servir
3,7 Mo et 8 097 liens à quelqu'un sur le réseau d'une salle est **aussi** une règle imposée
sans consentement, et personne ne l'a votée non plus. Il n'existe pas d'état non régulé. La
seule question est laquelle des deux règles on écrit, et si celui qui la subit peut la voir.

## Risks / Trade-offs

- **Un appelant existant reçoit 50 morceaux au lieu de 8 097 sans préavis** → aucune
  atténuation ; le contrat rend la rupture lisible dans un diff, et c'est tout ce dont ce
  projet dispose. Voir le tableau des modalités dans `openspec/discovery.md`.
- **Le visiteur perd l'accès visuel au catalogue au-delà de 50** → réel, temporaire,
  et nommé. Il ne devient définitif que si l'interface de pagination n'est jamais faite.
- **Le second appel pour compter coûte une requête de plus par liste** → un `COUNT` sur un
  index existant, contre 8 047 morceaux qu'on cesse de charger. Le compte est largement
  favorable.
- **Le gain sur le chemin de recherche est en octets, pas en temps** → nommé ci-dessus,
  non corrigé ici.

## Migration Plan

Aucune migration. Le retour en arrière consiste à ne plus passer les paramètres.

## Open Questions

Aucune. La valeur par défaut — 50 — a été tranchée par l'auteur le 2026-08-18, alignée sur
le `count` de `/posts/feed`.
