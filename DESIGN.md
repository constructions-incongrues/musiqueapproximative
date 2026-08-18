# Système de design — Musique Approximative

> **Ce document décrit le système existant, pas un système souhaité.** Il a été
> établi par lecture de la source et observation du site en production le
> 2026-08-18. Chaque valeur ci-dessous a été relevée, pas proposée.
>
> Quand le fichier source et le rendu divergent, les deux sont notés. La section
> « Écarts relevés » liste ces divergences ; elle constate, elle ne corrige pas.

## Contexte produit

- **Ce que c'est** : une playlist quotidienne collaborative. Un morceau par jour,
  posté par un membre du collectif Constructions Incongrues, accompagné d'un texte.
- **Pour qui** : l'auditeur qui ouvre le site pour écouter et enchaîner ; le DJ de
  soirée qui cherche vite depuis son téléphone ; le contributeur qui pose son
  morceau et veut le retrouver ; l'intégrateur qui consomme les formats machine.
- **Échelle** : 8 097 morceaux, 206 contributeurs, en ligne depuis plus de quinze ans.
- **Type** : site éditorial. Pas une application, pas une vitrine.
- **Ce que le site dit de lui-même** : « C'est l'exutoire anarchique d'une bande de
  mélomanes fêlé⋅e⋅s. L'arbitraire y est roi et on s'y amuse bien. »

Cette dernière phrase n'est pas un slogan, c'est une spécification. Elle explique
le mécanisme décrit plus bas.

## Direction esthétique

- **Direction** : minimalisme brutal, monochrome strict, plus une couche de chaos délibéré.
- **Niveau de décoration** : nul sur la base. Aucun dégradé, aucune ombre, aucun
  arrondi, aucune icône. La typographie et l'inversion font tout le travail.
- **Humeur** : une affiche sérigraphiée. Contraste maximal, aucune demi-teinte
  décorative, un geste par écran.

### Le geste central : l'inversion

L'inversion noir/blanc est la seule figure de style du système, et elle opère à
trois échelles :

| échelle     | ce qui s'inverse                                                                                                                                                                            |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Structure   | Bandeau noir → dalle blanche de contenu → pied noir. La page morceau est une carte blanche posée sur du noir ; la page liste, elle, reste blanche sur noir, donc en négatif de la première. |
| Interaction | Tout survol de lien inverse le fond et le texte (`#fff`/`#000`). C'est le seul retour visuel du système.                                                                                    |
| Sélection   | `::selection` s'inverse par zone : blanc sur noir dans l'en-tête et les listes, noir sur blanc dans le corps de texte.                                                                      |

## Typographie

- **Titres, affichage, boutons** : **Arvo** — un slab serif géométrique. Porte les
  titres de morceau, les titres de section, les libellés de bouton.
- **Corps, listes, en-tête** : **Rambla** — une linéale humaniste. Porte les
  paragraphes, la liste des morceaux, la liste des contributeurs, le bandeau.
- **Données, code** : aucune. Le site n'a pas de tableaux ni de code.
- **Chargement** : `@import url(//fonts.googleapis.com/css?family=Arvo)` et de même
  pour Rambla, en tête de `main.css`. Deux remarques factuelles : l'URL est en
  protocol-relative (`//`), forme abandonnée depuis que le web est en HTTPS ; et un
  `@import` en CSS bloque le rendu en série plutôt qu'en parallèle d'un `<link>`.

### Échelle typographique

L'échelle du titre principal est déclarée en huit points d'arrêt manuels, sans
`clamp()` :

| viewport | déclaré | **rendu réel** |
| -------- | ------- | -------------- |
| ≥ 1200px | 4.9rem  | 78,4px         |
| ≤ 1050px | 3.5rem  | 56px           |
| ≤ 800px  | 3.5rem  | 56px           |
| ≤ 720px  | 3rem    | 48px           |
| ≤ 630px  | 2.5rem  | 40px           |
| ≤ 530px  | 2rem    | 32px           |
| ≤ 430px  | 1.5rem  | 24px           |
| ≤ 370px  | 1.2rem  | 19,2px         |

La colonne « rendu réel » diverge de l'intention : voir « Écarts relevés », point 1.
C'est probablement ce qui explique qu'il ait fallu huit paliers pour un seul élément.

