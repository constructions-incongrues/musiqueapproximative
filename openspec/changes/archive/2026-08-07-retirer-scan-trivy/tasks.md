## 1. Retrait

- [x] 1.1 Supprimer `.github/workflows/security.yml`
- [x] 1.2 Retirer le badge « Security Scan » de `README.adoc`
- [x] 1.3 Retirer le même badge de `docs/modules/ROOT/pages/index.adoc`

## 2. Vérification manuelle

- [x] 2.1 Sur la pull request de ce changement, vérifier qu'aucun check `Trivy Scan` n'apparaît plus
      — vérifié sur la PR #103 : le check a disparu de la liste, alors qu'il figurait sur
      toutes les pull requests depuis la #96.
- [x] 2.2 **Vérifier que `Trivy Scan` ne figure pas parmi les contextes requis du ruleset `main`**, et l'en retirer le cas échéant. Un contexte qui ne remonte jamais laisse la branche indéfiniment non fusionnable, sans message d'erreur — c'est le premier suspect pour expliquer que la fusion automatique n'ait jamais abouti. Les trois contextes qui subsistent sont `Validation du code`, `Build et Push Docker` et `Trunk Check`. À relever sous Réglages → Rules → Rulesets → `main`, et non sous Réglages → Branches
      — **vérifié le 2 août 2026 : le piège n'a pas été retendu.** Les contextes requis du
      ruleset sont exactement `Trunk Check`, `Build et Push Docker` et `Validation du
      code`. Aucune trace de `Trivy Scan`, ni de `Build Docker`, le nom fantôme qui avait
      bloqué la branche six mois durant.
- [x] 2.3 Vérifier que le README et la page d'accueil de la documentation ne comportent plus de badge mort
      — vérifié sur `main` : zéro occurrence de `security.yml` dans `README.adoc` et dans
      `docs/modules/ROOT/pages/index.adoc`, et le fichier de workflow n'existe plus.
- [x] 2.4 Traiter le badge mort préexistant de `docs/modules/ROOT/pages/index.adoc`, qui pointe vers `lint.yml` — ce workflow n'existe pas dans le dépôt. Repéré à l'occasion, hors périmètre de ce changement
      — corrigé : le badge vise désormais `pr.yml`, comme celui du `README.adoc`, qui portait
      déjà la bonne cible sous le même libellé « Lint ». Les deux fichiers sont alignés. Le
      dépôt ne contient aucun `lint.yml` ; `pr.yml` est bien le workflow qui exécute le
      contrôle.
- [x] 2.5 Vérifier que les alertes déjà remontées par Trivy restent consultables dans l'onglet Security, ou acter leur disparition
      — elles restent consultables. L'API de code scanning rapporte 2143 alertes, dont
      certaines de l'outil `Trivy`, aux côtés de `CodeQL`, `PHPMD` et `Scorecard`. Retirer
      le workflow n'efface pas ce qu'il avait remonté.
