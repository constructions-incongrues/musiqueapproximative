## Why

`activer-le-cache-en-test` a rendu observables six scénarios de la spec `desastres` qui
décrivaient un comportement n'ayant pas lieu dans l'environnement de test. Il n'en a
couvert qu'un. Ce changement couvre ce qui reste couvrable.

L'invariance est la propriété centrale du système : le tirage se fait à la production de la
page, et son résultat vaut pour toutes les consultations servies depuis la même
représentation. C'est ce qui distingue un désastre d'un simple effet aléatoire, et c'est
aussi ce que le cache peut casser en silence — les deux bugs archivés de cette zone
n'étaient rien d'autre que des ruptures d'invariance.

Vérifié avant d'écrire ces lignes, sur les fixtures :

```
1. /post/X?danse    → danse, mangelettres, shared   entrées de cache = 2
2. /post/X          → (aucune)                      entrées de cache = 3   ← autre clé
3. autre navigateur → (aucune), identique à 2       entrées de cache = 3   ← servi du cache
```

Le déclencheur fait partie de la clé de cache, et deux navigateurs distincts partagent la
représentation.

## What Changes

- Ajoute `src/test/functional/frontend/desastreInvarianceTest.php`, qui couvre quatre
  scénarios de la spec `desastres` :
  - « Consultations successives d'une même adresse » — même tirage à chaque visite ;
  - « Deux visiteurs sur la même adresse » — mêmes recettes quel que soit le navigateur ;
  - « Paramètres de la requête pris en compte » — le déclencheur distingue les entrées ;
  - « Une même adresse dans le temps » — l'expiration provoque une nouvelle production.
- Aucun comportement observable ne change : le changement pose `skip_specs: true`.

## Hors périmètre

- **« Aléatoire propre à une recette ».** Le tirage se fait dans le navigateur, à
  l'exécution du script. Un test serveur ne peut pas l'observer ; il faudrait un navigateur
  sans tête, ce qui est un autre appareil.
- **« Deux adresses différentes ».** « Leurs tirages sont indépendants » est une propriété
  statistique. La mesurer demanderait un grand nombre de productions de page, pour un
  résultat qui testerait surtout le générateur pseudo-aléatoire de PHP.
- **« Consultation servie depuis le cache », clause « aucune règle n'est évaluée ».** La
  seconde clause du même scénario — « la page porte les recettes retenues lors de sa
  production » — est couverte. La première ne s'observe pas depuis la réponse : il faudrait
  instrumenter le moteur de règles.
- **Les deux clauses « peut ».** « L'effet servi peut différer », « le rendu peut néanmoins
  différer » : on ne peut pas écrire de test qui échoue sur une permission.

## Capabilities

### New Capabilities

Aucune.

### Modified Capabilities

Aucune. Le comportement du site ne change pas ; ce changement n'ajoute que des tests.

## Impact

- **Ajouté** : `src/test/functional/frontend/desastreInvarianceTest.php`.
- **Lu, jamais modifié** : `src/plugins/sfDesastrePlugin/`, `src/apps/frontend/config/desastres*`.
- **Contrat public** : pas concerné.
- **Dépendance** : ce test n'a de sens que si l'environnement `test` met en cache. Il le
  vérifie en première assertion plutôt que de le supposer, et échoue clairement si le
  réglage disparaît.