Chaque règle de taille porte une double déclaration (`font-size: 13px;
font-size: 1rem;`), repli destiné à IE8. Les deux valeurs ne se correspondent pas,
et aucune des deux n'est celle qui s'affiche.

## Couleur

- **Approche** : monochrome. Il n'y a **pas de couleur d'accent**, et c'est un choix
  structurant, pas un oubli.

| rôle                            | valeur                      |
| ------------------------------- | --------------------------- |
| Fond de page                    | `#000`                      |
| Texte sur fond de page          | `#fff`                      |
| Dalle de contenu                | `#fff`                      |
| Texte de la dalle               | `#000`                      |
| Sous-titre de morceau (`h2`)    | `#555`                      |
| Lien dans un texte              | `#555050`, `#000` au survol |
| Lien de liste, contributeur     | `#bbb`                      |
| Barre de progression du lecteur | gris moyen                  |

- **Sémantique** : aucune. Pas de vert de succès, pas de rouge d'erreur. Le site
  n'a pas de formulaire à valider hors la recherche.
- **Mode sombre** : sans objet. Le mode sombre _est_ le mode par défaut. Le blanc
  n'apparaît qu'en dalle de contenu.

### Pourquoi le monochrome est le socle du reste

Le mécanisme des désastres injecte des couleurs violentes — cyan, magenta, glitch.
Ces irruptions ne sont lisibles **que parce que la base n'a aucune couleur**. Une
palette d'accent transformerait chaque désastre en bruit parmi d'autres. La
contrainte n'est pas une austérité, c'est ce qui rend l'effet possible.

## Espacement

- **Unité de base** : aucune. Il n'y a pas d'échelle.
- **Valeurs observées** : 2, 3, 5, 8, 10, 15, 20, 30, 40, 70px, posées au cas par cas.
- **Densité** : serrée sur les listes, aérée sur la page morceau.

C'est un constat, pas un reproche : le site n'a pas de bibliothèque de composants
à tenir cohérente, il a une poignée de gabarits.

## Mise en page

- **Approche** : une grille pour le contenu, du flex pour tout le reste.
- **Grille principale** : `.content > .wrapper` en
  `grid-template-columns: 5% auto 5%`, qui passe à `100%` sous 768px. Les 5% de
  chaque côté sont les marges noires qui encadrent la dalle blanche.
- **Bandeau** : `display: flex` avec `justify-content: space-between`, `min-height: 40px`,
  `padding: 0 20px`. Passe en `flex-wrap: wrap` sous 768px pour que le champ de
  recherche prenne sa propre ligne.
- **Contributeurs** : deux colonnes flex à 48%, `min-width: 300px`, qui s'empilent
  sous 768px en inversant l'ordre (« À propos » remonte en premier).
- **Point d'arrêt principal** : 768px. Les autres (1200, 1050, 800, 720, 630, 530,
  480, 430, 370) ne servent que l'échelle du titre et deux ajustements.
- **Rayon de bordure** : aucun, nulle part.
- **Largeur maximale de contenu** : aucune. La dalle occupe 90% du viewport quelle
  que soit sa largeur.

## Mouvement

- **Approche** : quasi nulle sur la base. Le seul mouvement du système de base est
  `opacity: 0.7` au survol des flèches de navigation.
- **Aucune transition** n'est déclarée. Les inversions de survol sont instantanées.
- Tout le reste du mouvement appartient à la couche désastre.

## La couche désastre

C'est ce qui distingue ce système de tout autre, et un document qui l'omettrait
décrirait un site qui n'existe pas.

`sfDesastrePlugin` applique aléatoirement, selon des règles déclarées dans
`src/apps/frontend/config/desastres.yml`, une des **17 recettes** livrées sous
`src/web/desastres/` : `amour`, `bleu`, `danse`, `fish`, `kraftwerk`, `light`,
`mamie`, `mangelettres`, `musique`, `noir`, `postillons`, `redirect`, `robot`,
`sale`, `shared`, `splitouine`, `tts`.

Chaque recette est une surcouche CSS — et parfois JS — qui **écrase** le système de
base. Deux exemples relevés :

