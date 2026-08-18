# Conception

## Contexte

`buildOnlinePostsQuery` sert **deux familles d'appelants aux besoins opposés** :

```
  buildOnlinePostsQuery($contributor, $count, $fields = '*')
       │
       ├── $fields = '*'              frontend : listes HTML, json, xspf, max
       │                              → lit le contributeur, y compris UserProfile
       │
       └── $fields = FIELDS_SUBSONIC  module rest : 5 appels
                                      'p.id, p.track_title, …, u.username'
                                      → projection DÉLIBÉRÉMENT restreinte, exclut body (TEXT)
```

La difficulté n'est pas d'ajouter une jointure. C'est de l'ajouter **sans annuler
l'optimisation que les appelants Subsonic ont explicitement demandée** : une projection
étroite qui écarte le corps des morceaux, un champ `TEXT`.

## Décision : la jointure suit la projection

**Quand l'appelant laisse `$fields` à sa valeur par défaut**, il veut l'objet complet — c'est
le cas de tous les chemins frontend, et ce sont eux qui lisent `UserProfile`. La requête
joint alors `UserProfile` et projette explicitement les trois tables.

**Quand l'appelant a passé une projection restreinte**, il a déjà dit ce qu'il voulait. La
requête ne change pas d'un octet. Les cinq appels Subsonic ne lisent d'ailleurs jamais
`UserProfile` : `FIELDS_SUBSONIC` ne réclame que `u.username`, déjà couvert par la jointure
existante.

La condition porte donc sur ce que l'appelant a demandé, pas sur qui il est. Un futur
appelant qui laisse le défaut hérite du bon comportement sans le savoir ; un appelant qui
restreint garde la main.

## Pourquoi la jointure ne peut pas fausser les comptes

`countOnlinePosts()` appelle `->count()` sur cette même requête. Une jointure qui
multiplierait les lignes fausserait le total.

Elle ne le peut pas : le schéma déclare la relation `one`-to-`one`.

```yaml
# src/config/doctrine/schema.yml
UserProfile:
  relations:
    sfGuardUser:
      type: one
      foreignType: one
      foreignAlias: UserProfile
```

Un utilisateur a au plus un profil. Un `leftJoin` sur une relation `one` ne peut pas
dupliquer une ligne — et le `leftJoin`, plutôt qu'un `innerJoin`, garantit qu'un morceau dont
le contributeur n'a pas de profil reste servi.

## Ce que la vérification doit faire, et pourquoi elle est difficile

Un test qui compte les requêtes se trompe s'il ne vide pas l'identity map de Doctrine entre
les mesures. **C'est arrivé pendant le diagnostic de ce change** : une première mesure
concluait « ce n'est pas 8 100 requêtes » parce que les utilisateurs étaient déjà chargés
par l'essai précédent. Le N+1 était invisible.

Le test doit donc appeler `$conn->clear()` avant chaque mesure, et **comparer deux tailles**
plutôt que viser un nombre absolu : un coût constant se démontre en montrant qu'il ne bouge
pas quand la liste double, pas en affirmant qu'il vaut 1.

## Ce que cette conception écarte

**Charger `UserProfile` dans tous les cas**, y compris pour Subsonic. Plus simple à écrire,
mais annule une optimisation délibérée sur cinq appels qui servent un protocole d'écoute — et
le commentaire de `FIELDS_SUBSONIC` dit explicitement pourquoi il exclut `body`.

**Corriger `getDisplayName()` pour éviter `UserProfile`.** On supprimerait une lecture sur
trois, en laissant les deux autres, et on toucherait à une classe du plugin d'authentification
pour un problème de requête. La jointure les corrige toutes les trois d'un coup.

**Un cache applicatif des contributeurs.** Résout le symptôme par une couche de plus, là où
une jointure le supprime. Le premier seuil d'un outil est celui où il rend plus qu'il ne
coûte ; un cache pour éviter une jointure est du mauvais côté.
