# Tâches

Pas de `specs/` : `skip_specs` déclaré. Aucune route, aucun format, aucune sortie du site ne
change — c'est l'intégration continue qui est réparée. Pas de `design.md` : un job retiré
d'un fichier n'est pas un changement structurant.

## 1. Établir la cause réelle avant de corriger

- [x] 1.1 Reprendre le journal de la dernière exécution (`32176597969`) et chercher la ligne
  `jq not installed`. Elle est là — préfixée du code de couleur `^[[36;1m`, qui est le
  **listing** imprimé par GitHub avant d'exécuter une étape, pas sa sortie.
- [x] 1.2 Lire la fonction à laquelle elle appartient : `payload()`, gardée par
  `if [[ "all" == "payload" ]]`. `check-mode` vaut `all`. Ce code n'a jamais tourné, et
  l'étape « Set up inputs » conclut `outcome=success`.
- [x] 1.3 Localiser l'échec véritable : `Run trunk check on all`, `outcome=failure`, sur
  `Checked 1147 files / 8 unformatted files / 3 security issues / 27 lint issues`.
- [x] 1.4 Vérifier que c'est la même cause depuis toujours, et non une régression de juin :
  exécutions du 10 juin et du 22 juin, signature identique (`13 unformatted / 5 security /
  27 lint`).
- [x] 1.5 Compter les succès sur toute la vie du workflow : `total_count` = 122,
  `status=success` = **0**. Il n'est jamais passé au vert depuis sa création le
  2 janvier 2026.
- [x] 1.6 Constater que le job ne fait pas ce que son nom annonce : `INPUT_TRUNK_TOKEN` vide
  à chaque exécution, et l'avertissement `Check uploads and check all mode is no longer
  supported` imprimé par Trunk lui-même.

## 2. Trancher entre les trois pistes proposées

- [x] 2.1 Épingler `trunk-io/trunk-action` à une version antérieure : **écarté**, corrigerait
  un défaut qui n'existe pas.
- [x] 2.2 Installer `jq` avant l'action : **écarté**, même raison.
- [x] 2.3 Retirer le job : **retenu**. L'enquête rend la piste plus ferme que sa
  formulation — l'envoi nocturne n'a pas cessé d'avoir un usage, il n'en a jamais eu.
- [x] 2.4 Quatrième voie envisagée puis écartée : exclure le legacy et le code tiers dans
  `.trunk/trunk.yaml`. Elle modifie du même geste ce que `pr.yml` vérifie, demande de trier
  du code que personne ne corrigera, et rendrait vert un job qui n'aurait toujours rien à
  téléverser.

## 3. Ne pas perdre de couverture au passage

- [x] 3.1 Vérifier que `pr.yml` passe déjà `trunk check` sur chaque pull request, et qu'il
  est vert (exécution `32176870640`, succès).
- [x] 3.2 Vérifier que le ruleset `main` n'exige que le contexte « Validation du code ».
  « Trunk Check Upload » n'y figure pas : aucun contrôle requis ne disparaît.
- [x] 3.3 Vérifier que `cache_trunk.yaml` peuple toujours le cache Trunk à chaque
  modification de `.trunk/trunk.yaml`. Rien à reporter.

## 4. Le retrait

- [x] 4.1 La PR #188 ayant été fusionnée entre-temps, `nightly.yml` ne porte plus que
  `trunk_check` : son contrôle de production vit désormais dans `contrat-production.yml`.
  Retirer le job revient donc à vider le fichier, et un workflow sans job est invalide.
- [x] 4.2 **Supprimer `.github/workflows/nightly.yml`** plutôt que d'en retirer un job.
- [x] 4.3 Vérifier que le rendez-vous nocturne n'est pas perdu : `contrat-production.yml`
  garde le même horaire (`0 8 * * 1-5`) et le même `workflow_dispatch`.
- [x] 4.4 Consigner la raison hors du fichier, puisqu'il n'y a plus de fichier où la
  mettre : elle vit dans ce change et dans le message de commit. Sans elle, un rendez-vous
  nocturne absent est une invitation à le recréer.

## 5. Vérification

- [x] 5.1 Vérification faite avant la fusion de #188, sur un `nightly.yml` alors amputé de
  son seul job Trunk : `gh workflow run nightly.yml` → conclusion **`success`**, exécution
  [32178326989](https://github.com/constructions-incongrues/musiqueapproximative/actions/runs/32178326989),
  le 18 août 2026 à 19 h 44. **Première et unique exécution verte** du workflow en 123.
  Elle prouve que le job Trunk était bien la seule cause de l'échec.
- [x] 5.2 Le journal montre que le contrôle a réellement interrogé la production :
  `Contrat servi : statut 200, type application/yaml, 9 routes, aucune variable restante.`
- [x] 5.3 Le fichier étant maintenant supprimé, cette exécution n'est plus reproductible.
  La vérification qui la remplace porte sur le rendez-vous qui subsiste.

## 6. Ce que ce change ne ferme pas

- [ ] 6.1 **Les 38 remarques de `trunk check --all` restent.** Elles portent sur du code
  vendorisé et du legacy Symfony 1. Personne ne les voit plus la nuit, et c'est délibéré :
  elles n'étaient de toute façon lues par personne.
- [x] 6.2 **`nightly.yml` a disparu.** La PR #188, fusionnée entre-temps, en a sorti le
  contrôle de production ; ce change en retire le dernier job, donc le fichier.
- [x] 6.3 **Le commentaire de la PR #188 gravait le diagnostic `jq` erroné** dans le dépôt,
  en tête de `contrat-production.yml` et en deux points de son `tasks.md`. Corrigé sur sa
  branche (commit `d5bec71`) : la décision de #188 ne bouge pas, elle se renforce — un
  rendez-vous qui n'a jamais été vert est encore moins un endroit où loger une alerte.

### Vérification manuelle

- [ ] 6.4 Après la fusion, vérifier que le rendez-vous nocturne tient toujours, désormais
  porté par le seul `contrat-production.yml` : `gh workflow run contrat-production.yml`,
  puis contrôler que la conclusion est `success` et que le journal écrit bien `Contrat
  servi : statut 200, type application/yaml, 9 routes, aucune variable restante.`
- [ ] 6.5 Vérifier qu'aucune exécution `Nightly` n'apparaît plus au lendemain 8 h — le
  workflow n'existe plus, et c'est le seul point que sa suppression pouvait casser.
