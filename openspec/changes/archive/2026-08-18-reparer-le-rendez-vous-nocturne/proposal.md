## Why

`nightly.yml` est rouge. Il l'est depuis sa création, le 2 janvier 2026 : **122 exécutions,
zéro succès**. Aucune n'a jamais été verte.

Le diagnostic qui circule — `trunk-action` appellerait `jq` sans l'installer — est faux. La
ligne `::error::jq not installed on system!` apparaît bien dans le journal, mais dans le
**listing** que GitHub imprime avant d'exécuter une étape, pas dans sa sortie. Elle
appartient à une fonction `payload()` que l'action ne déclenche que si `check-mode` vaut
`payload` ; ici il vaut `all`. Ce code n'a jamais tourné. Épingler l'action à une version
antérieure ou installer `jq` corrigerait un défaut qui n'existe pas.

L'échec réel est trois étapes plus loin, dans `all.sh` :

```
Checked 1147 files
✖ 8 unformatted files
✖ 3 security issues
✖ 27 lint issues
##[error]Process completed with exit code 1.
```

`check-mode: all` passe le linter sur **tout l'arbre**, legacy et code tiers compris : un
`jquery-1.4.2.min.js` vendorisé mal formaté, le `readme.html` du lecteur Flash abandonné,
les `settings.yml` de Symfony 1 dont la syntaxe `%SF_…%` et `@` fait échouer `yamllint`, et
un « Base64 High Entropy String » que `checkov` croit être un secret. Aucune de ces
38 remarques ne sera corrigée : la règle du dépôt est qu'un change ne refactore pas le
legacy au passage. Le rendez-vous ne peut donc pas passer au vert, et il ne l'a jamais pu.

S'y ajoute que le job ne fait plus ce que son nom annonce. Il s'appelle « Trunk Check
**Upload** », mais aucun `TRUNK_TOKEN` n'est défini — rien n'est téléversé — et Trunk
imprime à chaque exécution :

> `Check uploads and check all mode is no longer supported.`

Un rendez-vous qui n'a jamais tenu, sur une vérification que l'éditeur a retirée, pour un
envoi qui n'a lieu nulle part.

L'urgence n'est pas le job : c'est que le rendez-vous soit lisible. Le contrôle du contrat
en production, ajouté le 18 août 2026, a dû être sorti de `nightly.yml` (PR #188) parce
qu'une alerte logée dans un rendez-vous rouge en permanence est une alerte déjà éteinte.
Ce déménagement fait, `nightly.yml` ne contient plus que ce job. Le retirer, c'est vider le
fichier.

## What Changes

- **Suppression de `.github/workflows/nightly.yml`.** Le fichier ne portait plus que le job
  `trunk_check`, dont la raison d'être — téléverser un état nocturne à Trunk — n'existe plus
  côté éditeur, et n'a jamais eu de jeton pour l'exercer. Un fichier sans job est un
  fichier invalide : il part avec son dernier job.
- Le rendez-vous nocturne ne disparaît pas pour autant : `contrat-production.yml` tourne au
  même horaire, jours ouvrés à 8 h, et c'est désormais le seul.
- Le contrat public n'est **pas** concerné : aucune route, aucun format, aucune sortie du
  site ne change. Ce change ne touche que l'appareil d'intégration continue.
- La couverture de lint ne baisse pas là où elle mordait : `pr.yml` passe déjà `trunk check`
  sur chaque pull request, et le ruleset `main` n'exige que le contexte
  « Validation du code ». Aucun contrôle requis ne disparaît.
- `cache_trunk.yaml` continue de peupler le cache Trunk à chaque modification de
  `.trunk/trunk.yaml` : rien à reporter ici.

## La troisième piste, tranchée

La demande proposait trois pistes : épingler l'action, installer `jq`, ou retirer le job
« si l'envoi nocturne n'a plus d'usage ». Les deux premières répondent à un défaut
imaginaire. La troisième est la bonne, et l'enquête la rend plus ferme que sa formulation :
l'envoi n'a pas *cessé* d'avoir un usage, il n'en a **jamais** eu — pas de jeton depuis le
premier jour, et un mode retiré depuis par l'éditeur.

Restait une quatrième voie, écartée : faire taire les 38 remarques par des exclusions dans
`.trunk/trunk.yaml`. Elle demande de trier du code tiers et du legacy que personne ne
corrigera, elle modifie du même geste ce que `pr.yml` vérifie, et elle rendrait vert un job
qui, une fois vert, n'aurait toujours rien à téléverser.

## Hors périmètre

- **Corriger les 38 remarques de `trunk check --all`.** Elles portent sur du code
  vendorisé et du legacy Symfony 1 ; les traiter est un autre travail, et la règle du dépôt
  interdit de le faire à l'occasion.
- **Toucher à `pr.yml`, `pr_annotate.yml` ou `cache_trunk.yaml`.** Ils fonctionnent ;
  `pr.yml` est vert et porte déjà, en commentaire, le contournement d'un autre défaut de
  `trunk-action`.
- **Recréer un rendez-vous nocturne de lint.** Le lint qui mord est celui de la pull
  request. Un second, nocturne et sur tout l'arbre, ne serait lu par personne — c'est
  précisément ce qui vient d'être constaté sur 122 exécutions.
- **Toucher à `contrat-production.yml`**, fusionné entre-temps par la PR #188. Il tient son
  horaire et son propre rouge ; rien à y reprendre.
