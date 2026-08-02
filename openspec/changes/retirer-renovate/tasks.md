## 1. Retrait

- [x] 1.1 Supprimer `renovate.json`
- [x] 1.2 Supprimer `docs/modules/ROOT/pages/cicd/renovate.adoc`
- [x] 1.3 Retirer la ligne `renovate.adoc` de l'arbre de documentation dans
      `docs/modules/ROOT/pages/README.adoc`, et rattacher `release-please.adoc` à la
      dernière branche de l'arbre (`└──`)
- [x] 1.4 Retirer le linter `renovate@42.76.5` de la liste `lint.enabled` de
      `.trunk/trunk.yaml`

## 2. Pull requests Dependabot sans objet

Fermetures côté GitHub, sans modification de fichier. Chacune est réversible : une pull
request fermée se rouvre.

- [ ] 2.1 Fermer la **#84** (`aquasecurity/trivy-action` 0.33.1 → 0.35.0), qui modifie
      `.github/workflows/security.yml`, supprimé par `retirer-scan-trivy`
- [ ] 2.2 Fermer la **#88** (`php` 7.4.33 → 8.5.5), incompatible avec la contrainte
      `"php": "^7.4"` de `src/composer.json` et avec le socle Symfony 1.5 / Doctrine 1.3
- [ ] 2.3 Dire dans chaque fermeture pourquoi la pull request est sans objet, pour que
      Dependabot ne la recrée pas sans que la raison soit lisible

## 3. Vérification manuelle

- [ ] 3.1 Sur la pull request de ce changement, vérifier que le check `Trunk Check` passe
      au vert — c'est l'un des trois contextes requis du ruleset `main`, avec
      `Build et Push Docker` et `Validation du code`
- [ ] 3.2 Après fusion, vérifier depuis la racine du dépôt que `grep -ri renovate .` ne
      remonte plus que `repomix-output.xml` et `build/`, tous deux générés, plus les
      artefacts OpenSpec de ce changement et les mentions historiques dans
      `openspec/changes/retirer-scan-trivy/proposal.md` et
      `openspec/changes/reparer-fusion-automatique/tasks.md`. Aucune occurrence ne doit
      subsister dans `docs/modules/ROOT/pages/`, `.trunk/` ou à la racine
- [ ] 3.3 Vérifier que la documentation publiée sur
      https://constructions-incongrues.github.io/musiqueapproximative ne comporte plus de
      page Renovate sous CI/CD, et qu'aucun lien de navigation ne mène à une 404
- [ ] 3.4 Vérifier que les pull requests #84 et #88 sont bien fermées, et que les cinq
      autres — #73, #75, #83, #85, #86 — sont toujours ouvertes
- [ ] 3.5 Vérifier qu'aucune issue « Dependency Dashboard » n'apparaît dans le dépôt dans
      les jours qui suivent : ce serait le signe que l'app Renovate a été installée entre
      temps, et que ce changement a été pris à contre-pied
