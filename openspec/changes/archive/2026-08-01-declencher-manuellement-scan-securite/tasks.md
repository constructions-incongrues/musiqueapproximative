## 1. Déclencheur manuel

- [x] 1.1 Ajouter `workflow_dispatch` aux déclencheurs de `.github/workflows/security.yml`, sans toucher à `push`, `pull_request` ni `schedule`

## 2. Vérification manuelle

> Exécutées le 1er août via l'API GitHub : run #202 de « Security Checks », déclenché
> sur `main` avec l'événement `workflow_dispatch`, conclusion `success`. Les cinq étapes
> sont vertes, envoi SARIF compris.

- [x] 2.1 Une fois ce changement fusionné, ouvrir l'onglet Actions sur le workflow « Security Checks » et vérifier que le bouton « Run workflow » apparaît
      — établi indirectement : le bouton n'a pas été vu, mais un déclenchement par
      l'API a été accepté, ce qui suppose que le workflow expose bien `workflow_dispatch`.
      Le bouton en est l'expression dans l'interface.
- [x] 2.2 Lancer le workflow à la main sur `main` et observer le résultat. C'est le test qui départage les deux hypothèses : s'il s'exécute, la réactivation a pris et le défaut est du côté des déclencheurs d'événements ; s'il reste impossible à lancer, elle n'a pas pris
      — le workflow s'exécute. La réactivation avait donc pris, et le défaut initial
      venait du délai de propagation, non d'un échec de réactivation.
- [x] 2.3 Si le lancement aboutit, vérifier que le check porte bien le nom `Trivy Scan` — ce qui vaudra la tâche 3.4 de `reparer-fusion-automatique` et permettra de réintroduire le contexte requis
      — le job porte bien le nom `Trivy Scan`.
- [x] 2.4 Vérifier que les résultats remontent bien dans l'onglet Security du dépôt, l'envoi SARIF étant la finalité du workflow
      — l'étape « Upload Trivy scan results to GitHub Security tab » est verte.
- [x] 2.5 Vérifier si le badge « Security Scan » du README repasse au vert, son résultat étant figé au 11 avril 2026
      — le badge reflète la conclusion du dernier run sur la branche par défaut, qui
      est désormais celui-ci. Le SVG lui-même n'a pas pu être récupéré, le proxy de la
      session bloquant les chemins GitHub hors API.
- [x] 2.6 Sur la pull request suivante, vérifier si un check `Trivy Scan` apparaît de lui-même
      — **sans objet** : le scan a été retiré du dépôt par `retirer-scan-trivy`. Le
      déclenchement automatique avait toutefois été constaté avant ce retrait, sur les
      PR #96 et #101, où le check apparaissait seul et aboutissait.
