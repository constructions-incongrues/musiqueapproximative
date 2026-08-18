## 1. Établir la carte de couverture

- [x] 1.1 Lister les 59 scénarios des deux capacités, et pour chacun dire s'il est exercé
  aujourd'hui, par quel fichier, et par quelle assertion. C'est la carte contre laquelle le
  reste se mesure.
- [x] 1.2 Relever ce que les fixtures portent : morceaux hors ligne, à publication future,
  sans identifiant d'URL, nombre de contributeurs, champs contenant guillemets et retours à
  la ligne. Un test ne vérifie que ce que les fixtures contiennent.
- [ ] 1.3 Marquer les scénarios **hors d'atteinte** d'un test fonctionnel, avec leur raison.
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

- [ ] 2.1 XSPF d'une liste : document de playlist, encodage déclaré, type de contenu, un
  élément par morceau portant adresse absolue du fichier, artiste, titre, corps et adresse
  de page.
- [ ] 2.2 XSPF, titre de la playlist : nomme le contributeur quand la liste est filtrée,
  reprend le terme cherché sur une recherche, désigne l'ensemble sinon. Trois cas.
- [ ] 2.3 XSPF d'un morceau isolé : une playlist d'un seul élément.
- [ ] 2.4 Max/MSP d'une liste : une ligne par morceau, avec rang, artiste, titre, adresse
  du fichier, adresse de page, contributeur, total et corps.
- [ ] 2.5 Max/MSP, assainissement : guillemets et retours à la ligne retirés des champs
  textuels. **Vérifier que les fixtures portent un morceau qui en contient** — sans quoi le
  test passe sans rien exercer.
- [ ] 2.6 Max/MSP d'un morceau isolé : même structure qu'une ligne de liste, rang `0` et
  total `1`.

## 3. Vérifier la représentation JSON

- [ ] 3.1 Champs jamais exposés : les champs internes de mise en ligne, de révision et
  l'objet utilisateur complet sont absents. C'est le scénario qui protège contre une fuite,
  et rien ne l'exerce.
- [ ] 3.2 Description de la piste et du contributeur : les objets `track` et `contributor`
  portent les champs décrits, et les champs bruts correspondants ne sont pas à la racine.
- [ ] 3.3 Liens de navigation : `contributor_playlist`, `avatar`, et `post_previous` /
  `post_next` quand ces morceaux sont connus.
- [ ] 3.4 Corps du morceau : `body` porte `markdown` et `html`.

## 4. Vérifier le catalogue et la navigation

- [ ] 4.1 Ordre du catalogue : du plus récent au plus ancien.
- [ ] 4.2 Filtrage par contributeur : seuls ses morceaux, et le titre de page l'annonce.
- [ ] 4.3 Recherche par termes : les morceaux correspondants, le titre annonce le nombre et
  les termes, et un résultat non publiable est écarté.
- [ ] 4.4 Navigation séquentielle : suivant, précédent, et restriction à un contributeur.
- [ ] 4.5 Formats annoncés : sur une page de liste, `json`, `xspf` et `max` en
  `<link rel="alternate">`, dont seuls `json` et `xspf` parmi les liens visibles ; sur une
  page de morceau, `json` seul annoncé.

## 5. Chaque test doit pouvoir échouer

- [ ] 5.1 Pour **chaque** test ajouté, le voir échouer avant de l'accepter : fausser
  temporairement ce qu'il vérifie, constater l'échec nommé, rétablir. Un test qu'on n'a pas
  vu rouge ne prouve rien.
- [ ] 5.2 Consigner dans ce fichier la liste des tests dont l'échec a été observé. Ceux qui
  n'y figurent pas ne sont pas acceptés.

## 6. Vérification

- [ ] 6.1 `docker-compose exec php php symfony test:all` — la suite passe. Relever avant et
  après, en fichiers et en tests.
- [ ] 6.2 Relancer la suite complète après **chaque** modification de fixtures : la suite
  Subsonic compte des morceaux et des artistes, et ses 119 assertions en dépendent.
- [ ] 6.3 Reprendre la carte de 1.1 : dire combien de scénarios sont désormais exercés,
  combien restent hors d'atteinte et pourquoi. Donner le chiffre, pas une appréciation.
- [ ] 6.4 `find src -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;`.
- [ ] 6.5 `openspec validate verifier-les-representations-machine --type change --strict`.

### Si un test dément la spécification

- [ ] 6.6 Consigner l'écart ici : le scénario, ce qu'il annonce, ce que le site fait.
- [ ] 6.7 Ne pas le trancher dans ce changement — `skip_specs` est déclaré, et c'est
  délibéré. Le porter au plan de release comme une story, avec les deux lectures possibles :
  la spécification a mal décrit, ou le code a dérivé.
