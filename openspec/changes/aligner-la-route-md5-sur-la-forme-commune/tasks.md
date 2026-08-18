> Pas de `design.md`. Son instruction est conditionnelle et ce changement enveloppe une
> réponse : un module, aucune dépendance, aucune migration. La contrainte du socle qui
> oriente la solution — les quatre `$.get` de `layout.php` qu'il ne faut pas casser — est
> consignée dans la proposition, comme la règle du dépôt le prévoit dans ce cas.

## 1. Relever l'état de départ

- [ ] 1.1 Relever les deux réponses côte à côte : `/post/{slug}?format=json` et
  `/post/md5/{md5sum}` pour le même morceau. Constater que les clés de l'objet sont
  **identiques** et que seule l'enveloppe diffère. C'est ce qui borne cette story à
  l'enveloppe.
- [ ] 1.2 Relever ce que servent `/posts/next`, `/posts/prev` et `/posts/random` :
  `{url, title}`. Ce sont elles qu'il ne faut **pas** toucher.
- [ ] 1.3 Relever les quatre appels de `layout.php` qui consomment ces routes, pour savoir
  précisément ce qui casserait.

## 2. Envelopper la réponse

- [ ] 2.1 Dans `executeMd5`, servir `{"posts":[<morceau>]}` au lieu de l'objet nu. Le
  gabarit de liste montre la forme attendue ; il porte le commentaire qui la justifie —
  « Even single ressources are displayed as lists ».
- [ ] 2.2 Ne pas toucher à `Post::toJson()`, partagé par toutes les représentations. Ce
  changement enveloppe, il ne modifie pas l'objet.
- [ ] 2.3 Ne pas ajouter de `forward404Unless` : une empreinte inconnue produit
  aujourd'hui une erreur fatale, et la corriger est la story 5. Le constater, le laisser.

## 3. Amender le contrat

- [ ] 3.1 `/post/md5/{md5sum}` passe du schéma de l'objet nu à celui de l'enveloppe.
- [ ] 3.2 Retirer de la description de la route la mention « Sert l'objet **nu**, sans
  l'enveloppe… Deux contrats pour le même objet ; c'est un écart connu » : l'écart n'existe
  plus. Retirer de même la mention de cet écart dans `info.description`.
- [ ] 3.3 Vérifier que la description des routes du lecteur dit toujours que leur forme est
  **délibérée** — c'est désormais la seule divergence assumée, et elle doit rester lisible.
- [ ] 3.4 Le test de contrat vérifie les champs de premier niveau : `/post/md5/` doit
  maintenant présenter `posts`, et non plus la liste des champs de l'objet. C'est le
  contrat qui le dit, le test suit.
- [ ] 3.5 `make configure`, puis vérifier que le contrat rendu a conservé ses `$ref`.

## 4. Vérification

- [ ] 4.1 `docker-compose exec php php symfony test:all` — la suite passe.
- [ ] 4.2 Le test de contrat passe, et il **aurait échoué** sans l'amendement : le vérifier
  en rétablissant temporairement l'ancien schéma, puis rétablir.
- [ ] 4.3 Comparer au relevé de 1.1 : les deux réponses ont maintenant la même forme.
  Le démontrer en comparant les clés de premier niveau, qui doivent être identiques.
- [ ] 4.4 **Ne pas casser la navigation du site.** Vérifier que `/posts/next`, `/posts/prev`
  et `/posts/random` servent toujours `{url, title}`, inchangés.
- [ ] 4.5 Charger une page de morceau dans un navigateur et exercer les raccourcis `j`,
  `k` et `r` : l'enchaînement fonctionne. C'est la vérification qui compte — les quatre
  `$.get` de `layout.php` sont le seul consommateur identifié de tout ce JSON.
- [ ] 4.6 Vérifier qu'une empreinte inconnue se comporte **exactement comme avant** : ce
  changement ne doit ni améliorer ni aggraver le cas d'erreur, qui appartient à la story 5.
- [ ] 4.7 `find src -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;`.
- [ ] 4.8 `openspec validate aligner-la-route-md5-sur-la-forme-commune --type change --strict`.
