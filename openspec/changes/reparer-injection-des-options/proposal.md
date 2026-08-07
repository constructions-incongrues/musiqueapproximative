## Why

Le corpus affirme, sous « Requirement: Application d'une recette » :

> **QUAND** la recette déclare des options
> **ALORS** ces options sont mises à disposition du désastre côté client

C'est vrai pour un visiteur sur des milliers. Pour tous les autres, les fichiers du
désastre se chargent et le désastre ne s'applique pas — silencieusement.

`sfDesastreFilter` injecte les options **après** que `sfCacheFilter` a écrit l'entrée de
cache. La représentation mise en cache contient donc les feuilles de style et les scripts
du désastre, mais pas le bloc `window.DesastreOptions` dont ils dépendent. Le premier
visiteur reçoit la réponse enrichie par le filtre ; tous ceux qui suivent, pendant les
86 400 secondes de `cache.yml`, reçoivent l'entrée mise en cache, amputée.

Mesuré le 7 août 2026 en local, en environnement `prod` avec le cache actif, sur
`/post/the-tanzdiele-strandgut` :

| Demande | Ressources du désastre | `window.DesastreOptions` |
|---|---|---|
| 1re, cache vide | présentes | **présent** |
| 2e à 5e | présentes | **absent** |

Le `diff` des deux réponses ne porte que sur le bloc injecté : un `<style>` de variables
CSS et un `<script>` posant `window.DesastreOptions`, 476 octets, présents une fois puis
jamais.

Puis mesuré contre la production, sur deux adresses jamais servies auparavant :

| Adresse | 1re demande | demandes suivantes |
|---|---|---|
| `/post/free-kitten-greener-pastures?z=100` | options présentes | absentes |
| `/post/matching-mole-o-caroline?z=201` | options présentes | absentes |

Le défaut est en ligne.

Il est silencieux parce que les scripts de désastre déréférencent les options sans garde.
`src/web/desastres/mangelettres/javascript/mangelettre.js` lit
`window.DesastreOptions.mangelettres.selector` dès sa sixième ligne : sur une réponse
servie depuis le cache, l'objet est absent, le script lève une `TypeError`, et rien ne
paraît. Ni erreur visible, ni désastre.

## What Changes

Le filtre est déclaré dans `src/apps/frontend/config/filters.yml` **avant** `cache`. La
chaîne de filtres de symfony 1 se déroule vers l'intérieur puis remonte en ordre inverse :
au retour, un filtre déclaré plus haut s'exécute après un filtre déclaré plus bas.
`sfCacheFilter` écrit donc son entrée avant que `sfDesastreFilter` n'ait injecté.

Déplacer la déclaration `desastre` sous `cache` inverse l'ordre au retour : l'injection a
lieu d'abord, l'écriture du cache ensuite, et la représentation mise en cache est complète.

L'autre correctif envisageable — garder les scripts contre l'absence d'options — a été
écarté. Il ferait taire l'erreur sans rétablir le désastre : les visiteurs continueraient
de télécharger des ressources inertes.

## Impact

Ce changement rend vraie une exigence que
[`preciser-aleatoire-des-desastres`](../preciser-aleatoire-des-desastres/proposal.md)
décrit sans qu'elle le soit encore : « toutes les réponses portent le même résultat de
tirage » et « deux visiteurs voient le même effet ». Aujourd'hui le tirage est bien mis en
cache, mais son effet ne l'est pas. Après ce changement, les deux le sont.

Le comportement observable change pour les visiteurs : un désastre tiré s'applique
désormais à toute la durée de vie de l'entrée de cache, et non à sa seule première
consultation. C'est ce que le corpus décrit depuis toujours.

Aucune autre réponse n'est touchée. Le filtre ne modifie que les réponses `text/html`
porteuses d'attributs de désastre, et ressort immédiatement dans tous les autres cas.
