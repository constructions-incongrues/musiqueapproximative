## EN ATTENTE D'UN DIAGNOSTIC — revue d'ingénierie du 2026-08-18

**Ce change ne part pas en l'état.** La revue a établi que sa prémisse n'est pas vérifiée.

Le plan attribue les 17,5 s au volume sérialisé. Les mesures disent autre chose :

| format | poids | durée | accès contributeur par morceau |
| --- | --- | --- | --- |
| `xspf` | 3,5 Mo | **2,9 s** | **aucun** |
| `max` | 2,6 Mo | **15,6 s** | `getSfGuardUser()->username` |
| `json` | 8,4 Mo | **17,5 s** | `…->UserProfile->website_url` + rendu Markdown |

**Moins d'octets, cinq fois plus lent.** Vérifié dans le code : `Post::toJson()` ligne 119 lit
`getSfGuardUser()->UserProfile->website_url`, et `buildOnlinePostsQuery` ne joint que
`sfGuardUser` — **jamais `UserProfile`**. Soit une requête par morceau, environ 8 100.
`_xspfPlaylist.xspf.php` ne touche au contributeur **zéro fois** : c'est exactement le format
rapide. La corrélation est parfaite.

Si l'hypothèse tient, élargir la jointure ramène le catalogue entier à quelques secondes
**sans rompre aucun contrat public** — alors que ce change rompt le contrat pour traiter un
coût dont il n'a pas identifié la cause.

## Diagnostic mené le 2026-08-19 — l'hypothèse est confirmée

Mesuré en local sur le catalogue complet (8 099 morceaux), identity map vidée avant chaque
essai :

| | requêtes | durée |
| --- | --- | --- |
| hydratation seule | **1** | 0,79 s |
| + `getSfGuardUser()->username` | **8 099** | 6,63 s |
| + `UserProfile->website_url` | **8 270** | 6,53 s |
| + `Markdown(body)` | 1 | 0,65 s |
| + `html_entity_decode` ×4 | 1 | 0,49 s |

**L'accès au contributeur représente 88 % du coût au-dessus de la ligne de base.** Ni le
rendu Markdown ni le décodage d'entités ne pèsent.

Et la correction est mesurée :

| | requêtes | durée |
| --- | --- | --- |
| jointure actuelle | 8 271 | 7,17 s |
| **+ `leftJoin('u.UserProfile pr')`, select explicite** | **1** | **1,08 s** |

**8 270 requêtes supprimées, 6,6× plus rapide**, pour un `leftJoin` et un `select`. Aucune
rupture de contrat, aucun changement de comportement public.

*Piège de méthode consigné : une première mesure concluait « ce n'est pas 8 100 requêtes »
et se trompait — l'identity map de Doctrine restait chaude d'un essai à l'autre. Sans
`$conn->clear()` entre les mesures, le N+1 est invisible.*

## GELÉ — décision de l'auteur du 2026-08-19

Le diagnostic ayant montré que la lenteur venait d'un N+1 et non du volume, **ce change est
gelé**. La correction de la jointure part seule, dans un change à part : gain net, aucune
contrepartie, aucun contrat rompu.

Ce change reste au dépôt, avec son diagnostic et ses huit décisions de revue, **prêt à
repartir le jour où un consommateur réclame la pagination**. Il ne repartira pas sur un
argument de latence : celui-là est mesuré faux.

Ce qu'il faudra instruire s'il repart : les sept points non tranchés en section 7 des
tâches, et le fait qu'aucun consommateur de ces routes n'a jamais été identifié — question
ouverte n° 3 du plan de release, sans réponse depuis le premier jour.

---

## Why

Les trois représentations machine de `/posts` servent **l'intégralité du catalogue** —
8 098 morceaux — à chaque demande. Mesuré sur la production le 2026-08-18 :

| format | poids | durée |
| --- | --- | --- |
| `max` | 2,6 Mo | 15,6 s |
| `xspf` | 3,5 Mo | 2,9 s |
| `json` | **8,4 Mo** | **17,5 s** |

Le `xspf` figure en outre parmi les **liens visibles proposés au visiteur** sur une page de
liste, ce que la spec `formats-de-sortie` impose : un humain clique et attend.

Le plan visait d'abord le seul XSPF, sur un relevé de 3,1 s vieux de sept mois. La mesure
reprise a inversé la hiérarchie — le XSPF est **le plus rapide des trois**. Les traiter
séparément aurait corrigé la plus petite des latences, et multiplié l'appareil de
planification par trois pour un seul arbitrage.

