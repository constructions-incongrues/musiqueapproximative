## Context

Le socle rend ses 404 par un module d'erreur configuré globalement, qui sert du HTML. Une
action qui appelle `forward404Unless` n'a pas la main sur ce qui sera rendu ensuite : la
décision de format doit donc être prise **dans l'action**, avant de céder la main au socle.

Deux des quatre cas ne sont pas des 404 mal habillés mais des erreurs fatales — appel de
méthode sur `false`, et argument de type incorrect. Ils précèdent toute question de format.

## Goals / Non-Goals

**Goals :** aucune erreur d'exécution sur une ressource absente ; un corps analysable dans
le format demandé ; distinguer une demande mal formée d'une ressource absente ; ne pas
casser le lecteur du site.

**Non-Goals :** la page d'erreur HTML, `/rest`, `/oembed`, la forme produite par
`ApiErrorResponse`.

## Decisions

### Le format se décide dans l'action, pas dans le module d'erreur

Détourner le module d'erreur global pour qu'il rende du JSON selon le format demandé
toucherait toutes les applications et tous les modules, `admin` compris. Le périmètre serait
sans commune mesure avec le besoin, et le risque de régression porterait sur des pages qui
n'ont rien demandé.

Les actions concernées sont **quatre**, toutes dans un seul module. Elles décident
elles-mêmes : si le format demandé est machine, elles rendent l'erreur ; sinon elles
laissent le socle faire ce qu'il fait déjà.

### Corriger les fatales d'abord, habiller ensuite

`executeMd5` sérialise sans avoir vérifié que le morceau existe. `executeNext` et
`executePrev` passent à `getNextPost(Post $post, …)` le résultat d'un `find()` qui vaut
`false` quand l'identifiant est absent ou inconnu.

Ces deux défauts existent indépendamment du format : ils frappent aussi un appelant qui ne
demande rien de particulier. Les corriger n'est pas un préalable à cette story, c'en est la
première moitié — et la plus grave, puisqu'un 500 expose une trace d'exécution.

### Quatre cent quand la demande est mal formée, quatre cent quatre quand la ressource est absente

`current` est déclaré obligatoire au contrat. Son absence n'est pas « la ressource n'existe
pas » mais « la demande ne permet pas de savoir laquelle ». Confondre les deux prive un
client de l'information qui lui dirait quoi corriger.

Le `current` **inconnu** est en revanche une ressource absente : la demande est bien formée,
c'est le morceau qui manque.

### Le 200 vide de la navigation devient un 404, et c'est le point à surveiller

`/posts/next` sur le morceau le plus récent sert aujourd'hui
`{"url":"/post/","title":" - "}` — un succès annoncé, un morceau vide dans le corps. C'est
la seule des quatre corrections qui touche une route que **le lecteur du site appelle sur
chaque page**.

Ce qui rend le changement sûr, et qui doit être vérifié plutôt que supposé : `showSuccess.php`
ne rend les flèches de navigation **que si le voisin existe** (`if ($post_previous)`). Sur le
morceau le plus récent, `.nav-r a` n'existe pas — le rappel AJAX n'a donc aucun élément à
mettre à jour, et son échec silencieux ne retire rien. Le `$.get` de jQuery n'appelle pas son
rappel de succès sur un 404 : le lien conserve ce que le serveur a rendu.

L'alternative — garder un `200` et y mettre un marqueur explicite d'absence — a été écartée :
elle demande au client de distinguer deux formes de succès, là où le protocole a un code
pour ça.

## Risks / Trade-offs

- **Un client qui traitait le 200 vide comme « pas de suivant »** cesse de recevoir un 200 →
  aucun client de ce genre n'est identifié, et le seul consommateur connu est le lecteur du
  site, qui n'en fait rien.
- **Le lecteur casse d'une façon non anticipée** → c'est le risque réel de cette story. La
  vérification passe par le navigateur, sur le morceau le plus récent et sur le plus ancien,
  et non par le seul test fonctionnel.
- **Trois routes changent de code de statut** → mais depuis un `500` pour deux d'entre elles.
  On ne casse pas un contrat, on cesse de planter.
- **La classe reste nommée d'après une norme écartée** → consigné dans la proposition. La
  renommer est un autre travail.

## Migration Plan

Aucune. Le retour en arrière consiste à retirer les vérifications ajoutées.

## Open Questions

Aucune.
