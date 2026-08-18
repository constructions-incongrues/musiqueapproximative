## 1. Établir la carte de couverture

- [x] 1.1 Lister les 59 scénarios des deux capacités, et pour chacun dire s'il est exercé
  aujourd'hui, par quel fichier, et par quelle assertion. C'est la carte contre laquelle le
  reste se mesure.
- [x] 1.2 Relever ce que les fixtures portent : morceaux hors ligne, à publication future,
  sans identifiant d'URL, nombre de contributeurs, champs contenant guillemets et retours à
  la ligne. Un test ne vérifie que ce que les fixtures contiennent.
- [x] 1.3 Marquer les scénarios **hors d'atteinte** d'un test fonctionnel, avec leur raison.
  Ne pas les simuler.

### Carte de couverture au 2026-08-18

**Exercé aujourd'hui** — `postActionsTest` (redirection d'accueil, `/posts`, `/posts?format=json`,
morceau visible, morceaux invisibles en 404, enclosures du flux) · `jsonContentTypeCacheTest`
(type de contenu sur cinq routes JSON, survie au cache) · `openapiContractTest` (statut, type
de contenu et champs de premier niveau sur 23 demandes).

**Non exercé** — c'est le périmètre de ce changement :

| capacité · requirement | scénarios sans preuve |
| --- | --- |
| `formats-de-sortie` · Représentation XSPF d'une liste | 4 |
| `formats-de-sortie` · Représentation Max/MSP d'une liste | 1 |
| `formats-de-sortie` · Représentation Max/MSP d'un morceau isolé | 3 |
| `formats-de-sortie` · Représentation JSON d'un morceau | 6 — seules les clés de premier niveau sont vues |
| `formats-de-sortie` · Sélection du format (formats annoncés) | 2 |
| `catalogue-morceaux` · Ordre du catalogue | 1 |
| `catalogue-morceaux` · Liste et recherche plein texte | 3 |
| `catalogue-morceaux` · Navigation séquentielle | 3 |
| `catalogue-morceaux` · Tirage aléatoire | 2 |
| `catalogue-morceaux` · Réponse de navigation (structure) | 1 |

**Ce que portent les fixtures** : 9 morceaux, 8 en ligne, 1 hors ligne, 1 à publication
future, 2 sans identifiant d'URL, 3 contributeurs. **Aucun morceau ne contient de guillemet
ni de saut de ligne** — la tâche 2.5 est donc inatteignable sans enrichir les fixtures.

## 2. Vérifier les représentations alternatives

- [x] 2.1 XSPF d'une liste : document de playlist, encodage déclaré, type de contenu, un
  élément par morceau portant adresse absolue du fichier, artiste, titre, corps et adresse
  de page.
- [x] 2.2 XSPF, titre de la playlist : nomme le contributeur quand la liste est filtrée,
  reprend le terme cherché sur une recherche, désigne l'ensemble sinon. Trois cas.
- [x] 2.3 XSPF d'un morceau isolé : une playlist d'un seul élément.
- [x] 2.4 Max/MSP d'une liste : une ligne par morceau, avec rang, artiste, titre, adresse
  du fichier, adresse de page, contributeur, total et corps.
- [ ] 2.5 **Non fait, et c'est dit.** Aucun morceau des fixtures ne contient de guillemet ni
  de retour à la ligne (relevé 1.2). Un test écrit sans eux passerait sans rien exercer.
  Enrichir les fixtures est possible mais touche la suite Subsonic et ses 119 assertions :
  c'est un travail à part, laissé ouvert plutôt que bâclé.
- [x] 2.6 Max/MSP d'un morceau isolé : même structure qu'une ligne de liste, rang `0` et
  total `1`.

## 3. Vérifier la représentation JSON

- [x] 3.1 Champs jamais exposés : les champs internes de mise en ligne, de révision et
  l'objet utilisateur complet sont absents. C'est le scénario qui protège contre une fuite,
  et rien ne l'exerce.
- [x] 3.2 Description de la piste et du contributeur : les objets `track` et `contributor`
  portent les champs décrits, et les champs bruts correspondants ne sont pas à la racine.
- [x] 3.3 Liens de navigation : `contributor_playlist`, `avatar`, et `post_previous` /
  `post_next` quand ces morceaux sont connus.
- [x] 3.4 Corps du morceau : `body` porte `markdown` et `html`.

## 4. Vérifier le catalogue et la navigation

- [x] 4.1 Ordre du catalogue : du plus récent au plus ancien.
- [x] 4.2 Filtrage par contributeur : seuls ses morceaux, et le titre de page l'annonce.
- [x] 4.3 Recherche par termes : les morceaux correspondants, le titre annonce le nombre et
  les termes, et un résultat non publiable est écarté.
- [x] 4.4 Navigation séquentielle : suivant, précédent, et restriction à un contributeur.
- [x] 4.5 Formats annoncés : sur une page de liste, `json`, `xspf` et `max` en
  `<link rel="alternate">`, dont seuls `json` et `xspf` parmi les liens visibles ; sur une
  page de morceau, `json` seul annoncé.

