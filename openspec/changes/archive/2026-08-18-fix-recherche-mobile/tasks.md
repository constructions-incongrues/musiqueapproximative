## 1. Correction de la feuille de style

- [x] 1.1 Dans `src/web/frontend/assets/stylesheets/main.css`, supprimer le bloc
      `@media screen and (max-width: 768px) { .search-container { display: none } }`
      (section « Header / Search », autour de la ligne 492)
- [x] 1.2 Rendre le formulaire `#search` adaptable à la largeur disponible : le champ
      `input.search` occupe la place restante, `input.submit` garde sa taille propre,
      sans débordement horizontal en dessous de 400 px
- [x] 1.3 Porter `input.search` et `input.submit` à une hauteur de cible d'au moins
      44 px et une taille de police d'au moins 16 px sur les écrans étroits, pour la
      saisie tactile et pour éviter la mise à l'échelle automatique d'iOS
- [x] 1.4 Vérifier que la règle `header input.search` du bloc de style en dur de
      `src/apps/frontend/modules/post/templates/showSuccess.php` (thème sombre) reste
      cohérente avec la nouvelle mise en forme, sans la réécrire

## 2. Balisage, si nécessaire

- [x] 2.1 N'ajuster `src/apps/frontend/templates/layout.php` que si le CSS seul ne suffit
      pas à tenir la largeur ; dans ce cas, se limiter au conteneur `.search-container`
      et au formulaire `#search`, sans toucher au reste du bandeau

## 3. Vérification manuelle

- [x] 3.1 Vider le cache (`docker-compose exec php php symfony cache:clear`), puis
      charger `http://localhost:8080/` dans un navigateur ramené à 360 px de large :
      le champ de recherche et le bouton « Search ! » sont visibles dans le bandeau
- [x] 3.2 À 360 px, saisir un terme présent dans le catalogue et envoyer le formulaire :
      la page atteinte est `/posts?q=<terme>`, elle liste les morceaux correspondants,
      et son titre annonce le nombre de résultats
- [x] 3.3 À 360 px, vérifier qu'aucune barre de défilement horizontale n'apparaît sur la
      page, ni sur `/` ni sur `/posts`
- [x] 3.4 Reprendre 3.1 aux largeurs 320 px, 480 px, 768 px et 1280 px : le formulaire
      reste visible et utilisable à chacune
- [x] 3.5 Sur un terminal tactile ou l'émulation mobile du navigateur, mettre le champ au
      point : la page ne se met pas à l'échelle automatiquement
- [x] 3.6 Charger `http://localhost:8080/posts?q=<terme>` à 360 px : le champ de
      recherche du bandeau contient bien `<terme>`
- [x] 3.7 Recharger une page de morceau plusieurs fois pour tirer différents désastres,
      et vérifier à 360 px que le formulaire reste visible avec les surcharges de style
      qu'ils appliquent

### Notes de vérification

Environnement : le conteneur de ce worktree publie Nginx sur le port **8081**, pas 8080
(`docker-compose ps`). Les URL ci-dessus ont donc été demandées sur `http://localhost:8081`.
La redirection de `/` perd le port — la vérification est passée par
`/post/kossoy-sisters-what-will-we-do-with-the-baby-o` et `/posts`.

Mesures relevées (largeur du champ / du bouton, débordement horizontal) :

| Largeur | Champ | Bouton | `scrollWidth` | Débordement |
| ------- | ----- | ------ | ------------- | ----------- |
| 320 px  | 223 px | 72 × 44 px | 320 | non |
| 360 px  | 265 px | 70 × 44 px | 360 | non |
| 480 px  | 383 px | 72 × 44 px | 480 | non |
| 768 px  | 671 px | 72 × 44 px | 768 | non |
| 1280 px | 141 px | 50 px | 1280 | non — rendu de bureau inchangé, la media query ne s'applique plus |

- 3.2 : recherche du terme `baby` depuis le bandeau à 360 px → `/posts?q=baby`, titre
  `23 résultat(s) pour la recherche "baby"`.
- 3.5 : mené en émulation mobile (agent Pixel 8, 5 points tactiles). `visualViewport.scale`
  reste à 1 à la mise au point du champ, dont la taille de police calculée est bien 16 px.
  C'est cette taille de police, et elle seule, qui satisfait la vérification. Le
  comportement de Safari iOS lui-même n'a pas été observé, faute de terminal.

  La balise `viewport` du site pose par ailleurs `maximum-scale=1, minimum-scale=1`
  (`src/apps/frontend/templates/layout.php:7`). Une première rédaction de cette note s'en
  prévalait comme d'une garantie supplémentaire. C'est un mauvais argument : cette
  déclaration n'empêche pas seulement la mise à l'échelle automatique, elle empêche aussi
  le visiteur d'agrandir la page de lui-même. Analyse en fin de fichier.
