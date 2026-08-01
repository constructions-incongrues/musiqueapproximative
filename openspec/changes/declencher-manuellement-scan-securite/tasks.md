## 1. Déclencheur manuel

- [x] 1.1 Ajouter `workflow_dispatch` aux déclencheurs de `.github/workflows/security.yml`, sans toucher à `push`, `pull_request` ni `schedule`

## 2. Vérification manuelle

- [ ] 2.1 Une fois ce changement fusionné, ouvrir l'onglet Actions sur le workflow « Security Checks » et vérifier que le bouton « Run workflow » apparaît
- [ ] 2.2 Lancer le workflow à la main sur `main` et observer le résultat. C'est le test qui départage les deux hypothèses : s'il s'exécute, la réactivation a pris et le défaut est du côté des déclencheurs d'événements ; s'il reste impossible à lancer, elle n'a pas pris
- [ ] 2.3 Si le lancement aboutit, vérifier que le check porte bien le nom `Trivy Scan` — ce qui vaudra la tâche 3.4 de `reparer-fusion-automatique` et permettra de réintroduire le contexte requis
- [ ] 2.4 Vérifier que les résultats remontent bien dans l'onglet Security du dépôt, l'envoi SARIF étant la finalité du workflow
- [ ] 2.5 Vérifier si le badge « Security Scan » du README repasse au vert, son résultat étant figé au 11 avril 2026
- [ ] 2.6 Sur la pull request suivante, vérifier si un check `Trivy Scan` apparaît de lui-même. C'est ce qui dira si le déclenchement automatique est rétabli, indépendamment du lancement manuel
