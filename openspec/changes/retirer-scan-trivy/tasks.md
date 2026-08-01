## 1. Retrait

- [x] 1.1 Supprimer `.github/workflows/security.yml`
- [x] 1.2 Retirer le badge « Security Scan » de `README.adoc`
- [x] 1.3 Retirer le même badge de `docs/modules/ROOT/pages/index.adoc`

## 2. Vérification manuelle

- [x] 2.1 Sur la pull request de ce changement, vérifier qu'aucun check `Trivy Scan` n'apparaît plus
      — vérifié sur la PR #103 : le check a disparu de la liste, alors qu'il figurait sur
      toutes les pull requests depuis la #96.
- [ ] 2.2 **Au moment de restaurer la protection de `main`, ne pas y déclarer `Trivy Scan` comme contexte requis.** Un contexte qui ne remonte jamais laisse la branche indéfiniment non fusionnable, sans message d'erreur. Les trois contextes qui subsistent sont `Validation du code`, `Build et Push Docker` et `Trunk Check`
- [x] 2.3 Vérifier que le README et la page d'accueil de la documentation ne comportent plus de badge mort
      — vérifié sur `main` : zéro occurrence de `security.yml` dans `README.adoc` et dans
      `docs/modules/ROOT/pages/index.adoc`, et le fichier de workflow n'existe plus.
- [ ] 2.4 Traiter le badge mort préexistant de `docs/modules/ROOT/pages/index.adoc`, qui pointe vers `lint.yml` — ce workflow n'existe pas dans le dépôt. Repéré à l'occasion, hors périmètre de ce changement
- [ ] 2.5 Vérifier que les alertes déjà remontées par Trivy restent consultables dans l'onglet Security, ou acter leur disparition
