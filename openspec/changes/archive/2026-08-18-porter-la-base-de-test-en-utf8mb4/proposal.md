## Why

La base de production est en `latin1` : tout caractère hors cp1252 saisi par un contributeur
est remplacé par `?` à l'écriture, définitivement. **81 morceaux** ont déjà un titre ou un
auteur détruit, 37 contributeurs sont concernés, et cinq dégâts datent de 2026.

Aujourd'hui **aucun test ne peut échouer là-dessus**, et la mesure faite à la proposition
corrige ce qu'on en supposait :

| | base de test (`utf8`) | production (`latin1`) |
| --- | --- | --- |
| `Paweł Zadrożniak` | **passe** | `Pawe? Zadro?niak` |
| `Сергей 坂本龍一` | **passe** | `?????? ????` |
| `🎵` | **erreur MySQL 1366** | `?`, en silence |

La base de test tient donc déjà tout le plan multilingue de base — le manque principal n'est
pas son jeu de caractères mais **le fait qu'aucune fixture ne l'exerce**. Le passage à
`utf8mb4` ne gagne que les emoji.

Cette nuance vaut d'être retenue : `utf8` **refuse bruyamment** ce qu'il ne peut pas tenir,
`latin1` **détruit en silence**. C'est le second qui est en production, et c'est le silence
qui fait le dommage — un contributeur averti aurait corrigé son titre.

Cette story ne répare rien en production. Elle rend l'écart **exécutable** : elle produit le
test qui dit ce que le site doit faire, avant qu'on touche à une base qui porte dix-huit ans
de publications.

## What Changes

- La base de test est créée en `utf8mb4` / `utf8mb4_unicode_ci` au lieu de `utf8` — le gain
  propre étant les emoji, et l'alignement sur ce que la story 19 posera en production.
- Le DSN porte `charset=utf8mb4`, faute de quoi la connexion négocierait autre chose et
  convertirait — c'est exactement le mécanisme qui détruit en production.
- **Les fixtures gagnent des morceaux qui exercent la frontière** — c'est l'essentiel du
  changement : cyrillique, idéogrammes, latines étendues (`ł`, `ż`, `ğ`), emoji.
- Un test fonctionnel vérifie que ces caractères traversent intacts **toutes** les
  représentations : page HTML, JSON, XSPF, `max`, et le flux.

## Ce que ça ne fait pas, et qu'il faut dire

Le test passera en environnement de test **pendant que la production reste cassée**. C'est
voulu : l'écart devient constatable avant d'être réparé, ce qui est la seule façon de
démontrer ensuite que la migration opère. Un test écrit après la migration ne prouverait
rien — il passerait du premier coup, sans qu'on sache s'il aurait échoué avant.

## Capabilities

### New Capabilities

Aucune.

### Modified Capabilities

- `catalogue-morceaux` : l'exigence « Définition d'un morceau publiable » décrit ce qui rend
  un morceau visible et se tait sur ce que le système conserve de ce qu'on lui confie. Elle
  gagne le fait qu'un morceau est restitué **tel qu'il a été saisi**, quel que soit le
  système d'écriture de son titre.

## Hors périmètre

- **La base de production**, qui ne bouge pas. C'est la story 19.
- **Les 81 morceaux déjà détruits**, qu'aucune migration ne rendra. C'est la story 20.
- **Le garde-fou à la saisie** — `track_title` et `track_author` n'ont aucun validateur.
  Versé en « Could », conditionnel à ce que la migration tarde.
- **La collation de recherche**, dont l'effet sur `post_index` relève de la migration.

## Impact

- **Modifié** : `Makefile` (cible `test-init`), `src/config/databases.yml-dist`,
  `src/data/fixtures/subsonic.sql`.
- **Ajouté** : un test fonctionnel sous `src/test/functional/frontend/`.
- **Non modifié** : le code applicatif, et la base de production.
- **À surveiller** : les fixtures sont partagées avec la suite Subsonic et ses 119
  assertions. Les ajouts doivent être additifs.
