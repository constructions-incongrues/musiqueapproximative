# Tâches

Pas de `specs/` : `skip_specs` est déclaré. Corriger un fichier de configuration de l'outil
de planification ne change aucun comportement observable du site. Pas de `design.md`.

## 1. Mesurer chaque affirmation avant de la corriger

- [x] 1.1 Couverture : `test:all` donne **21 fichiers, 625 tests, tous verts**. Six fichiers
  fonctionnels du frontend exercent le contrat public.
- [x] 1.2 Doctrine : `src/composer.lock` verrouille `lexpress/doctrine1` en **v1.4.6**. Le
  contexte disait 1.3.
- [x] 1.3 Piège relevé au passage : `Doctrine_Core::VERSION` vaut **`1.2.4`** dans le code
  vendu, constante que le fork n'a jamais mise à jour. Qui vérifie la version en lisant le
  code obtient une troisième valeur, fausse elle aussi. **Écrit dans le contexte** pour que
  personne ne s'y reprenne.
- [x] 1.4 Moteur : `docker-compose.yml` monte **`mariadb:10.11`**, et la production tourne
  sur MariaDB 10.11 — c'est écrit dans `deploiement.adoc`. Le contexte disait MySQL.
- [x] 1.5 JSON : le contrat OpenAPI et `CLAUDE.md` disent tous deux que les formes **ne sont
  pas** conformes à JSON:API 1.0, et `openspec/discovery.md` consigne que la migration a été
  écartée. Le contexte renvoyait pourtant à `jsonapi.org`.

## 2. Corriger

- [x] 2.1 Remplacer la phrase sur la couverture par la mesure, en **nommant** les fichiers
  qui exercent le contrat — un chiffre seul se périme sans qu'on sache ce qu'il recouvrait.
- [x] 2.2 Renvoyer, pour ce qui n'est pas couvert, au contrat OpenAPI, qui déclare lui-même
  sa proportion vérifiée. Ne pas tenir ce compte à deux endroits.
- [x] 2.3 Corriger la pile et ajouter l'avertissement sur `Doctrine_Core::VERSION`.
- [x] 2.4 Remplacer « jsonapi.org » par ce qui est vrai, et renvoyer aux deux adresses où le
  contrat est servi.

## 3. Élargissement assumé

- [x] 3.1 Le packet de la story ne visait qu'une phrase et déclarait le reste « vérifié
  exact ». **Cette vérification était fausse.** Trois autres affirmations l'étaient aussi.
- [x] 3.2 N'en corriger qu'une aurait laissé un fichier dont la raison d'être est
  d'être exact continuer à en propager trois. L'élargissement est **annoncé dans la
  proposition**, conformément à la règle du dépôt — un change ne touche que ce qu'il
  annonce, et celui-ci annonce ce qu'il touche.
- [x] 3.3 Re-vérifier le reste du bloc plutôt que reconduire l'assertion qui s'est révélée
  fausse : routes, applications, modèle, déploiement, banque de mémoire — **exacts**.

## 4. Vérification

- [x] 4.1 `openspec` relit la configuration sans erreur.
- [x] 4.2 Les changes existants valident toujours.

### Vérification manuelle

- [ ] 4.3 À la prochaine proposition, vérifier que le contexte injecté porte la couverture
  réelle. C'est le seul endroit où l'effet est observable : le contexte ne se voit que dans
  ce qu'il produit.