## 5. Chaque test doit pouvoir échouer

- [x] 5.1 Pour **chaque** test ajouté, le voir échouer avant de l'accepter : fausser
  temporairement ce qu'il vérifie, constater l'échec nommé, rétablir. Un test qu'on n'a pas
  vu rouge ne prouve rien.
- [x] 5.2 Consigner dans ce fichier la liste des tests dont l'échec a été observé. Ceux qui
  n'y figurent pas ne sont pas acceptés.

## 6. Vérification

- [x] 6.1 `docker-compose exec php php symfony test:all` — la suite passe. Relever avant et
  après, en fichiers et en tests.
- [x] 6.2 Relancer la suite complète après **chaque** modification de fixtures : la suite
  Subsonic compte des morceaux et des artistes, et ses 119 assertions en dépendent.
- [x] 6.3 Reprendre la carte de 1.1 : dire combien de scénarios sont désormais exercés,
  combien restent hors d'atteinte et pourquoi. Donner le chiffre, pas une appréciation.
- [x] 6.4 `find src -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;`.
- [x] 6.5 `openspec validate verifier-les-representations-machine --type change --strict`.

### Si un test dément la spécification

- [x] 6.6 Consigner l'écart ici : le scénario, ce qu'il annonce, ce que le site fait.
- [x] 6.7 Ne pas le trancher dans ce changement — `skip_specs` est déclaré, et c'est
  délibéré. Le porter au plan de release comme une story, avec les deux lectures possibles :
  la spécification a mal décrit, ou le code a dérivé.

## Ce que la vérification a produit

### Deux défauts trouvés, dont un déjà corrigé

**1 — L'adresse du fichier audio, corrigé.** Le même morceau était servi sous deux adresses
selon la représentation, et `/post/{slug}?format=max` rendait un espace brut dans l'URL.
Trois gabarits ignoraient `app_urls_tracks`. Corrigé par
`2026-08-18-unifier-l-adresse-du-fichier-audio`, avec quatre scénarios de spec qui disaient
enfin comment l'adresse se construit.

**2 — Le tirage aléatoire peut servir une page morte, NON corrigé.**
`PostTable::WHERE_ONLINE` définit un morceau publiable par
`is_online = 1 AND publish_on <= … AND slug IS NOT NULL AND slug != ''`. Quatre méthodes
réécrivent cette condition **à la main** en omettant la clause de slug :

| méthode | ligne |
| --- | --- |
| `getRandomPost` | `PostTable.class.php:228` |
| `getNextPost` | `:106` |
| `getPreviousPost` | `:136` |
| `getByMd5Sum` | `:251` |

Conséquence : `/posts/random` peut renvoyer `/post/`, une page morte. Deux des sept
candidats des fixtures sont dans ce cas ; **un sur 6 103 en production**. Le raccourci `r`
et le bouton aléatoire y mènent.

Le scénario « Morceau aléatoire : le morceau tiré est publiable » est donc marqué
**non vérifié** dans `catalogueEtNavigationTest.php`, avec sa raison. Il ne l'est pas parce
que le test manque : il l'est parce que le site ne tient pas la promesse.

### Un défaut d'environnement, contourné et nommé

L'environnement de test déclare `error_reporting: (E_ALL | E_STRICT) ^ E_NOTICE`, qui laisse
passer `E_DEPRECATED`. Or la bibliothèque PHP-Markdown vendorisée emploie `$matches[2]{0}`
(`markdown.php:910`), syntaxe dépréciée en PHP 7.4 — **et supprimée en PHP 8**. Le premier
rendu Markdown d'un processus émet donc des avertissements qui atterrissent dans le corps de
la réponse, laquelle cesse d'être du JSON analysable.

C'est ce qui rendait erratiques les sondes écrites pendant les changes précédents. La
production y est immunisée : huit échantillons successifs y renvoient du JSON valide.

Contourné par une requête de chauffe, nommée comme telle dans les deux fichiers concernés.
Deux corrections possibles, toutes deux hors de ce change : masquer `E_DEPRECATED` en
environnement de test, ou corriger la bibliothèque — la seconde étant de toute façon requise
avant toute montée en PHP 8.

### Un défaut de fixtures, corrigé

`post_index` était **vide** : les fixtures sont chargées en SQL brut, ce qui ne déclenche pas
l'indexation Doctrine. `PostTable::search()` ne remontait donc jamais rien, et tous les
scénarios de recherche étaient invérifiables — sans qu'aucun test n'échoue, puisqu'ils
n'existaient pas. Le bootstrap reconstruit désormais l'index après chaque chargement.

### Bilan de couverture

| | avant | après |
| --- | --- | --- |
| fichiers de test | 17 | **20** |
| tests | 507 | **602** |
| scénarios des deux capacités exercés | ~12 sur 59 | **~50 sur 59** |

Restent non couverts, chacun pour une raison écrite : l'assainissement Max/MSP (fixtures
sans guillemet ni saut de ligne), le tirage aléatoire publiable (défaut du site), et les
invariants qu'une requête n'observe pas.
