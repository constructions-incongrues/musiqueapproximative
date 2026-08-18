## Why

Un client machine qui demande une ressource absente reçoit une page HTML habillée. C'est le
second gap du parcours de l'intégrateur, et il l'oblige à traiter l'échec hors de son chemin
normal — analyser du HTML, ou deviner à partir du seul code de statut.

Mais le relevé machine dit pire que ce que le plan annonçait. **Deux des quatre cas ne
renvoient pas 404 : ils plantent.** Une erreur fatale PHP n'est pas un contrat, c'est un
crash, et elle expose une trace d'exécution.

`ApiErrorResponse` est écrite et testée depuis des mois, et n'a jamais eu d'appelant.

## L'état relevé, les quatre cas

| demande | aujourd'hui | attendu |
| --- | --- | --- |
| `/post/{slug inconnu}?format=json` | 404 · `text/html` | 404 · `application/json` |
| `/post/md5/{empreinte inconnue}` | **500 — erreur fatale** | 404 · `application/json` |
| `/posts/next` sans `current`, ou `current` inconnu | **500 — erreur fatale** | 400 / 404 · `application/json` |
| `/posts/next` sur le morceau le plus récent | 200 · `{"url":"/post/","title":" - "}` | 404 · `application/json` |

Le quatrième cas n'est pas une erreur de code mais un mensonge : un `200` qui annonce un
morceau, et un morceau vide dans le corps.

## What Changes

- Les deux erreurs fatales disparaissent. `executeMd5` vérifie que le morceau existe avant
  de le sérialiser ; `executeNext` et `executePrev` vérifient que le morceau courant existe
  avant de chercher son voisin.
- Une erreur sur une route JSON revient **en JSON**, avec le type de contenu du format
  demandé, et un corps construit par `ApiErrorResponse`.
- `/posts/next|prev` sans voisin répond 404 au lieu d'un 200 vide.
- `/posts/next|prev` sans `current` répond 400 : le paramètre est déclaré obligatoire au
  contrat, son absence est une demande mal formée et non une ressource absente.
- Amendement du contrat OpenAPI : les réponses d'erreur y sont déclarées, et les trois
  mentions « ne produit pas de 404 mais une erreur du serveur » disparaissent avec les
  erreurs.

Le contrat public est concerné : **trois routes changent de code de statut** — mais elles
changent depuis un crash, non depuis un comportement défendable.

## Sur la forme du corps d'erreur

`ApiErrorResponse` produit `{"errors":[{"status","title","detail"}]}`, forme héritée de la
convention JSON:API que ce projet a écartée. Elle est reprise **pour ses propres mérites**,
non par conformité : c'est une forme simple, déjà écrite, déjà testée, et inventer autre
chose demanderait du code neuf pour un gain nul. La classe restera cependant nommée d'après
une norme qu'on ne suit plus — c'est consigné plutôt que masqué.

## Capabilities

### New Capabilities

Aucune.

### Modified Capabilities

- `formats-de-sortie` : gagne une exigence « Représentation d'une erreur ». Les
  représentations y sont décrites ; l'échec en est une, et il n'y figurait pas.
- `catalogue-morceaux` : les exigences « Recherche par empreinte du fichier » et
  « Réponse de navigation » décrivent le succès et se taisent sur l'absence. Elles gagnent
  ce qui arrive quand la ressource n'existe pas.

## Hors périmètre

- **`/rest`**, qui suit le protocole Subsonic et répond `200` même en erreur, par
  spécification.
- **`/oembed`**, qui a la sienne.
- **La page d'erreur HTML**, qui ne change pas. Ce changement ne touche que ce qui est
  demandé dans un format machine.
- **Les autres codes** — 400 sur un `format` inconnu, 500 applicatifs. Le premier n'est pas
  une erreur (la spec dit que la page HTML est servie), le second est hors sujet.
- **Renommer `ApiErrorResponse`** ou changer la forme qu'elle produit.

## Impact

- **Modifié** : `src/apps/frontend/modules/post/actions/actions.class.php` (`executeMd5`,
  `executeNext`, `executePrev`, et le chemin d'erreur de `executeShow`),
  `src/web/openapi.yaml-dist`.
- **Branché sans être modifié** : `src/lib/helper/ApiErrorResponse.php`, qui reçoit enfin
  un appelant.
- **À ne pas casser** : les six `$.get` de `layout.php`. Le gabarit garde déjà l'absence de
  voisin côté serveur — les flèches ne sont rendues que s'il y en a un — donc un 404 sur
  ces routes n'a aucun élément à mettre à jour. À vérifier plutôt qu'à supposer.
- **Dépendances** : aucune nouvelle. Story 4 livrée.
