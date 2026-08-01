## 1. Alignement des contextes requis

- [x] 1.1 Dans `.github/settings.yml`, remplacer le contexte `Build Docker` par `Build et Push Docker`, nom réel du job de `ci.yml`
- [x] 1.2 Retirer `Trivy Scan` des contextes requis, le workflow qui le produirait étant désactivé
- [x] 1.3 Renommer le job `build` de `.github/workflows/security.yml` de `Build` en `Trivy Scan`, pour que le nom décrive ce que fait le job et corresponde à ce que la protection attendait

## 2. Exigence de revue

- [x] 2.1 Passer `required_approving_review_count` de `1` à `0` dans `.github/settings.yml`

## 3. Vérification manuelle

- [ ] 3.1 Contrôler si l'app GitHub Settings est installée sur le dépôt. Si elle ne l'est pas, reporter à la main les corrections des sections 1 et 2 dans Réglages → Branches → `main` : c'est la protection réelle qui compte, pas le fichier
- [ ] 3.2 Comparer la protection réellement appliquée à ce que déclare `settings.yml`, notamment `enforce_admins` : trois fusions manuelles ont abouti sur des branches non fusionnables, ce que cette règle interdirait
- [ ] 3.3 Réactiver les quatre workflows désactivés pour inactivité — `security.yml`, `scorecard.yml`, `nightly.yml`, `repomix.yml` — depuis l'onglet Actions ou via `PUT /repos/:owner/:repo/actions/workflows/:id/enable`
- [ ] 3.4 Sur la pull request de ce changement, vérifier qu'un check `Trivy Scan` apparaît bien une fois `security.yml` réactivé, et qu'il aboutit
- [ ] 3.5 Une fois 3.4 constaté, réintroduire `Trivy Scan` dans les contextes requis de `.github/settings.yml`
- [ ] 3.6 Ouvrir une pull request de contrôle, sans y toucher, et vérifier que la fusion automatique se déclenche seule une fois la CI verte — c'est le seul test qui valide l'objet de ce changement
- [ ] 3.7 Vérifier que le badge « Security Scan » du README repasse au vert, son résultat étant figé au 11 avril 2026
