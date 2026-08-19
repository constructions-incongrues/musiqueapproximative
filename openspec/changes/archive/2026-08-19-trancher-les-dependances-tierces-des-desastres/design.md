## Context

Deux décisions du projet se contredisent, et rien ne l'écrit.

Le 2026-08-18, Redoc a été versé au dépôt — 1 071 ko — plutôt qu'appelé depuis un CDN, au
motif que le visiteur qui lit la description de l'API n'a pas à être annoncé à un tiers. Le
même jour, sept recettes de désastre sur dix-neuf continuaient d'appeler deux CDN à
l'exécution, sur des pages publiques.

**Le relevé, mesuré avant d'écrire :**

| hôte | renvois | fichiers distincts |
| --- | --- | --- |
| `cdn.jsdelivr.net` | 6 | `gsap@3.13.0/dist/gsap.min.js`, `gsap@3.13.0/dist/SplitText.min.js` |
| `cdnjs.cloudflare.com` | 4 | `animejs/3.2.2/anime.min.js` |

Sept recettes concernées : `amour`, `consonnard`, `light`, `musique`, `noir`,
`splitouine_titles_matchduration`, `voyelliste`.

**Ce ne sont pas des chemins morts**, et c'est la story 29 qui permet de le dire. Sur les
productions relevées à ce jour, `voyelliste` et `consonnard` — deux appelantes de
jsdelivr — figurent parmi les plus tirées. La dépendance déclarée par le packet est donc
satisfaite, et son inquiétude levée : un CDN appelé sur un désastre qui ne sort jamais
aurait été un autre problème.

## Goals / Non-Goals

**Goals:**

- Que la contradiction cesse d'être implicite, quelle que soit l'issue
- Que le visiteur cesse d'être annoncé à deux CDN au hasard d'un tirage
- Que ce que le site expose soit écrit, y compris quand il n'expose rien

**Non-Goals:**

- Le webhook `n8n`, service maison du collectif, qui relève d'un autre arbitrage
- Les liens externes des pages de contenu — un lien n'est pas une requête
- Toute modification du tirage, des règles, des probabilités ou du cache

## Decisions

### Auto-héberger, parce que la décision a déjà été prise pour la même raison

Les deux issues se défendaient dans l'abstrait. Elles ne se défendent plus également une
fois posées à côté du précédent : le projet a déjà tranché cette exacte question, en
versant un fichier de plus d'un mégaoctet plutôt que de laisser un CDN voir ses lecteurs.

Maintenir l'appel tiers sur les désastres reviendrait à dire que le visiteur qui lit la
documentation mérite cette protection, mais que celui qui consulte un morceau ne la mérite
pas. Cette position est tenable si elle est assumée ; elle n'est pas tenable en silence, et
personne ne l'a jamais formulée.

**L'argument de taille ne joue pas** : `gsap.min.js` fait 70 ko, les deux autres moins, et
le Redoc déjà versé pèse 1 071 ko. Ce qui a été accepté une fois pour un mégaoctet ne se
refuse pas pour un dixième.

### Suivre le motif déjà posé plutôt qu'en inventer un

```
web/frontend/assets/javascripts/redoc/redoc-2.5.3.standalone.js
web/frontend/assets/javascripts/redoc/redoc-2.5.3.standalone.js.LICENSE.txt
```

La version est dans le nom du fichier, la licence est à côté. Les trois fichiers versés
suivront la même forme. Il n'y a pas de raison d'inventer une convention quand le dépôt en
porte déjà une, et la version dans le nom rend visible la mise à jour qu'un chemin nu
masquerait.

### La licence est un verrou, pas une formalité

Auto-héberger, c'est **redistribuer**. La question n'est donc pas seulement « a-t-on le
droit d'utiliser », mais « a-t-on le droit de servir depuis chez soi ».

`anime.js` est sous MIT, ce qui règle son cas. GSAP est un cas à instruire : ses conditions
ont changé au fil des versions, et `SplitText` a longtemps été un greffon réservé aux
adhérents. **Ce point est vérifié avant que le fichier soit versé, pas après.**

Si une licence ne permet pas la redistribution, la décision bascule pour ce fichier
seulement : il reste appelé depuis son hôte, et l'exception est écrite avec sa raison. Une
exception écrite vaut mieux qu'une contradiction muette — c'est le but même de ce change, et
il est atteint dans les deux branches.

### Vérifier par l'absence de requête, pas par la lecture de la configuration

Une recette dont on a changé l'URL dans le YAML peut continuer d'appeler un tiers par un
autre chemin : un `@import` dans une feuille de style, une police, une image de fond, un
appel que la bibliothèque fait elle-même au chargement.

La vérification porte donc sur ce que le navigateur demande réellement quand un désastre
s'applique, et non sur ce que le fichier de configuration déclare.

## Risks / Trade-offs

**Le risque principal est de croire la bascule faite parce que le YAML a changé.** C'est la
forme locale du défaut qui a falsifié cinq packets de cette release : conclure de ce qui est
lisible plutôt que de ce qui est exécutable.

**Les fichiers versés ne se mettent plus à jour tout seuls.** Un CDN sert la dernière
version d'une branche ; un fichier versé reste celui qu'on a versé. C'est un coût réel, et
c'est aussi la contrepartie recherchée — une bibliothèque qui change sous les pieds du site
sans que personne l'ait décidé est un autre problème. La version dans le nom rend la mise à
jour explicite.

**Sept recettes sur dix-neuf sont touchées**, dont plusieurs parmi les plus tirées. Une
bascule ratée ne casserait pas la page — les désastres sont des ornements — mais elle
retirerait silencieusement l'effet. D'où une vérification par recette, et non un contrôle
global qui passerait au vert en n'ayant rien chargé du tout.
