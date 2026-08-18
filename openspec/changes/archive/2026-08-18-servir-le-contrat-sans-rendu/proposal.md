## Why

Le contrat OpenAPI publié le 2026-08-18 **n'était servi nulle part**. Mesuré :

```
https://www.musiqueapproximative.net/openapi.yaml  ->  404
https://www.musiqueapproximative.net/posts/feed    ->  200
```

La cause n'est pas un oubli, c'est une convention devenue fausse. Le contrat était un
gabarit `openapi.yaml-dist` rendu par `make configure`, et les rendus sont ignorés par git.
Cette convention datait du déploiement par rsync, qui rendait les fichiers **avant** de les
envoyer. Le déploiement se fait maintenant par `git pull` : **aucun fichier `-dist` ajouté
depuis ce basculement n'arrive en production.** Le contrat est le premier ; il ne serait
pas le dernier.

Une copie manuelle du gabarit a été posée sur le serveur entre-temps. Elle répond 200 mais
déclare `url: https://${APP_DOMAIN}` — le seul champ dont une machine a besoin pour appeler
l'API est le seul qui soit cassé. Le test de contrat ne l'a pas vu : il lit le fichier
local, jamais la production.

Le document n'a besoin d'être un gabarit que pour deux variables, toutes deux dans le bloc
`servers`. OpenAPI accepte une adresse de serveur **relative**, résolue contre l'emplacement
du document. Le besoin de rendu disparaît, donc le mode de défaillance aussi.

## What Changes

- `src/web/openapi.yaml-dist` devient `src/web/openapi.yaml`, versionné et servi tel quel.
- Le bloc `servers` passe à `url: /`. Plus aucune variable dans le document.
- Le test de contrat troque son garde-fou : il ne compare plus les `$ref` du rendu à ceux du
  gabarit, il vérifie qu'aucun motif `${...}` ne subsiste.
- La CI vérifie en plus que `openapi.yaml-dist` **n'est pas réapparu**.
- `release-please-config.json`, `.gitignore` et `CLAUDE.md` suivent le renommage.

Le contrat public est concerné : l'adresse de serveur déclarée change de forme, d'absolue à
relative.

## Hors périmètre

- Les autres fichiers `-dist` du dépôt. Le même piège les guette, mais ils sont déjà
  présents sur le serveur depuis l'ère rsync et rien ne prouve qu'ils soient à jour. C'est
  une story à part, qui demande de mesurer avant de toucher.
- Servir le contrat sous une seconde adresse, ou en JSON. Une adresse suffit.
- Vérifier le contrat contre la **production** plutôt que contre l'instance de test. C'est
  le trou qui a laissé passer ce défaut, et il reste ouvert — il est nommé dans les tâches.