- `noir` repeint `.content` en `rgb(25,25,25)`, passe les titres en blanc, inverse
  les flèches par `filter: invert(1)`, et fait dériver un disque noir flouté de
  200px en `animation: moving 12s infinite alternate`.
- `bleu` applique un `@keyframes glitch` de 1s sur les `h2`, avec décalages en
  `translate`/`skew` et bascules de teinte vers le cyan et le violet.

**Conséquence pour quiconque travaille sur ce design** : le système de base n'est
pas un contrat d'apparence, c'est un _état de repos_. Un désastre peut légitimement
le contredire. Une revue visuelle qui signalerait un désastre comme une incohérence
se tromperait de cible.

**Ce qui reste néanmoins invariant**, et qu'un désastre ne devrait pas casser :
le morceau reste lisible, jouable et partageable. Le reste est négociable par
construction.

## Écarts relevés

Divergences entre ce que la source déclare et ce que le navigateur rend, constatées
en production le 2026-08-18. Aucune n'est corrigée ici.

### 1. Toute l'échelle typographique rend 1,6× plus grand que ce qui est écrit

`main.css` ouvre par `html { font-size: 62.5% }`, la convention qui fait valoir
`1rem` pour 10px. Mais `layout.php` charge `reset.css` **après** `main.css`, et le
reset contient `html, body, … { font-size: 100%; font: inherit }`. La racine
revient donc à 16px.

Vérifié dans le navigateur : `getComputedStyle(document.documentElement).fontSize`
vaut `16px`. Chaque valeur en `rem` du fichier rend 1,6× sa taille voulue —
`4.9rem` donne 78px au lieu de 49px.

Le correctif tient en une ligne, mais il changerait la taille de **tous** les textes
du site d'un coup. Ce n'est pas un nettoyage, c'est une décision.

### 2. Les liens de format échappent au système

`Autre formats : json xspf` s'affiche en `rgb(0, 0, 238)` — le bleu par défaut des
navigateurs — en **Times**, souligné. Dans un système monochrome en Arvo et Rambla,
c'est le seul endroit du site qui ne porte aucun style.

### 3. Le texte « À propos » est composé sans interligne

`.about p` calcule `font-size: 16px` pour `line-height: 16px`, soit un interligne de
1,0. Combiné à `text-align: justify`, sur un viewport de 375px, cela produit un pavé
compact traversé de lézardes. C'est le plus long texte du site.

### 4. Le fichier de thème est vide et part quand même

`src/web/theme/musiqueapproximative/main.css` pèse **0 octet**. Il est chargé à
chaque rendu de page, confirmé parmi les cinq feuilles de la page en production.
Le mécanisme de thème existe — un second thème, `quickos`, est livré — mais le
thème par défaut ne s'en sert pas.

### 5. Six feuilles de style et deux habillages de lecteur sont morts

Présents dans l'arbre, jamais chargés par `layout.php` :

| fichier                             | lignes |
| ----------------------------------- | ------ |
| `ie-rtl.css`                        | 2 965  |
| `player.css`                        | 640    |
| `demo.css`                          | 308    |
| `backup_1.css`                      | 187    |
| habillage `player/skin/ma`          | —      |
| habillage `player/skin/blue.monday` | —      |

À quoi s'ajoute `ie.css`, chargé lui, mais sous condition `[if lt IE 9]` : 2 965
lignes servies à un navigateur que plus personne n'utilise.

## Journal des décisions

| Date       | Décision                                                                           | Motif                                                                                                                                                                                                                          |
| ---------- | ---------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 2026-08-18 | Document créé par déduction de l'existant, non par proposition                     | Le site a un design depuis quinze ans mais aucune source de vérité : le CSS vit dans `main.css`, dans 206 lignes en ligne de `showSuccess.php`, et dans un fichier de thème vide. On ne redessine pas ce qu'on n'a pas décrit. |
| 2026-08-18 | La couche désastre est traitée comme une partie du système, pas comme une anomalie | Une revue visuelle qui la signalerait comme incohérence se tromperait : l'arbitraire est le propos déclaré du site.                                                                                                            |
| 2026-08-18 | Les écarts sont constatés, pas corrigés                                            | Le premier d'entre eux change la taille de tous les textes du site. C'est une décision de produit, pas une tâche de ménage.                                                                                                    |
