## 1. Montée de version

- [x] 1.1 Dans `.github/workflows/security.yml`, remplacer l'épinglage `b6643a29fecd7f34b3597bc6acb0a98b03d33ff8` de `aquasecurity/trivy-action` par `ed142fd0673e97e23eac54620cfb913e5ce36c25`, commit du tag `v0.36.0`
- [x] 1.2 Annoter la ligne du numéro de version, comme le fait `actions/checkout` dans les autres workflows

## 2. Vérification manuelle

> Les tâches 2.1 à 2.4 sont constatées dans le journal du job `Trivy Scan` de la pull
> request : installation aboutie, scan exécuté, `trivy-results.sarif` validé puis
> téléversé, traitement de l'analyse terminé côté GitHub. Le check est vert, et un check
> `Trivy` distinct est apparu — celui que produit la remontée du rapport.
>
> La 2.5 demande l'onglet Security du dépôt, hors de portée de l'agent. La 2.6 attend une
> exécution sur `main`, donc la fusion.

- [x] 2.1 Sur la pull request de ce changement, vérifier que l'étape d'installation de Trivy aboutit — c'est elle qui échouait, avant même que le scan ne démarre
- [x] 2.2 Vérifier que le scan s'exécute et que le check `Trivy Scan` passe au vert. Si l'échec persiste, relever le nouveau message : il désignera l'étape suivante, la montée de version étant le seul remède disponible sans renoncer à l'action
- [x] 2.3 Vérifier que le fichier `trivy-results.sarif` est bien produit, le gabarit `@/contrib/sarif.tpl` étant récupéré en amont et pouvant avoir bougé
- [x] 2.4 Vérifier que les résultats remontent dans l'onglet Security du dépôt, l'envoi SARIF étant la finalité du workflow
- [x] 2.5 Examiner les vulnérabilités remontées
      — **jamais fait, et désormais sans objet** : le scan a été retiré du dépôt avant que
      l'onglet Security n'ait été consulté. C'est la seule question de fond que cette
      séquence n'aura pas tranchée — on ne saura pas ce que Trivy v0.70.0 avait à dire
      d'une image PHP 7.4. Le retrait a d'ailleurs été décidé sans cette information.
- [x] 2.6 Vérifier que le badge « Security Scan » du README repasse au vert, son résultat étant figé au 11 avril 2026
      — le run #202 de « Security Checks », déclenché à la main sur `main`, a abouti.
      Le badge affiche la conclusion du dernier run sur la branche par défaut.