- 3.7 : mené par inspection exhaustive des feuilles de désastres plutôt que par tirages
  successifs. Une seule d'entre elles cible le bandeau — `desastres/danse`, qui applique
  au `header` une rotation de ±1° sans toucher ni à sa mise en page ni à ses fonds.
  Aucune ne cible `.search-container`, `#search` ni `input.search`. Rendu contrôlé sur une
  page de morceau réelle, formulaire visible.

Deux constats apparus en cours de route, corrigés dans le même geste :

1. Le champ sur sa propre ligne se pose sur le fond blanc du contenu ; sans bordure
   (`input.search { border: none }` dans la feuille existante) il devenait indiscernable.
   D'où le `border: 1px solid #000` posé sur le champ et le bouton dans la media query.
2. Le contenu démarre exactement au bas du bandeau et masquait la bordure basse du champ,
   y compris le soulignement blanc du thème sombre. D'où la marge verticale de 8 px sur
   `.search-container`.

Le thème sombre du bandeau (le bloc `<style>` de `showSuccess.php`) n'est actif que
lorsque le glitch du logo se déclenche. Il a été vérifié en forçant ses règles dans la
page : `header input.search { border-bottom: 1px solid #fff !important }` prend bien le
dessus sur la nouvelle bordure, et le champ reste lisible sur le bandeau noir.

### Constat annexe : le verrouillage du zoom

<!-- incongru-voix: lessig — zoom du visiteur interdit par la balise viewport,
     régulé par l'architecture — recours: aucun côté site, partiel côté navigateur -->

Hors périmètre de ce changement, mais relevé en le vérifiant, et consigné ici pour que la
décision soit prise sciemment plutôt que reconduite par inertie.

```
CONTRAINTE : agrandir la page est impossible — pour tout visiteur mobile,
             au premier chef celui qui en a besoin pour lire

  loi           rien. L'obligation d'accessibilité (loi 2005-102 art. 47,
                décret 2019-768) vise l'État, les collectivités et les
                entreprises au-delà de 250 M€ de chiffre d'affaires en France.
                Un site de collectif n'y entre pas. L'European Accessibility
                Act (ordonnance 2023-859, applicable depuis juin 2025) vise
                les services marchands ; pas davantage.

  norme         WCAG 2.1 critère 1.4.4 (agrandissement jusqu'à 200 %) et RGAA
                critère 10.4. maximum-scale est un anti-pattern documenté
                depuis une douzaine d'années. La sanction est venue des
                navigateurs eux-mêmes : Safari iOS ignore la déclaration
                depuis iOS 10, Chrome Android l'ignore si le visiteur a activé
                « Forcer l'activation du zoom ».

  prix          pour le visiteur : nul sur iOS, où la règle ne s'applique plus.
                Sur Chrome Android : trois écrans de réglages, à condition de
                savoir que l'option existe. Sur un navigateur ancien ou une
                webview : infini.
                pour le site : nul. Le seul champ de saisie du frontend est
                celui de la recherche, et il est désormais à 16 px — la balise
                ne protège donc plus rien. `input.mailing` n'existe plus dans
                aucun template, c'est du CSS mort.

  architecture  la déclaration interdit le geste. Elle est appliquée partout
                où le navigateur ne l'a pas désarmée.

  RECOURS       aucun du côté du site : pas de réglage, pas de notification.
                Le visiteur qui pince sans effet ne sait pas qu'une règle vient
                de lui être appliquée — il conclut que le site est mal fait.
                Le seul recours existant a été créé unilatéralement par Apple
                et Google à sa place, contre l'auteur du site.
```

Deux choses en découlent.

La première est que cette règle n'a jamais été votée comme une règle. Elle a été écrite
dans une balise, en une ligne, sans que personne ait à défendre l'idée « les visiteurs de
ce site n'ont pas le droit d'agrandir le texte ». Posée en ces termes devant le collectif,
elle n'aurait pas passé le premier tour.

La seconde est que la réparation est d'une ligne et sans contrepartie : retirer
`maximum-scale=1, minimum-scale=1` de `layout.php:7`. La vérification du zoom-au-focus
menée ci-dessus tient toute seule sur `font-size: 16px`.

Cela reste un autre changement que celui-ci, et volontairement : une contrainte qui pèse
sur les visiteurs mérite sa propre décision, pas d'être glissée dans un correctif de mise
en page.

