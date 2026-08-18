> **Deux livraisons, et l'ordre est une contrainte, pas une préférence.** La première porte
> le script et ne change rien en production. L'auteur lance le script. La seconde seulement
> alors pose `encoding: utf8mb4`. Poser l'encodage avant la conversion enverrait de
> l'utf8mb4 vers des colonnes latin1 — le mécanisme qui détruit aujourd'hui, en pire.
> Voir `design.md`.

## 1. Livraison 1 — le script, qui ne s'exécute pas tout seul

- [x] 1.1 Écrire `src/data/migration/utf8mb4.sql` : contrôle préalable, puis conversion
  table par table dans un ordre écrit — `post`, `post_index`, `user_profile`, et les sept
  tables `sf_guard_*`.
- [x] 1.2 Le contrôle préalable **s'arrête** si l'un des trois états attendus est faux :
  tables en `latin1`, connexion en `utf8`, aucune séquence doublement encodée. Un
  avertissement qu'on peut ignorer sur une conversion irréversible n'est pas un garde-fou.
- [x] 1.3 **Reprise vérifiée, et le comportement est plus fin qu'annoncé.** Le script ne
  « saute » pas les tables converties : son curseur ne les sélectionne pas. Deux cas, tous
  deux éprouvés — reprise après interruption à mi-parcours : les deux tables restantes sont
  converties, les deux déjà faites ne sont pas retouchées. Relance sur une base entièrement
  convertie : **arrêt** avec un message explicite, le contrôle préalable ne trouvant rien à
  faire. C'est le bon comportement : une relance à vide sur une base de production doit dire
  pourquoi elle ne fait rien plutôt que de réussir en silence.
- [x] 1.4 Documenter l'exécution dans `docs/` : la commande, l'ordre, le dump préalable, et
  ce qu'on regarde après. Écrire pour quelqu'un qui n'aura pas cette conversation.
- [x] 1.5 Ne **rien** changer à `databases.yml-dist` dans cette livraison.

## 2. Avant de lancer — sur la production, pas sur le dump local

- [x] 2.1 **Refaire la mesure du double encodage sur la production.** Le zéro relevé
  jusqu'ici vient d'un dump vieux de cinq ans. C'est la vérification qui décide si la
  conversion répare ou détruit.
- [x] 2.2 Relever l'état réel : jeu de caractères de chaque table, encodage de connexion,
  nombre de lignes. Le comparer à ce que le script attend.
- [ ] 2.3 Dump complet, et **vérifier qu'il se relit** — un dump non testé n'est pas une
  sauvegarde.
- [x] 2.4 Relever, avant conversion, un échantillon de titres accentués et leur `HEX()` :
  c'est contre lui qu'on vérifiera que rien n'a bougé.

## 3. La conversion

- [ ] 3.1 Lancer le script. Consigner l'heure, la durée et la sortie.
- [ ] 3.2 Reconstruire l'index de recherche : `post_index` change de collation.
- [ ] 3.3 Vérifier dans `information_schema` que les dix tables portent `utf8mb4`.
- [ ] 3.4 Reprendre l'échantillon de 2.4 : les titres accentués doivent être **identiques**,
  et leur `HEX()` avoir changé — un octet latin1 devient deux octets UTF-8. Si le texte a
  bougé, la conversion a mal tourné et il faut restaurer.
- [ ] 3.5 Vérifier que les 81 morceaux détruits le sont toujours et pas davantage : la
  conversion ne les répare pas, elle ne doit pas les aggraver.

## 4. Livraison 2 — l'encodage de connexion

- [ ] 4.1 **Seulement après avoir constaté 3.3 et 3.4** : poser `encoding: utf8mb4` sur le
  bloc `all` de `databases.yml-dist`, avec le même commentaire que le bloc `test` — le
  `charset=` du DSN n'a aucun effet, Doctrine 1 analysant le DSN lui-même.
- [ ] 4.2 Fusionner, et vérifier que le déploiement a bien pris.

## 5. Vérification, sur le site en ligne

- [ ] 5.1 Poster un morceau au titre cyrillique et un portant un emoji, depuis l'admin, comme
  un contributeur le ferait. C'est le seul test qui exerce le chemin réel.
- [ ] 5.2 Vérifier qu'ils sont servis intacts dans la page, le JSON, le XSPF et le `max` —
  les quatre représentations que le test de la story 18 couvre en environnement de test.
