## 1. Relever l'état de départ

- [x] 1.1 Relever les quatre cas : `/post/{slug inconnu}?format=json`,
  `/post/md5/{empreinte inconnue}`, `/posts/next` sans `current` et avec un `current`
  inconnu, `/posts/next` sur le morceau le plus récent. Code de statut, type de contenu, et
  début du corps pour chacun.
- [x] 1.2 Relever ce que le lecteur affiche **aujourd'hui** sur le morceau le plus récent et
  sur le plus ancien : les flèches sont-elles rendues, et que valent leurs `href` ? C'est
  l'état contre lequel on vérifiera qu'on n'a rien cassé.

### Relevé du 2026-08-18

| demande | statut | type | corps |
| --- | --- | --- | --- |
| `/post/{slug inconnu}?format=json` | 404 | `text/html` | page HTML habillée |
| `/post/md5/{empreinte inconnue}` | **500** | `text/html` | — |
| `/posts/next` sans `current` | **500** | `text/html` | — |
| `/posts/next?current=999999` | **500** | `text/html` | — |
| `/posts/next` sur le plus récent | 200 | `application/json` | `{"url":"/post/","title":" - "}` |
| `/posts/prev` sur le plus ancien | 200 | `application/json` | `{"url":"/post/","title":" - "}` |

État du lecteur sur le morceau le plus récent (`kossoy-sisters-what-will-we-do-with-the-baby-o`) :
`.nav-l a` vaut `/post/ibn-al-rabin-a-geneve?random=0`, et **`.nav-r a` n'existe pas** — le
gabarit ne le rend pas faute de voisin. C'est le fait qui rend sûr le passage au 404 : le
rappel AJAX n'a aucun élément à mettre à jour.

## 2. Supprimer les erreurs fatales

- [x] 2.1 `executeMd5` : vérifier que le morceau existe avant de le sérialiser. Aujourd'hui
  `toJson()` est appelée sur `false`.
- [x] 2.2 `executeNext` et `executePrev` : vérifier que le morceau courant existe avant de
  chercher son voisin. Aujourd'hui `getNextPost(Post $post, …)` reçoit le `false` d'un
  `find()` sur un identifiant absent ou inconnu.
- [x] 2.3 Vérifier qu'aucun des trois cas ne produit plus de 500, **avant même** d'avoir
  habillé quoi que ce soit. Les deux moitiés de cette story sont séparables et la première
  vaut seule.

## 3. Servir l'erreur dans le format demandé

- [x] 3.1 Écrire le rendu d'erreur au format machine : type de contenu du format demandé,
  corps construit par `ApiErrorResponse`, code de statut passé en paramètre. Le placer là où
  les quatre actions peuvent s'en servir sans le dupliquer.
- [x] 3.2 Le brancher sur `/post/{slug}` quand le format demandé est machine et que le
  morceau n'existe pas. Laisser la représentation HTML au socle.
- [x] 3.3 Le brancher sur `/post/md5/` — empreinte inconnue, **404**.
- [x] 3.4 Le brancher sur `/posts/next|prev` — `current` absent : **400** ; `current`
  inconnu : **404** ; aucun voisin : **404**.
- [x] 3.5 Vérifier que `ApiErrorResponse` est appelée et non réimplémentée : elle est écrite
  et testée depuis des mois, c'est son premier appelant.

## 4. Amender le contrat

- [x] 4.1 Déclarer les réponses d'erreur des routes concernées, avec leur schéma.
- [x] 4.2 Retirer les trois mentions « ne produit pas de `404` mais une erreur du serveur,
  non déclarée ici » : elles cessent d'être vraies.
- [x] 4.3 Retirer de `/post/{slug}` la mention que le corps du 404 reste du HTML « y compris
  lorsque `format=json` est demandé », et son jeu de paramètres qui l'exerçait — l'écart
  disparaît.
- [x] 4.4 Déclarer le schéma du corps d'erreur, et le porter aux champs de premier niveau
  que le test vérifie.
- [x] 4.5 `make configure`, vérifier que le contrat rendu a conservé ses `$ref`.

## 5. Vérification

- [x] 5.1 `docker-compose exec php php symfony test:all` — la suite passe. Relever avant et
  après.
