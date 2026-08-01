## 1. Alignement des contextes requis

- [x] 1.1 Dans `.github/settings.yml`, remplacer le contexte `Build Docker` par `Build et Push Docker`, nom réel du job de `ci.yml`
- [x] 1.2 Retirer `Trivy Scan` des contextes requis, le workflow qui le produirait étant désactivé
- [x] 1.3 Renommer le job `build` de `.github/workflows/security.yml` de `Build` en `Trivy Scan`, pour que le nom décrive ce que fait le job et corresponde à ce que la protection attendait

## 2. Exigence de revue

- [x] 2.1 Passer `required_approving_review_count` de `1` à `0` dans `.github/settings.yml`

## 3. Vérification manuelle

- [x] 3.1 Contrôler si l'app GitHub Settings est installée sur le dépôt
      — **elle ne l'était pas.** `settings.yml` ne pilotait donc rien : les corrections
      des sections 1 et 2, fusionnées par la PR #92, n'ont modifié qu'un fichier
      décoratif. Elle a depuis été installée, par le changement
      `appliquer-settings-yml-par-lapp`.
- [x] 3.2 Reporter à la main dans Réglages → Branches → `main` les corrections que `settings.yml` déclarait sans les appliquer — fait, indépendamment de l'installation de l'app.
- [ ] 3.3bis Relever la protection réellement appliquée, notamment `enforce_admins` : sept fusions manuelles ont abouti sur des branches non fusionnables, ce que cette règle interdirait si elle était active
- [x] 3.3 Réactiver `security.yml`, désactivé pour inactivité — fait, le workflow est repassé `active`. Le scan de sécurité peut de nouveau s'exécuter.
      — `scorecard.yml`, `nightly.yml` et `repomix.yml` sont **délibérément laissés
      éteints** : seul le scan de sécurité importait. Leurs fichiers restent au dépôt
      sans jamais s'exécuter, ce qui est un piège pour qui les lira — leur retrait
      mériterait un changement dédié.
- [x] 3.4 Vérifier qu'un check `Trivy Scan` apparaît sur une pull request et qu'il aboutit
      — constaté sur la PR #96 : le check est apparu de lui-même et a abouti, une fois
      `trivy-action` montée en version. Le déclenchement automatique fonctionne, avec un
      délai de propagation d'une dizaine de minutes après la réactivation du workflow.
- [ ] 3.5 Une fois la protection réelle corrigée (3.2), y réintroduire `Trivy Scan` parmi les contextes requis, et le remettre dans `.github/settings.yml` pour que le fichier reste fidèle
- [ ] 3.6 Ouvrir une pull request de contrôle, sans y toucher, et vérifier que la fusion automatique se déclenche seule une fois la CI verte — c'est le seul test qui valide l'objet de ce changement
- [x] 3.7 Vérifier que le badge « Security Scan » du README repasse au vert, son résultat étant figé au 11 avril 2026
      — le run #202 de « Security Checks », déclenché à la main sur `main`, a abouti.
      Le badge affiche la conclusion du dernier run sur la branche par défaut.
