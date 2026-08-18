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

## 2 à 5. Ce qui appartient à l'opérateur, non à ce change

**Ces étapes ne sont pas cochées, et elles ne le seront pas ici.** Elles demandent l'accès à
la base de production, que ce dépôt n'a pas :

- prendre un dump et vérifier qu'il se relit ;
- relever un échantillon accentué et son `HEX()` ;
- lancer le script, reconstruire l'index de recherche ;
- vérifier que le texte est identique et que les octets ont changé ;
- **livraison 2** — poser `encoding: utf8mb4` sur le bloc `all`, et seulement alors ;
- vérifier sur le site en ligne, en postant un morceau cyrillique et un morceau avec emoji.

Elles vivent dans `docs/modules/ROOT/pages/migration-utf8mb4.adoc`, écrites pour quelqu'un
qui n'aura pas cette conversation. Les recopier ici en cases à cocher laissait croire que ce
change pouvait les mener ; il ne le peut pas.

**Ce que ce change livre, et qui est fait** : le script, son contrôle préalable, sa
documentation, et la répétition sur le moteur et les données de la cible.

**Ce qui reste ouvert au plan** : la livraison 2, qui est une story à part — elle ne peut
partir qu'une fois la conversion constatée.

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

## 2quater. Portage de l'environnement de dev sur MariaDB — 2026-08-18

À la demande de l'auteur, le conteneur de base est passé de `mysql:5.7.25` à
`mariadb:10.11`, la version de la production. Le site n'a jamais tourné que sur MariaDB :
le dump d'amorçage du dépôt est lui-même produit par MariaDB 10.3. Seul le conteneur de
développement divergeait.

Effet de bord bienvenu : `platform: linux/amd64` disparaît. MySQL 5.7 n'a pas d'image arm64
et tournait en émulation.

L'ancien répertoire de données est conservé en `var/db.mysql57.bak` — les fichiers MySQL 5.7
sont illisibles par MariaDB, mais on ne jette pas 236 Mo sans filet. Suite complète après
portage : **624 tests, tous verts**.

### Ce que le portage a révélé, et c'est la raison de l'avoir fait

**Le contrôle préalable était inutilisable, et MySQL le masquait.**

Sa première version cherchait `LIKE '%Ã©%'`. Une chaîne littérale traverse une conversion de
jeu de caractères avant d'atteindre une colonne `latin1`, et le résultat dépend du client.
Sur MariaDB avec son client `utf8mb3` par défaut, ce motif remontait **3 538 corps sur
8 216** : le script se serait arrêté à tous les coups, sur un corpus dont on savait par
ailleurs qu'il n'a aucun double encodage.

Un garde-fou qui se déclenche toujours ne protège de rien — il apprend seulement à passer
outre.

La comparaison porte désormais sur les **octets**, via `HEX()`, qui ne traverse aucune
conversion. Un `é` doublement encodé occupe deux octets `C3 A9` dans une colonne `latin1` ;
un `é` authentique en occupe un seul, `E9`.

| | corpus réel | corpus empoisonné |
| --- | --- | --- |
| ancien contrôle (`LIKE`) sur MariaDB | 3 538 — arrêt | arrêt |
| nouveau contrôle (`HEX`) sur MariaDB | **0 — passe** | **arrêt** |

Le nouveau discrimine, l'ancien non.

### Répétition sur le moteur cible

| contrôle | résultat |
| --- | --- |
| conversion du corpus réel sur MariaDB 10.11 | 12 tables ; `directus_*` non touchées |
| `Güyôm` | `47FC79F46D` → `47C3BC79C3B46D` |
| `variètè_Good` | `E8` → `C3A8`, deux fois |
| morceaux | 8 216 avant, 8 216 après |
| morceaux détruits | 82 avant, 82 après |
| garde-fou sur corpus empoisonné | arrêt |

**Un faux positif écarté** : une sonde signalait un `C3 83` après conversion, dans le corps
du morceau `3062`. Vérification faite, ce corps porte **83 octets de contrôle avant
conversion** — les « reliquats d'un import binaire » que le gabarit XSPF documente déjà. La
conversion les préserve, elle n'en crée pas.
