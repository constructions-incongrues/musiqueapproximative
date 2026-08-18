# Tâches

Pas de `specs/` : `skip_specs` déclaré. Aucun comportement observable du site ne change —
la story mesure et décide. Pas de `design.md`.

## 1. Inventorier

- [x] 1.1 Recenser les `*-dist` hors `vendor/`. **Deux fichiers** :
  `apps/frontend/config/app.yml-dist`, `config/databases.yml-dist`. Le contrat OpenAPI
  était le troisième et a quitté la convention le soir même.
- [x] 1.2 Établir pour chacun s'il **peut** cesser d'être un gabarit. **Non, pour les
  deux** : identifiants de base, jeton Cloudflare, valeurs propres au domaine. Les verser
  rendus serait publier des secrets.

## 2. Mesurer l'écart réel, depuis la production

- [x] 2.1 Rendre les valeurs attendues depuis `etc/www.musiqueapproximative.net/.env` et
  les confronter à ce que le site sert. Ne pas comparer des fichiers — comparer ce qui
  arrive au visiteur.
- [x] 2.2 `app.yml` : titre de page, URL des pistes, `og:url`, autoplay. **Quatre valeurs
  sur quatre concordent.** Rien n'a dérivé.
- [x] 2.3 `databases.yml` : **aucune valeur n'est observable depuis l'extérieur.** C'est le
  point dur de cette story et il est consigné plutôt que contourné — la ligne `encoding:
  utf8mb4`, posée aujourd'hui, ne se vérifie pas depuis un navigateur. Si elle n'était pas
  rendue, la connexion convertirait toujours et personne ne le saurait avant le prochain
  titre détruit.

## 3. Ce que la mesure a retourné

- [x] 3.1 **Résultat négatif : rien n'a dérivé.** La story supposait un parc de fichiers
  périmés ; il n'y en a pas.
- [x] 3.2 Le danger n'était donc pas leur état, c'est qu'**aucun mécanisme ne signalerait
  leur dérive**. Ce déplacement est le vrai produit de la story.
- [x] 3.3 Consigner le fait d'exploitation qui a mis le site en 500 ce jour :
  **`make configure` n'est pas exécutable sur le serveur.** Il lit `src/.env`, qui n'y
  existe pas — montage Docker en développement. Lancé là-bas, la liste blanche est vide,
  `envsubst` ne substitue rien, et toute la configuration devient des gabarits bruts.

## 4. La page

- [x] 4.1 Écrire `docs/modules/ROOT/pages/fichiers-de-configuration.adoc`.
- [x] 4.2 L'inscrire à la navigation.

## 5. Ce que ce change ne ferme pas

- [ ] 5.1 **Rendre la dérive détectable.** C'est le vrai manque et il déborde ce fichier :
  il vaut pour le contrat comme pour la configuration. Story 23.
- [ ] 5.2 **Rendre `make configure` exécutable sur le serveur**, ou l'interdire
  explicitement. Le documenter ne fait que prévenir qui lit la documentation ; celui qui
  suit un conseil pressé ne la lit pas. Demande de décider où vit le `.env` de production.