- [x] 5.2 Comparer au relevé de 1.1 : les quatre cas servent maintenant du JSON analysable,
  avec les codes attendus, et **aucun 500**.
- [x] 5.3 Le test de contrat passe, et il **aurait échoué** sans l'amendement : le vérifier
  puis rétablir.
- [x] 5.4 Vérifier qu'aucune trace d'exécution n'apparaît dans les corps servis.
- [x] 5.5 Vérifier que la page HTML d'erreur est **inchangée** : `/post/{slug inconnu}` sans
  `format` sert toujours la même page.

### Ne pas casser le lecteur du site

- [x] 5.6 Dans un navigateur, charger le **morceau le plus récent** : les flèches rendues et
  leurs `href` sont identiques au relevé de 1.2. C'est le cas où `/posts/next` répond
  désormais 404.
- [x] 5.7 Idem sur le **morceau le plus ancien**, où c'est `/posts/prev` qui répond 404.
- [x] 5.8 Sur un morceau du milieu, vérifier que l'enchaînement fonctionne normalement — les
  deux voisins existent, rien ne doit avoir changé.
- [x] 5.9 Relever la console du navigateur sur ces trois pages : un 404 attendu ne doit pas
  y produire d'erreur JavaScript.

- [x] 5.10 `find src -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;`.
- [x] 5.11 `openspec validate servir-les-erreurs-en-json --type change --strict`.

### Mesures relevées le 2026-08-18

| demande | avant | après |
| --- | --- | --- |
| `/post/{slug inconnu}?format=json` | 404 · `text/html` | 404 · `application/json` |
| `/post/{slug inconnu}` sans format | 404 · `text/html`, 2 138 o | **inchangé** |
| `/post/md5/{empreinte inconnue}` | **500** | 404 · `application/json` |
| `/posts/next` sans `current` | **500** | 400 · `application/json` |
| `/posts/next?current={inconnu}` | **500** | 404 · `application/json` |
| `/posts/next` sur le plus récent | 200 · `{"url":"/post/","title":" - "}` | 404 · `application/json` |
| `/posts/prev` sur le plus ancien | 200 · `{"url":"/post/","title":" - "}` | 404 · `application/json` |

Aucune trace d'exécution dans aucun corps servi. Suite : **507 → 523 tests**.

### Le lecteur, vérifié et non supposé

Trois pages contrôlées au navigateur, liens relevés après résolution des appels AJAX.

| page | `.nav-l a` | `.nav-r a` |
| --- | --- | --- |
| le plus récent (`/posts/next` → 404) | `/post/ibn-al-rabin-a-geneve?random=0` | absent, avant comme après |
| le plus ancien (`/posts/prev` → 404) | `/post/art-interface-wardance?random=0` | idem |
| un morceau du milieu | `…cool-raoul…` | `…kossoy-sisters…` |

**Ni régression ni réparation : les pages sont identiques.** Une hypothèse a été formulée
puis démentie en la vérifiant — on croyait que le `200` vide écrasait le lien par
`/post/`, faisant un lien mort que ce changement aurait réparé. Contrôle fait sur l'état
antérieur : le lien valait déjà `/post/art-interface-wardance?random=0`. Les flèches sont
alimentées par `/posts/random`, non par `prev`/`next`, qui ne sont appelées que dans
d'autres branches de `layout.php`. Le gain de ce changement est donc pour l'appelant
machine seul, ce qui est exactement ce qu'il annonçait.

Les erreurs de console (« unknown error occurred when fetching the script ») apparaissent
à l'identique sur le morceau du milieu, **où aucun 404 ne survient** : elles sont
préexistantes et étrangères à ce changement.

### Une correction au test de contrat

Le test tirait des fixtures le morceau **le plus récent** comme valeur de `current`. Or le
contrat déclare désormais un `200` sur `/posts/next` — et le plus récent n'a pas de suivant,
il sert donc le `404`, rendant la réponse déclarée inatteignable.

Le test choisit maintenant un morceau ayant un voisin **de chaque côté**, en interrogeant le
modèle plutôt qu'en le déduisant de l'ordre chronologique : la relation de voisinage n'est
pas une simple chaîne, un seul des cinq morceaux publiables des fixtures a les deux. Si
aucun n'en a, le test le dit et échoue, au lieu de passer sur une couverture amoindrie.
