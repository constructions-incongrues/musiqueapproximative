## Why

`/posts` sert **8 097 morceaux** en une seule réponse : 3,7 Mo en HTML, 8,4 Mo en JSON, et
**16,5 s de génération** à froid. Rien ne permet de demander moins. C'est la douleur mesurée
du plan de release, et elle touche trois personas — l'intégrateur qui ne peut pas borner,
le DJ de soirée qui abandonne le catalogue sur le réseau d'une salle, le mélomane fêlé dont
la playlist publique pèse déjà 1 Mo.

Le câble est posé depuis toujours : `buildOnlinePostsQuery($contributor, $count)` accepte
une limite, qu'`executeList` ne lui passe jamais.

## What Changes

- Paramètres `limit` et `offset` sur `/posts`, valant pour **tous** les formats que sert
  cette action — HTML compris. Le DJ de soirée ne connaîtra jamais le paramètre : le défaut
  est ce que reçoit celui qui ne demande rien.
- **Défaut : 50 morceaux.** Tranché par l'auteur le 2026-08-18. La valeur n'invente rien :
  `/posts/feed` borne déjà à 50 par son paramètre `count`, et c'était la seule route bornée
  du site.
- **BREAKING** : un appelant qui ne demande rien reçoit 50 morceaux au lieu de 8 097. La
  page `/posts` n'affiche plus le catalogue entier.
- Le **total** est exposé, pour qu'un appelant sache ce qu'il n'a pas reçu.
- Comportement défini quand les paramètres sont absents, négatifs, non numériques, ou
  au-delà du total.
- Amendement du contrat OpenAPI : deux paramètres déclarés, et la mention « aucun bornage »
  qui disparaît avec le bornage.

Le contrat public est concerné : **la réponse par défaut de `/posts` change de taille**,
dans tous les formats. Aucun corps d'élément ne change ; c'est leur nombre qui change.

## Ce que le relevé impose et que le packet de la story ignorait

Trois faits, relevés dans le code à la proposition :

1. **`search()` ne renvoie pas une requête mais un tableau PHP.** Elle boucle sur les
   résultats de l'index et rappelle la base pour chacun. Borner la recherche n'est donc pas
   la même opération que borner la liste — la limite SQL ne s'y applique pas.
2. **Les titres de page comptent les morceaux servis.** `executeList` écrit
   « %d résultat(s) » et « %s a posté %d morceau(x) à ce jour » avec `count($posts)`. Bornés
   à 50, ces titres mentiraient. `countOnlinePosts()` existe déjà et n'est appelé que par
   `executeShow`.
3. **`buildOnlinePostsQuery` gère la limite mais pas le décalage.** `offset` est à ajouter ;
   `limit` est à brancher.

## Capabilities

### New Capabilities

Aucune.

### Modified Capabilities

- `catalogue-morceaux` : l'exigence « Liste et recherche plein texte » porte le scénario
  « Liste complète », qui dit que **tous** les morceaux publiables sont listés. Ce
  changement le rend faux. L'exigence gagne le bornage, ses paramètres, l'exposition du
  total, et la garantie que les libellés qui comptent des morceaux comptent le total et non
  la tranche servie.

## Hors périmètre

- **L'interface de pagination** — boutons « page suivante », numéros de page. Elle est en
  « Could » au plan de release, et elle relève du travail de navigation. **Conséquence
  assumée et nommée** : entre ce changement et cette interface, le catalogue au-delà des 50
  premiers morceaux n'est atteignable qu'en connaissant `offset`.
- **Les formats `xspf` et `max`** servis par cette même action : le XSPF est la story 3,
  qui réutilisera la convention tranchée ici. Ce changement borne ce que l'action produit ;
  la story 3 traite ce que le lien visible offre au visiteur.
- **Le flux RSS**, déjà borné à 50 par son propre paramètre.
- **L'élargissement de l'index de recherche** au corps et au contributeur, en « Could ».
- **La forme des corps JSON**, inchangée.

## Impact

- **Modifié** : `src/apps/frontend/modules/post/actions/actions.class.php` (`executeList`),
  `src/lib/model/doctrine/PostTable.class.php` (`buildOnlinePostsQuery`, `getOnlinePosts`,
  et le chemin de `search`), `src/web/openapi.yaml-dist`,
  les gabarits de liste si l'exposition du total l'exige.
- **Vérifié sans être modifié** : `executeFeed`, qui passe déjà `count=50` ;
  `executeShow`, seul appelant actuel de `countOnlinePosts()`.
- **Dépendances** : aucune nouvelle.
