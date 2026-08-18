## 1. Test

- [x] 1.1 Créer `src/test/functional/frontend/desastreInvarianceTest.php`, bootstrap
      fonctionnel puis `bootstrap/database.php`
- [x] 1.2 Vérifier en première assertion que `sf_cache` est vrai, et qu'une adresse forcée
      par un déclencheur porte des recettes non vides — sans quoi les assertions d'égalité
      qui suivent seraient vraies à vide
- [x] 1.3 « Consultations successives d'une même adresse » : deux visites de la même
      adresse portent le même ensemble de recettes
- [x] 1.4 « Deux visiteurs sur la même adresse » : un second `sfBrowser`, à session
      distincte, reçoit les mêmes recettes, et aucune entrée de cache nouvelle n'est écrite
- [x] 1.5 « Paramètres de la requête pris en compte » : l'adresse forcée et l'adresse nue
      occupent deux entrées de cache distinctes
- [x] 1.6 « Une même adresse dans le temps » : après vidage du cache, la page est reproduite
      et une entrée est réécrite

## 2. Vérification manuelle

- [x] 2.1 Lancer `docker compose run --rm --no-deps php php symfony test:all` et constater
      16 scripts, le seul échec attendu restant `getID3`
- [x] 2.2 Contrôle de mutation : remettre `cache: false` dans `settings.yml`, relancer ce
      seul fichier, et vérifier qu'il échoue plutôt que de passer à vide. Rétablir
- [x] 2.3 Vérifier qu'aucun fichier applicatif n'a été modifié :
      `git status --porcelain` ne doit montrer que des ajouts sous `src/test/` et
      `openspec/`
- [x] 2.4 Si une vérification n'a pas pu être menée, laisser sa case décochée et le dire en
      clair ici. Une case cochée signifie vérifiée

## Résultats

- Suite complète : **16 scripts, 461 assertions**, contre 15 et 449 avant ce changement.
  Le seul échec, `unit/model/PostMetadataTest` sur `Class 'getID3' not found`, est
  préexistant et étranger à ce sujet.
- `functional/frontend/desastreInvarianceTest` : 12 assertions, toutes vertes.
- Aucun fichier modifié : deux ajouts, rien d'autre.

### Contrôle de mutation

Remettre `cache: false` fait tomber **quatre** assertions, dont la plus importante :

```
not ok 1  - le cache est actif dans l environnement de test
not ok 3  - la seconde visite porte le meme ensemble de recettes
not ok 4  - l adresse nue est une entree de cache distincte de l adresse forcee
not ok 12 - la page est reproduite et une entree est reecrite
```

L'assertion 3 est la preuve que le test n'est pas vrai à vide : sans cache, deux visites
de la même adresse tirent au sort séparément et servent des recettes différentes. C'est
exactement l'invariance que ce fichier existe pour garder.

## Ce qui reste non couvert dans `desastres`, et pourquoi

| scénario | raison |
| --- | --- |
| « Aléatoire propre à une recette » | tirage dans le navigateur, invisible depuis une réponse serveur |
| « Deux adresses différentes » | propriété statistique ; la mesurer testerait surtout `mt_rand()` |
| « Consultation servie depuis le cache », clause « aucune règle n'est évaluée » | demanderait d'instrumenter le moteur de règles ; la seconde clause du scénario est couverte |
| « Règle probabiliste », « Observation d'une règle probabiliste » | statistiques, même raison |
| deux clauses « peut » | une permission ne peut pas faire échouer un test |

La spec `desastres` compte 32 scénarios. Ce changement et les deux qui l'ont précédé en
couvrent désormais la partie observable ; ce qui reste tient à la nature du sujet, pas à un
manque de travail.