- [ ] 5.3 Vérifier que la recherche les trouve : la collation de `post_index` a changé.
- [ ] 5.4 Les retirer ensuite, ou les garder — mais le décider, pas l'oublier.
- [ ] 5.5 `docker-compose exec php php symfony test:all` — la suite passe toujours.

### Ce que cette story ne fait pas

- [ ] 5.6 Consigner : les **81 morceaux détruits le restent**. La conversion arrête
  l'hémorragie, elle ne rend rien. C'est la story 20, et la confondre avec celle-ci ferait
  croire le problème réglé.

## 2bis. Répétition menée avant livraison

Le script a été éprouvé sur **trois copies** de la base locale, jamais sur la production ni
sur la base de développement de l'auteur. Les copies ont été supprimées après.

| épreuve | résultat |
| --- | --- |
| conversion d'une copie `latin1` complète | 4 tables ; `Güyôm` conservé, `HEX` de `47FC79F46D` à `47C3BC79C3B46D` |
| `Ceylán` | `E1` → `C3A1` |
| `für Elise` | `FC` → `C3BC` |
| insertion d'un emoji après conversion | acceptée |
| relance sur base déjà convertie | arrêt, message explicite |
| reprise après interruption | 2 restantes converties, 2 faites intouchées |
| corpus portant un double encodage | **arrêt — le garde-fou mord** |

La quatrième ligne est la démonstration qui compte : **le texte est identique, les octets ont
changé.** C'est exactement ce que `CONVERT TO CHARACTER SET` doit faire, et la seule preuve
que la conversion répare au lieu de détruire.

La dernière l'est autant : un garde-fou qu'on n'a pas vu mordre ne protège de rien.

### Un défaut du script, trouvé en le répétant

`ALTER DATABASE` n'est pas supporté par le protocole des requêtes préparées — erreur 1295.
La première version le lançait dynamiquement et échouait après avoir converti les tables.
Retiré du script, il est désormais **affiché en fin d'exécution** comme commande à lancer
séparément. Masquer cet échec aurait laissé la base avec un jeu par défaut incohérent.

## 2ter. Répétition sur les données RÉELLES de production — 2026-08-18

L'auteur a fourni un dump frais. La vérification que ce change disait ne pas pouvoir faire a
donc été faite, et elle lève la réserve principale.

### L'état réel

| | |
| --- | --- |
| serveur | **MariaDB 10.11**, et non MySQL 5.7 |
| morceaux | **8 216**, dont 8 099 publiés |
| dernier publié | **2026-08-18** — le jour même |
| tables du projet en `latin1` | `post`, `post_index`, les six `sf_guard_*` |
| `user_profile` | `utf8` |
| les quinze tables `directus_*` | `utf8` / `utf8mb4` — exclues par le script |
| **double encodage** | **0** |

**Le zéro est le chiffre qui décide.** Il venait jusqu'ici d'un dump vieux de cinq ans ; il
est désormais mesuré sur la production du jour. `CONVERT TO CHARACTER SET` fera donc
exactement ce qu'il faut.

### La conversion, sur ces données

| contrôle | résultat |
| --- | --- |
| contrôle préalable | passé |
| tables converties | 12 — les `directus_*` non touchées, comme voulu |
| `Güyôm` | `47FC79F46D` → `47C3BC79C3B46D`, texte identique |
| `Ceylán` | `E1` → `C3A1` |
| `variètè_Good` | `E8` → `C3A8`, deux fois |
| morceaux après | **8 216**, inchangé |
| morceaux détruits après | **82**, inchangé — ni réparés ni aggravés |
| mojibake introduit | **0** |
| `directus_files` | toujours `utf8`, non touchée |
| emoji + cyrillique + idéogrammes après conversion | acceptés |

La copie a été supprimée après. Le dump fourni est ajouté à `.gitignore` : il porte des
données de production et n'a rien à faire au dépôt.

### Ce que la répétition ne couvre toujours pas

Le conteneur local est **MySQL 5.7**, la production **MariaDB 10.11**. Les constructions
employées — `SIGNAL SQLSTATE`, curseurs, requêtes préparées,
`COLLATION_CHARACTER_SET_APPLICABILITY` — existent dans les deux, et le script compare à
`utf8mb4` plutôt qu'à `utf8`, ce qui le rend indifférent au fait que MariaDB nomme `utf8mb3`
ce que MySQL appelle `utf8`. Mais **la répétition a eu lieu sur un autre moteur que la
cible**, et il faut le dire plutôt que de laisser croire le contraire.