## What Changes

- Les routes de liste acceptent `count` et `offset` sur les formats `json`, `xspf` et `max`,
  et sont **bornées par défaut**.
- **Elles deviennent paginables** : chaque réponse dit combien de morceaux existent au
  total, quelle portion elle porte, et comment obtenir la suivante. Borner sans cela
  n'aurait pas produit une pagination mais une troncature — un consommateur qui reçoit
  50 morceaux sans savoir qu'il en existe 8 048 autres ne peut que deviner, en redemandant
  jusqu'à ce que ce soit vide.
- Le titre de playlist XSPF, que `formats-de-sortie` spécifie, dit ce que le document
  contient réellement lorsque la liste est tronquée.
- Le contrat OpenAPI est amendé : il déclare aujourd'hui « **Aucun bornage** », ce qui
  cessera d'être vrai.

Le contrat public est concerné : ces routes changent de comportement par défaut.

## Les deux questions ouvertes, tranchées par ce que le site fait déjà

**Le nom du paramètre et le défaut.** `/posts/feed` accepte `count` avec **50 par défaut**
(`$request->getParameter('count', 50)`). Le plan proposait d'inventer `limit` et de
rediscuter le chiffre. **Les deux seraient un doublon** : une seconde convention pour la
même chose, et un second nombre à retenir. Ce change reprend `count` et `50`, et ajoute
`offset`, qui n'a pas de précédent parce que le flux n'en avait pas besoin.

**Ce que porte la pagination, format par format.** Voir `design.md` : le `max` est du texte
brut lu par un patch Max/MSP, on ne peut rien lui ajouter dans le corps sans risquer de
casser son analyseur. La réponse retenue est un en-tête HTTP `Link`, qui vaut pour les trois
et ne touche à aucun corps, complété là où le format sait porter l'information lui-même.

**Ce que reçoit le visiteur qui clique le lien XSPF.** La moitié de la réponse existe :
`listSuccess.php` construit déjà ce lien avec `c` et `q`, donc il porte le contexte de la
liste regardée. Il reçoit donc les 50 premiers morceaux **de cette liste-là**, et le titre
du document le dit. Un intégrateur qui veut la suite pagine ; un visiteur qui voulait
écouter a une playlist qui se charge.

## Ce que ça casse, et pourquoi on le fait quand même

Un consommateur qui dépend aujourd'hui de la liste entière recevra **50 morceaux sans avoir
rien changé**, et rien ne le lui dira au moment où ça arrive. C'est une rupture de contrat,
pas un réglage.

Trois faits pèsent dans l'autre sens. Le contrat lui-même **avertit déjà** qu'aucun canal
n'existe pour signaler ses changements — « rien n'est poussé vers les consommateurs, il
n'existe ni en-tête de dépréciation, ni délai, ni adresse à qui écrire » — et cet
avertissement est publié. Aucun consommateur n'est identifié : la question ouverte n° 3 du
plan de release le demande depuis le début et n'a jamais trouvé de réponse. Et le coût
présent est réel : 17,5 s pour huit méga-octets, sur un lien qu'un humain clique.

**Le geste qui rend la rupture rattrapable est de la déclarer** : le contrat OpenAPI passe
de « Aucun bornage » à la description exacte du bornage, et ce document est servi en ligne
et versionné — son diff est le canal que le contrat désigne lui-même.

## Hors périmètre

- **La page HTML.** Non bornée par décision de l'auteur : on y fait Ctrl+F, et la recherche
  du site n'indexe pas le contributeur. Ce motif ne vaut pour aucun format machine — on ne
  cherche pas au clavier dans un fichier XSPF.
- **Le flux RSS**, déjà borné, et l'**API Subsonic**, hors du plan de release.
- **Une pagination par curseur.** Le catalogue est ordonné par date de publication
  décroissante et un morceau publié pendant qu'un consommateur pagine décale sa fenêtre.
  C'est un défaut réel de la pagination par rang, et il est connu ; le corriger demande un
  curseur stable, donc un autre modèle. Le site publie une fois par jour : le décalage est
  d'un morceau, pas d'une page.
- **Un plafond sur `count`.** Le laisser ouvert préserve l'usage actuel pour qui en dépend
  déjà ; le borner est une décision à prendre sur des mesures d'usage qu'on n'a pas.
