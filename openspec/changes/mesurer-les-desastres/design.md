## Context

Dix-neuf recettes, dix-neuf règles, et rien qui dise laquelle sort. Ce document part de ce
qui a été mesuré avant d'être écrit, parce que quatre packets de cette release ont été
falsifiés pour avoir été rédigés à partir de ce qui est lisible.

**L'inventaire est sain.** 19 recettes, 19 règles, aucune référence en l'air dans un sens
ni dans l'autre, aucune recette désactivée. `shared` est le seul répertoire de
`web/desastres/` qu'aucune recette ne nomme, et c'est sa raison d'être. Les probabilités
vont de 0,1 à 1 : `0,1` ×2, `0,3` ×1, `0,5` ×2, `0,7` ×8, `1` ×6.

**Il n'existe aucune trace.** Les deux seuls `error_log` du plugin signalent un fichier
d'import manquant. Aucun compteur, aucun en-tête.

**Le cache sert sans réexécuter l'action.** Mesuré sur une page dont une règle certaine se
déclenche : 0,146 s à la production, 0,032 s ensuite, document identique à l'octet près.
`cache.yml` déclare `with_layout: true` et `lifetime: 86400`.

**La recette est déjà lisible dans le corps**, mais par accident : sur cette page, le mot
`splitouine` apparaît cinq fois — dans la charge utile injectée. C'est un indice qui dépend
de ce que la recette injecte, pas une déclaration.

## Goals / Non-Goals

**Goals:**

- Savoir quelle recette est tirée, combien de fois, et laquelle ne l'est jamais
- Que le chiffre produit porte sa propre portée
- Que les stories 30 à 33 cessent de se décider au jugé

**Non-Goals:**

- Une interface de visualisation
- Mesurer autre chose que les désastres
- Modifier le tirage, les probabilités ou les règles
- Compter les consultations. Ce n'est pas un renoncement de confort : voir ci-dessous.

## Decisions

### Compter les tirages, et refuser de faire semblant de compter les visiteurs

C'est la question ouverte du packet, et la mesure la tranche.

Le tirage a lieu à la production de la page. Le cache englobe la mise en page, vaut
vingt-quatre heures, et sert ensuite sans réexécuter l'action — vérifié par le rapport de
temps, 0,146 s puis 0,032 s. Un compteur posé au tirage compte donc **des défauts de cache**.

Compter les consultations exigerait que du code s'exécute là où le cache l'évite
précisément. Trois façons de le faire, toutes rejetées :

- **Désactiver le cache sur les pages à désastre** — détruit la propriété qu'on mesure. Le
  désastre est invariant pour une représentation donnée ; sans cache, il redevient un effet
  aléatoire par requête, ce qui est un autre produit.
- **Compter côté client** — mesure les navigateurs qui exécutent le script, pas les
  consultations, et ajoute une dépendance tierce au moment où la story 31 cherche à en
  retirer.
- **Compter au niveau du serveur web** — mesure des requêtes HTTP sur une URL, sans savoir
  quel désastre la représentation portait. Ce serait un troisième chiffre, pas le second.

Le relevé nommera donc sa grandeur, et la portera à côté du chiffre plutôt que dans un
document séparé. Un « 1 240 » sans mention se lit comme une audience six mois plus tard —
c'est exactement ce que le packet demandait d'éviter.

### Enregistrer au tirage, dans un journal dédié

Le point d'enregistrement est le tirage lui-même, dans `sfDesastreManager` : c'est le seul
endroit qui connaisse à la fois la règle évaluée et son résultat, y compris quand ce
résultat est « aucune recette ».

Le médium est un journal dédié sous `log/`, pas une table.

- Aucune migration, aucun `doctrine:build-model`, aucun risque pour la base — la journée a
  déjà montré ce que coûte une écriture mal bornée sur ce projet.
- Le dénombrement se fait par une tâche symfony qui lit le journal **et** la liste des
  recettes déclarées, de sorte qu'une recette jamais tirée apparaisse à zéro plutôt que
  d'être absente. Une recette absente du relevé et une recette à zéro se confondraient,
  et c'est précisément la question posée.

**Ce que ce choix coûte** : les journaux tournent. L'historique n'est pas éternel et le
relevé ne vaut que pour la fenêtre conservée. C'est acceptable pour éclairer quatre
décisions ; ça ne le serait pas pour une statistique de long terme, et le jour où on en
voudra une, ce sera une table.

### Un en-tête posé avant la mise en cache

L'en-tête nomme la recette appliquée, et déclare explicitement l'absence de désastre
plutôt que de disparaître — un en-tête absent ne distingue pas « aucun désastre » de
« en-tête cassé ».

Il survit au cache, et ce n'est pas une supposition : `sfViewCacheManager::setPageCache()`
stocke `serialize($this->context->getResponse())`, la réponse entière, en-têtes compris, et
`getPageCache()` remplace l'objet réponse par celui qui a été désérialisé. Un en-tête posé
pendant la production est donc resservi tel quel à chaque succès de cache.

Ce même mécanisme impose l'emplacement : sur un succès, la réponse courante est **jetée**
au profit de la réponse en cache. Poser l'en-tête à ce moment-là n'aurait aucun effet. Il
doit l'être pendant la production, avec le reste.

## Risks / Trade-offs

**L'invariance est le seul risque sérieux.** Deux bugs archivés de cette zone n'étaient que
des ruptures d'invariance, et `desastreInvarianceTest` les garde. Deux façons de la casser
sans le vouloir : désactiver le cache pour se simplifier la collecte, ou poser l'en-tête à
un endroit qui rende la réponse non sérialisable. Le test existant doit rester vert, et il
ne suffit pas — il faut aussi vérifier que l'en-tête lui-même est invariant.

**Le relevé ne dira pas ce qu'on aurait aimé savoir.** « Combien de gens ont vu le désastre
X » restera sans réponse. C'est un choix assumé et écrit ; le risque est qu'il soit oublié
et que le chiffre serve à autre chose que ce qu'il mesure.

**Le journal grossit.** Une ligne par production de page à désastre. Avec un cache de
vingt-quatre heures, le volume est borné par le nombre d'URL distinctes par jour, pas par
le trafic — ce qui est précisément la raison pour laquelle ce chiffre n'est pas une
audience.
