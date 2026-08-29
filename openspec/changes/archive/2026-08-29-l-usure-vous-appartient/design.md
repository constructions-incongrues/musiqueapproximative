## Context

La bande usée pilote son altération par un seul nombre : l'`AudioParam` `intensite` du
worklet, borné à **[0, 1]**, qui multiplie la profondeur de la ligne à retard
(`profondeurEch = profondeurMs / 1000 * sampleRate * intensite`).

Côté page, `intensiteSelonAge()` calcule aujourd'hui :

```
part      = min(1, jours / (18 * 365,25))
intensite = plancher + (1 - plancher) * part²        avec plancher = 0,35
```

**Le problème est là :** pour un morceau de dix-huit ans ou plus, `part` vaut 1 et
`intensite` vaut déjà **1**. Il n'y a aucune marge au-dessus. L'usure personnelle ne peut
donc pas simplement s'ajouter à `intensite` — sur les morceaux les plus anciens, elle
n'aurait littéralement nulle part où aller.

Deux calibrages sont acquis et ne sont pas rouverts ici : la courbe quadratique et le
plancher 0,35 (story 35, choisis sur la distribution réelle du catalogue) et
`profondeurMs = 0,9` (story 33, mesuré à la 440 puis réécouté en ligne). Le
worklet anticipait d'ailleurs ce change : son commentaire dit que `intensite` est un
`AudioParam` « parce que les stories 35 et 36 la feront varier — selon l'âge du morceau,
puis selon le nombre d'écoutes ».

## Goals / Non-Goals

**Goals**

- Faire croître l'altération avec les écoutes de ce navigateur, sans toucher au calibrage
  acquis de l'âge ni au worklet.
- Garantir par construction, et non par une vérification supplémentaire, que l'altération
  reste dans les bornes que le worklet accepte.
- Ne rien faire sortir du navigateur.

**Non-Goals**

- Rejouer la mesure de `profondeurMs`, du plancher ou de la courbe d'âge.
- Modifier `bande-usee-processeur.js`. Il reçoit une intensité, il continue d'en recevoir une.
- Produire un mécanisme de mémoire réutilisable par les dix-huit autres recettes.

## Decisions

### D1 — L'écoute vieillit le morceau, elle ne s'ajoute pas à l'intensité

Retenu : convertir chaque écoute en **âge virtuel**, exprimé dans la même unité que l'âge
réel, et le verser dans le calcul existant avant la courbe.

```
jours_effectifs = jours_reels + usure_en_jours
part            = min(1, jours_effectifs / (18 * 365,25))
intensite       = plancher + (1 - plancher) * part²
```

*Pourquoi.* Le `min(1, …)` déjà présent devient le plafond exigé par la spec : il n'y a
rien à écrire pour l'obtenir, et rien qui puisse le contourner par erreur. La courbe, le
plancher et la constante de dix-huit ans continuent de s'appliquer sans être touchés. Et la
métaphore devient exacte : **écouter vieillit la bande**, ce qui est précisément ce que le
nom du désastre promet.

*Alternative écartée — ajouter l'usure à `intensite` après la courbe.* Il aurait fallu
réserver une bande de marge, donc abaisser le maximum atteignable par l'âge seul, donc
rouvrir un calibrage mesuré et déclaré hors périmètre. Le prix est trop élevé pour un
résultat équivalent.

*Alternative écartée — faire varier `profondeurMs`.* C'est une `processorOption`, fixée à
la construction du nœud : la faire varier obligerait à reconstruire le worklet à chaque
écoute, avec un risque de discontinuité audible que l'`AudioParam` évite par conception.

### D2 — L'usure décroît par demi-vie, pas par palier

L'usure accumulée est stockée en jours virtuels, avec la date de la dernière écoute, et
décroît exponentiellement :

```
usure = usure_stockee * 2^(-jours_depuis_derniere_ecoute / DEMI_VIE)
```

*Pourquoi une décroissance continue.* Un oubli par paliers ferait chuter l'altération d'un
coup entre deux visites, ce qui s'entendrait comme un changement de réglage. Une demi-vie
donne un retour progressif que personne ne remarque, ce qui est le comportement voulu :
l'oubli n'est pas un événement.

*Pourquoi on n'écrit rien à la lecture de la valeur.* La décroissance se calcule à la
lecture, à partir de la date stockée. Un navigateur fermé pendant six mois n'a rien à
rattraper.

### D3 — Une seule clé de stockage, pour tout le désastre

```
localStorage["desastres:bande-usee:usure"] = {
  "<slug-du-morceau>": { "j": <usure en jours virtuels>, "t": <horodatage ms>, "n": <ecoutes> }
}
```

*Correction apportée à l'implémentation.* Le champ `n` ne figurait pas dans ce design ; la
tâche 2.3 exigeait pourtant de journaliser le nombre d'écoutes, qu'aucun autre champ ne
permet de retrouver — `j` est une durée, pas un compte, et l'oubli l'érode. Un design qui ne
permet pas sa propre tâche est le design qui a tort : `n` a été ajouté. Il ne sert qu'au
journal de console et au diagnostic, jamais au calcul de l'intensité.

*Pourquoi une clé et non une par morceau.* Le catalogue compte plus de huit mille morceaux :
une clé par morceau saturerait le stockage de l'origine pour un ornement. Une seule entrée
JSON reste lisible, effaçable d'un geste, et bornée — voir R2.

*Le slug* est déjà dans l'URL de la page (`/post/:slug`) ; aucune balise nouvelle n'est
nécessaire, contrairement à la date de publication qu'avait dû ajouter la story 35.

