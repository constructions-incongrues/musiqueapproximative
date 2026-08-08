## Why

En dessous de 768 px de large, le formulaire de recherche du bandeau est purement et
simplement masqué : `src/web/frontend/assets/stylesheets/main.css` pose
`.search-container { display: none }` dans une media query. La recherche plein texte
existe et fonctionne (`/posts?q=…`), mais un visiteur sur téléphone n'a aucun moyen de
l'atteindre depuis le site — et le raccourci clavier `s`, qui donne le focus au champ,
n'a pas d'équivalent tactile.

## What Changes

- Le formulaire de recherche du bandeau reste visible et utilisable quelle que soit la
  largeur de l'écran, y compris sur mobile.
- Le champ et son bouton d'envoi s'adaptent à la largeur disponible au lieu de déborder :
  le champ prend la place restante, le bouton garde sa taille propre.
- La zone tactile du champ et du bouton respecte la hauteur minimale d'un doigt (44 px),
  et la taille de police du champ reste à 16 px pour éviter le zoom automatique d'iOS à
  la mise au point.
- Aucune modification du comportement de recherche lui-même : mêmes paramètres, même
  route, mêmes résultats.

### Hors périmètre

- La refonte du bandeau mobile (navigation, logo, liens) — seule la zone de recherche est
  touchée.
- La consolidation des feuilles de style du dépôt : `main.css` contient des media queries
  redondantes (`768`, `767`, `800`, `720`, `630`, `530`, `430`, `370`) et des blocs de
  style en dur dans `showSuccess.php`. On n'y touche pas à cette occasion.
- L'ajout d'une page ou d'une route de recherche dédiée : la recherche continue de passer
  par `/posts?q=…`.
- Le raccourci clavier `s` et le reste des raccourcis jQuery du pied de page.
- Les thèmes `quickos` et les désastres, qui surchargent le bandeau par ailleurs.

## Approche

Le socle n'a ni build CSS ni système de composants : les styles sont des fichiers servis
tels quels, et `main.css` est chargé par toutes les pages du frontend. La correction se
fait donc à l'endroit du problème — retirer la règle qui masque, et ajouter le peu de
mise en forme nécessaire pour que le formulaire tienne sur une largeur de téléphone. Pas
de nouveau fichier, pas de dépendance, pas de refonte du bandeau.

Deux options écartées :

- Remplacer le champ par une icône qui déplie le formulaire au clic : demande du
  JavaScript et un état d'ouverture, pour un bandeau qui contient déjà peu de choses.
- Déplacer la recherche dans le pied de page sur mobile : la duplique dans le HTML, ou
  impose un déplacement du bloc en CSS que la structure actuelle du bandeau ne facilite
  pas.

## Capacités

### Nouvelles capacités

Aucune.

### Capacités modifiées

- `catalogue-morceaux` : l'exigence « Liste et recherche plein texte » gagne le fait que
  le formulaire de recherche est atteignable depuis n'importe quelle page du site, quelle
  que soit la largeur d'écran. Aujourd'hui la spec décrit la recherche par le paramètre
  `q` sans rien dire de son point d'entrée dans l'interface.

## Impact

- `src/web/frontend/assets/stylesheets/main.css` : suppression de la règle qui masque
  `.search-container` sous 768 px, ajustement de la mise en forme du formulaire aux
  petites largeurs. Le bandeau y gagne `flex-wrap: wrap` : sur la même ligne que
  « Parcourir : tous les morceaux | <contributeur> », il ne restait qu'une soixantaine de
  pixels de champ de saisie. Le contenu du bandeau n'est pas modifié pour autant, seule
  sa capacité à passer à la ligne l'est.
- `src/apps/frontend/templates/layout.php` : uniquement si la mise en forme réclame un
  ajustement du balisage du formulaire — à éviter si le CSS suffit.

Le contrat public n'est pas concerné : aucune route, aucun format de sortie, aucun flux,
aucun oEmbed ni métadonnée de partage ne change. La correction est visuelle et se limite
au rendu HTML du frontend.
