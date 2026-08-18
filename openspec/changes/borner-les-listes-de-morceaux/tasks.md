## 1. Relever l'état de départ

- [ ] 1.1 Relever, pour `/posts` dans chaque format et pour `/posts?c=<contributeur>` et
  `/posts?q=<terme>`, le nombre de morceaux servis et le poids de la réponse. C'est la
  mesure contre laquelle le gain se démontrera.
- [ ] 1.2 Relever le temps de génération de `/posts?format=json` **à froid**, cache vidé.
  Le plan annonce 16,5 s en production ; mesurer ce qu'il vaut ici, pour disposer d'un
  avant et d'un après.
- [ ] 1.3 Relever les titres actuels de `/posts?c=…` et `/posts?q=…` : ils comptent les
  morceaux servis. Ce sont eux qui doivent continuer de dire vrai.

## 2. Brancher le bornage dans la requête

- [ ] 2.1 Ajouter le décalage à `buildOnlinePostsQuery` — il gère la limite, pas l'offset.
- [ ] 2.2 **Changer le traitement d'une limite invalide.** Aujourd'hui `max(0, (int) $count)`
  traduit une valeur négative par *aucune limite* ; conservé tel quel, `limit=-1` servirait
  les 8 097 morceaux et le bornage se contournerait par accident. Une valeur négative, nulle
  ou non numérique doit ramener au **défaut**.
- [ ] 2.3 Vérifier que `executeFeed`, qui passe déjà `count=50`, n'est pas affecté par ce
  changement de traitement.

## 3. Poser les paramètres dans l'action

- [ ] 3.1 Dans `executeList`, lire `limit` et `offset` et les passer à la requête, avec
  **50** par défaut. Le chemin sans `q` d'abord.
- [ ] 3.2 Borner le chemin de recherche. `PostTable::search()` renvoie un **tableau PHP**,
  pas une requête : la limite SQL ne s'y applique pas, il faut découper le tableau. Le
  faire au plus près de l'endroit où il est construit.
- [ ] 3.3 Vérifier que le bornage vaut pour **tous** les formats servis par cette action,
  HTML compris — c'est la même action, il ne devrait rien y avoir à faire de plus, mais le
  constater.

## 4. Faire dire vrai aux libellés

- [ ] 4.1 Remplacer `count($posts)` par le **total** dans le titre « %d résultat(s) pour la
  recherche ». Sans cela le site annoncerait « 50 résultats » là où il y en a 993.
- [ ] 4.2 Idem pour « %s a posté %d morceau(x) à ce jour ». `countOnlinePosts()` existe
  déjà ; c'est un second appel à la base, assumé.
- [ ] 4.3 Exposer le total dans la représentation JSON de la liste, pour qu'un consommateur
  sache ce qu'il n'a pas reçu.
- [ ] 4.4 **Afficher le total sur la page HTML de liste.** Le visiteur ne lira jamais le
  contrat et ne connaîtra jamais `offset` : sans cette ligne, une page de cinquante liens ne
  se distingue pas d'un catalogue de cinquante morceaux, et il conclura « c'est tout ce
  qu'il y a ». Le total est déjà calculé par les tâches 4.1 et 4.2 — c'est une ligne de
  gabarit. Voir le tableau des modalités dans `design.md` : c'est la seule notification que
  le visiteur reçoive, et elle ne lui donne toujours aucun recours.

## 5. Amender le contrat

- [ ] 5.1 Déclarer `limit` et `offset` sur `/posts` dans `src/web/openapi.yaml-dist`, avec
  leur défaut et leur comportement aux valeurs absurdes.
- [ ] 5.2 Retirer de la description de `/posts` la mention « **Aucun bornage.** La liste
  complète est servie » : elle cesse d'être vraie.
- [ ] 5.3 Déclarer le champ de total dans le schéma de l'enveloppe de liste, et le porter à
  la liste des champs de premier niveau que le test vérifie.
- [ ] 5.4 `make configure`, puis vérifier que le contrat rendu a conservé ses `$ref`.

## 6. Vérification

- [ ] 6.1 `docker-compose exec php php symfony test:all` — la suite passe.
- [ ] 6.2 Le test de contrat passe, et il **aurait échoué** sans l'amendement : le vérifier
  en rétablissant temporairement l'ancienne description, puis rétablir.
- [ ] 6.3 Comparer au relevé de 1.1 : `/posts` sert 50 morceaux dans chaque format, et le
  poids a chuté. Donner les deux chiffres.
- [ ] 6.4 Reprendre la mesure à froid de 1.2. Le gain de **temps** est attendu sur le
  catalogue, où la limite descend au SQL ; il est attendu **nul ou faible** sur le chemin de
  recherche, où le tableau est déjà chargé quand on le coupe. Consigner les deux, sans
  attribuer au bornage un gain qu'il n'apporte pas.
- [ ] 6.5 Vérifier les titres : `/posts?c=<le plus prolifique>` annonce son **total** de
  morceaux, pas 50. `/posts?q=<terme fréquent>` annonce le **total** de résultats.
- [ ] 6.6 Vérifier que le catalogue entier reste atteignable : demander une tranche assez
  large et constater qu'on récupère tout.
- [ ] 6.7 Vérifier le comportement aux bords : `limit=0`, `limit=-1`, `limit=abc`,
  `offset` au-delà du total, `limit` et `offset` combinés à `q` et à `c`. Aucun ne doit
  produire d'erreur, et aucun ne doit servir le catalogue entier.
- [ ] 6.8 Vérifier que `/posts/feed` sert toujours ses 50 morceaux et que `/post/:slug` est
  inchangé.
- [ ] 6.9 `find src -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;`.
- [ ] 6.10 `openspec validate borner-les-listes-de-morceaux --type change --strict`.

### Ce que ce changement laisse au visiteur

- [ ] 6.11 Charger `/posts` en HTML **sans rien connaître de ce changement**, et répondre à
  une seule question : cette page laisse-t-elle croire que le site contient cinquante
  morceaux ? Si oui, la tâche 4.4 n'est pas faite, quoi qu'en dise le code.
- [ ] 6.12 Constater et consigner : il n'y a **aucun moyen visible** d'aller au-delà du
  cinquantième morceau. C'est le demi-service assumé par la proposition. Vérifier qu'aucune
  page ne prétend le contraire — pas de lien « suivant » mort, pas de compteur laissant
  croire à une navigation qui n'existe pas.