### D4 — Une écoute est comptée au démarrage de la lecture, une fois par page

Le compteur s'incrémente au premier événement de lecture du lecteur, et une seule fois
pour un chargement de page donné. Une pause suivie d'une reprise ne compte pas une seconde
écoute ; rouvrir la page et relancer, si.

*Pourquoi pas à l'affichage.* Compter les visites plutôt que les écoutes userait la bande
de quelqu'un qui n'a rien entendu — le geste ne tiendrait plus.

### D5 — Rien ne remonte, et le document ne bouge pas

Aucune requête n'est émise. Le calcul est entièrement dans `bande-usee.js`, après le
service de la page.

C'est une contrainte dure et non une préférence : la capacité `desastres` exige, sous
« L'invariance est préservée », qu'une page tirée et mise en cache serve un corps identique
à chaque requête, et `desastreInvarianceTest` le vérifie. Une usure calculée côté serveur
romprait ce test.

## Risks / Trade-offs

**R1 — Sur les morceaux les plus anciens, l'usure personnelle est inaudible.** Un morceau
de dix-huit ans ou plus est déjà à `part = 1` : l'écouter cent fois n'y change rien. Environ
**2 % du catalogue** (les 179 morceaux de 2008) sont dans ce cas, et ceux de 2009 (296
morceaux) en sont proches — `part ≈ 0,94`.
→ *Mitigation* : c'est le plafond que la spec exige, appliqué tôt plutôt que tard, et il se
défend — une bande déjà usée jusqu'à la corde ne s'use plus. À dire dans le README de la
recette pour que ce ne soit pas découvert comme un défaut. Si l'effet manque vraiment, le
repli est l'alternative écartée en D1, au prix d'un recalibrage de `profondeurMs`.

**R2 — Le stockage grossit avec le nombre de morceaux écoutés.** Une entrée par morceau,
indéfiniment.
→ *Mitigation* : à l'écriture, purger les entrées dont l'usure décroît sous un seuil
négligeable — elles ne changent plus rien au rendu. Le stockage se borne alors de lui-même
au répertoire réellement écouté.

**R3 — `localStorage` peut être indisponible** (navigation privée stricte, stockage
désactivé, quota atteint). Un accès qui lève ferait échouer le désastre.
→ *Mitigation* : tout accès est enveloppé, et l'échec retombe sur l'usure d'âge seule. Un
désastre est un ornement : il ne doit jamais empêcher d'écouter. C'est la ligne de conduite
déjà tenue par `intensiteSelonAge()`, qui renvoie son repli quand la date manque.

**R4 — Les deux réglages neufs sont devinés, pas mesurés.** L'âge virtuel gagné par écoute
et la demi-vie de l'oubli n'ont aucune mesure derrière eux, alors que tous les réglages de
cette recette en ont une.
→ *Mitigation* : les exposer en options de la recette avec des valeurs de départ
explicitement provisoires, et les régler à l'oreille comme `profondeurMs` l'a été —
l'écoute en ligne est la vérification, pas le calcul. Valeurs de départ proposées :
**1 an d'âge virtuel par écoute**, **demi-vie de 30 jours**. La courbe étant quadratique,
les premières écoutes usent très peu ; il en faut une dizaine pour qu'un morceau récent
s'entende bouger, ce qui est l'intention.
→ *Relevé après implémentation, à confronter à l'oreille* : sur un morceau de onze jours,
l'intensité passe de **0,350 à vide à 0,552 vers dix écoutes**, et **sature vers dix-neuf**.
Le mouvement voulu est bien là ; le plafond arrive peut-être plus tôt qu'on ne le souhaite.
C'est `ageVirtuelParEcouteAns` qu'il faut baisser si c'est le cas.

**R5 — Le recours existe et n'est pas notifié.** `?sans-desastre` retire tout, son compris,
et suspend le comptage par l'effet du retour anticipé en tête de module. Mais il n'est
documenté que dans le README de la recette et la documentation Antora : un visiteur ne peut
pas le découvrir. Ce change transforme par ailleurs une contrainte portant sur *la page* en
un enregistrement portant sur *la personne* — modeste, local, anonyme, mais nouveau : c'est
la première mémoire du catalogue.
→ *Mitigation* : groupe de tâches 4 — figer l'ordre du retour anticipé comme exigence plutôt
que comme accident, et porter `?sans-desastre` dans le bloc « Raccourcis » du pied de page.
Analyse complète en quatre modalités dans la proposition, sous « Ce que ce change promulgue,
et qui peut y faire appel ».

## Migration Plan

Aucune. Pas de schéma, pas de dépendance, pas d'étape de déploiement : la fusion sur `main`
met en ligne, comme pour tout change de ce dépôt.

Le retour en arrière est le retrait du calcul d'usure dans `bande-usee.js` — le désastre
revient à l'usure d'âge seule. Les entrées `localStorage` laissées derrière sont inertes et
disparaissent avec le stockage du visiteur ; elles ne justifient pas de code de nettoyage.

## Open Questions

1. **Les deux réglages de R4 tiennent-ils à l'écoute ?** À trancher en ligne, sur un morceau
   récent, après une dizaine d'écoutes. C'est la seule question qui ne se règle pas en
   lisant du code.
2. **Faut-il purger les entrées négligeables (R2) dès ce change ou attendre ?** Le stockage
   ne devient un problème qu'après plusieurs centaines de morceaux écoutés depuis le même
   navigateur. Retenu par défaut : le faire tout de suite, c'est trois lignes à l'écriture.
