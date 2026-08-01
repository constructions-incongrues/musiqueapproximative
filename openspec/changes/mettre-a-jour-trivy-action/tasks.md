## 1. Montée de version

- [x] 1.1 Dans `.github/workflows/security.yml`, remplacer l'épinglage `b6643a29fecd7f34b3597bc6acb0a98b03d33ff8` de `aquasecurity/trivy-action` par `ed142fd0673e97e23eac54620cfb913e5ce36c25`, commit du tag `v0.36.0`
- [x] 1.2 Annoter la ligne du numéro de version, comme le fait `actions/checkout` dans les autres workflows

## 2. Vérification manuelle

- [ ] 2.1 Sur la pull request de ce changement, vérifier que l'étape d'installation de Trivy aboutit — c'est elle qui échouait, avant même que le scan ne démarre
- [ ] 2.2 Vérifier que le scan s'exécute et que le check `Trivy Scan` passe au vert. Si l'échec persiste, relever le nouveau message : il désignera l'étape suivante, la montée de version étant le seul remède disponible sans renoncer à l'action
- [ ] 2.3 Vérifier que le fichier `trivy-results.sarif` est bien produit, le gabarit `@/contrib/sarif.tpl` étant récupéré en amont et pouvant avoir bougé
- [ ] 2.4 Vérifier que les résultats remontent dans l'onglet Security du dépôt, l'envoi SARIF étant la finalité du workflow
- [ ] 2.5 Examiner les vulnérabilités remontées : Trivy passe de v0.65.0 à v0.70.0, et un moteur plus récent peut signaler ce que l'ancien ignorait. Un premier scan sévère est un résultat, pas une régression
- [ ] 2.6 Vérifier que le badge « Security Scan » du README repasse au vert, son résultat étant figé au 11 avril 2026
