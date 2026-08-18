## Why

Le contrat OpenAPI a répondu **404 pendant une journée** parce qu'il était un gabarit
`-dist` : les rendus sont gitignorés, et le déploiement se fait par `git pull`. La
convention datait du déploiement **par rsync**, qui rendait les fichiers *avant* de les
envoyer. Depuis le basculement, **aucun fichier `-dist` ajouté n'arrive en production**.

La question posée était : dans quel état sont les autres ? La story commençait par mesurer,
et la mesure a donné un résultat plus petit et plus intéressant que prévu.

**Il ne reste que deux fichiers `-dist`** — `apps/frontend/config/app.yml-dist` et
`config/databases.yml-dist`. Le contrat était le troisième ; il a quitté la convention le
soir même. Et **ces deux-là ne peuvent pas la quitter** : ils portent des identifiants de
base, un jeton Cloudflare et des valeurs propres au domaine. Les verser rendus serait
publier des secrets.

Les valeurs publiquement observables ont été confrontées à ce que le profil de production
produirait :

| valeur | attendue | servie par la production |
| --- | --- | --- |
| titre de page | Musique Approximative | ✅ |
| URL des pistes | `//www.musiqueapproximative.net/tracks/` | ✅ |
| `og:url` | `https://www.musiqueapproximative.net/…` | ✅ |
| autoplay | 0 | ✅ |

**Rien n'a dérivé.** Le résultat de cette story est donc négatif, et c'est ce qu'elle
apprend : le danger n'était pas l'état de ces fichiers, c'est qu'**aucun mécanisme ne
signalerait leur dérive.** `databases.yml` en est l'exemple net — la ligne `encoding:
utf8mb4`, posée aujourd'hui, n'est observable d'aucune façon depuis l'extérieur. Si elle
n'était pas rendue en production, la connexion convertirait toujours et personne ne le
saurait avant le prochain titre détruit.

## What Changes

- Ajout d'une page de documentation qui consigne l'inventaire, la mesure, et la décision
  pour chacun des deux fichiers : ils **restent des gabarits**, parce qu'ils portent des
  secrets.
- La page consigne aussi le fait d'exploitation qui a mis le site à terre ce jour :
  **`make configure` n'est pas exécutable sur le serveur**. Il lit `src/.env`, qui n'y
  existe pas — c'est un montage Docker en développement. Lancé là-bas, il réécrit toute la
  configuration en gabarits bruts.

## Hors périmètre

- **Rendre la dérive détectable.** C'est le vrai manque, et il déborde ce fichier : il vaut
  pour le contrat comme pour la configuration. Il relève de la story 23.
- **Rendre `make configure` exécutable sur le serveur.** Le documenter suffit ici ; le
  corriger demande de décider où vit le `.env` de production, ce qui est une question de
  déploiement.
- Toucher aux deux fichiers eux-mêmes. Ils sont corrects.
