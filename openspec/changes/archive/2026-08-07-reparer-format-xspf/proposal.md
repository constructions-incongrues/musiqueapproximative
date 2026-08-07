## Why

**Le format XSPF renvoie une erreur 500 avec un corps vide**, sur les deux routes qui le
proposent. Relevé le 2 août 2026 contre la production :

| Requête | Statut | Type de contenu | Taille |
|---|---|---|---|
| `/posts` | 200 | `text/html` | 3 701 373 o |
| `/posts?format=json` | 200 | `application/json` | 7 947 865 o |
| `/posts?format=max` | 200 | `application/maxmsp+text` | 2 509 715 o |
| `/posts?format=xspf` | **500** | `text/html` | **0 o** |

`/post/:slug?format=xspf` échoue de même. Les trois autres formats fonctionnent.

Ce n'est pas un coin obscur : la page de liste **annonce elle-même** ce format, par un
`<link rel="alternate" type="application/xspf+xml">` et par un lien visible sous
« Servez-vous ! ». Le site propose donc au visiteur, et à tout agrégateur qui lit ses
métadonnées, une adresse qui échoue.

L'écart n'a été vu que parce que le corpus de specs le rendait lisible. Sans exigence
écrite sur les types de contenu, un 500 sur un format de playlist passait pour normal.

### Deux causes distinctes, pour un même symptôme

**Sur la liste** — `listSuccess.xspf.php` charge une dépendance PEAR :

```php
set_include_path(get_include_path().':/usr/share/php');
require('File/XSPF.php');
```

Le `Dockerfile` installe `bash curl gettext git make zip` et les extensions `opcache` et
`pdo_mysql`. **Ni PEAR, ni `File_XSPF`.** Le `require` échoue fatalement, d'où le 500 au
corps vide. La dépendance existait sans doute sur l'hébergement d'origine ; elle n'a jamais
été portée dans l'image.

**Sur un morceau isolé** — il n'existe aucun gabarit `showSuccess.xspf.php`. Les autres
sont là : `showSuccess.json.php`, `showSuccess.max.php`, `showSuccess.php`. Symfony ne
trouve pas la vue et échoue.

## What Changes

- Le format XSPF **sert un document XSPF valide**, en `application/xspf+xml`, sur
  `/posts` comme sur `/post/:slug`.
- La dépendance à PEAR `File_XSPF` est levée. XSPF est un format XML de quelques
  éléments ; le produire directement supprime une dépendance système invisible du
  `composer.json`, absente de l'image, et dont l'échec est muet.
- Un gabarit de morceau isolé est ajouté, à l'image de ce que `json` et `max` font déjà :
  une playlist d'un seul élément.
- **Correction d'une exigence fausse du corpus.** Voir ci-dessous : ce n'est pas le code
  qui dévie de la spec sur ce point, c'est la spec qui décrit mal le code.

### L'exigence du corpus était inexacte

Le corpus affirme aujourd'hui :

> **QUAND** une page de morceau ou de liste est servie en HTML
> **ALORS** les formats `json` et `xspf` sont annoncés au visiteur
> **ET** le format `max` ne l'est pas, bien qu'il reste accessible

Confronté au code et à la production, c'est faux sur deux points :

- **une page de morceau n'annonce que `json`.** `executeShow()` construit un
  `$formatsLimited` qui ne contient que lui — c'est délibéré, le commentaire dit
  « Formats specifics ». `xspf` et `max` n'y sont annoncés ni en `<link>`, ni au visiteur ;
- **`max` est bien annoncé sur la liste**, en `<link rel="alternate">`. Le drapeau
  `display` ne gouverne que les liens visibles du pied de page, pas les métadonnées.

La distinction entre ces deux canaux — `<link>` d'un côté, liens visibles de l'autre —
n'existait pas dans l'exigence. Elle y entre.

Ce point mérite d'être noté pour lui-même : **le corpus a dérivé dès sa rédaction**, dans
la séance même qui l'a produit. Il a été écrit en lisant le code, sans le confronter à une
instance qui tourne. C'est le mode de défaillance de ce dépôt, et le corpus n'y échappe
pas — il l'attrape seulement plus vite, parce qu'il est vérifiable.

### Approche

Deux voies s'offraient pour la dépendance : installer PEAR et `File_XSPF` dans l'image, ou
produire le XML directement.

La seconde est retenue. `File_XSPF` est une bibliothèque PEAR sans version publiée depuis
plus de quinze ans, absente du `composer.json`, invisible de quiconque lit les dépendances
déclarées du projet, et dont l'absence ne se manifeste que par un 500 muet en production.
L'ajouter à l'image reconduirait exactement le problème que ce dépôt collectionne : une
chose dont dépend le comportement, et que rien ne rend visible.

Un document XSPF de playlist tient en une poignée d'éléments — `title`, `date`, puis un
`track` par morceau avec `location`, `creator`, `title`, `annotation`, `info`. Le produire
avec les outils XML de PHP est plus court que la dépendance qu'il remplace.

La contrainte du socle qui oriente ce choix : sur Symfony 1.5 et PHP 7.4, une dépendance
système non déclarée ne casse qu'au moment du rendu, dans une vue, sans que rien en amont
ne l'ait signalée. Moins il y en a, mieux le legacy se porte.

## Hors périmètre

- **Les autres formats.** `json`, `max` et le flux RSS fonctionnent et ne sont pas touchés.
- **Le choix d'annoncer ou non tel format sur telle page.** Ce changement décrit ce que
  fait le code, il ne rearbitre pas. Si `xspf` devait être annoncé sur une page de morceau,
  ce serait un autre changement, et une décision éditoriale.
- **`showSuccess.csv.php`**, gabarit présent au dépôt alors qu'aucun format `csv` n'est
  déclaré dans `setFormats()`. Repéré à l'occasion — un huitième artefact qui décrit sans
  contraindre. Son sort mérite son propre changement.
- La validation du document produit contre le schéma XSPF officiel, qui suppose un
  outillage que le dépôt n'a pas.
- Le contenu même de la playlist — quels morceaux, dans quel ordre, avec quels champs.
  Ce changement rétablit un format cassé, il ne le redessine pas.

## Capacités

### Nouvelles capacités

Aucune.

### Capacités modifiées

- `formats-de-sortie` — l'exigence « Sélection du format » est corrigée sur deux points :
  ce qu'un format déclaré garantit lorsqu'il est demandé, et lesquels sont réellement
  annoncés, sur quelle page, par quel canal.

## Impact

- `src/apps/frontend/modules/post/templates/listSuccess.xspf.php` : réécrit sans PEAR.
- `src/apps/frontend/modules/post/templates/showSuccess.xspf.php` : créé.
- **Contrat public rétabli, non modifié.** `application/xspf+xml` est ce que le site
  annonce déjà ; il cessera simplement de mentir. Aucune route ne bouge, aucun autre
  format n'est touché.
- Les agrégateurs et lecteurs qui suivent le `<link rel="alternate">` de la page de liste
  obtiendront une playlist au lieu d'une erreur. Combien sont-ils, on l'ignore : rien ne
  mesure l'usage de ce format, et il est cassé depuis assez longtemps pour que les usagers
  éventuels aient renoncé.
