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
      — **mauvais écran.** Réglages → Branches gouverne les règles classiques ; `main` est
      protégée par un ruleset, sous Réglages → Rules → Rulesets. Les corrections portées
      là n'ont donc jamais rencontré la protection qui s'applique réellement, ce qui
      explique qu'elles aient paru s'évaporer d'une fusion à l'autre.
- [x] 3.3bis Relever la protection réellement appliquée, notamment `enforce_admins` : sept fusions manuelles ont abouti sur des branches non fusionnables, ce que cette règle interdirait si elle était active
      — **relevé, et c'est le nœud de toute l'affaire.** `main` n'est pas gouvernée par une
      règle classique mais par un **ruleset** — un second système de protection, avec sa
      propre interface et sa propre API, sur lequel `settings.yml` et l'app Settings n'ont
      aucune prise. Ce ruleset est actif, et sa liste de contournement comporte
      « Repository admin — Always allow », l'équivalent d'`enforce_admins: false`.
      Les sept fusions manuelles s'expliquent : elles empruntaient ce contournement.
      La fusion automatique, elle, ne l'emprunte pas — elle attend une pull request
      réellement fusionnable. D'où les deux états observés ensemble tout au long de
      l'enquête : `blocked` pour l'automatisme, et fusionnable à la main.
- [x] 3.3 Réactiver `security.yml`, désactivé pour inactivité — fait, le workflow est repassé `active`. Le scan de sécurité peut de nouveau s'exécuter.
      — `scorecard.yml`, `nightly.yml` et `repomix.yml` sont **délibérément laissés
      éteints** : seul le scan de sécurité importait. Leurs fichiers restent au dépôt
      sans jamais s'exécuter, ce qui est un piège pour qui les lira — leur retrait
      mériterait un changement dédié.
- [x] 3.4 Vérifier qu'un check `Trivy Scan` apparaît sur une pull request et qu'il aboutit
      — constaté sur la PR #96 : le check est apparu de lui-même et a abouti, une fois
      `trivy-action` montée en version. Le déclenchement automatique fonctionne, avec un
      délai de propagation d'une dizaine de minutes après la réactivation du workflow.
- [x] 3.5 Réintroduire `Trivy Scan` parmi les contextes requis
      — **annulée.** Cette tâche contredisait `retirer-scan-trivy` 2.2, qui met en garde
      contre exactement ce geste. Le scan a été retiré du dépôt : exiger son contexte
      laisserait la branche indéfiniment non fusionnable, reproduisant le blocage que
      `Build Docker` a provoqué six mois durant. Les contextes à déclarer sont
      `Validation du code`, `Build et Push Docker` et `Trunk Check`.
- [x] 3.3ter Établir ce qui, dans ce dépôt, est censé **activer** la fusion automatique
      — **rien.** C'est le second volet de l'explication, et le plus embarrassant : la
      fusion automatique de GitHub n'a rien d'automatique. Elle s'active pull request par
      pull request, à la main ou par un appel d'API. Aucun workflow du dépôt ne le fait —
      les dix fichiers de `.github/workflows/` ne mentionnent jamais l'auto-merge — et
      aucune app ne s'en charge.
      — Conséquence pour les pull requests de cette enquête : **elle n'y a jamais été
      activée**, et il n'y avait donc rien à déclencher. Un obstacle mécanique s'y ajoute :
      elles sont ouvertes en brouillon, et GitHub refuse d'activer la fusion automatique
      sur un brouillon. Vérifié sur la PR #105, où l'API répond « Pull request is a draft ».
      — Cela ne vaut pas pour les #89, #90 et #91, où l'énoncé de ce changement rapporte
      qu'elle avait bien été armée. Ce sont ces trois-là que le ruleset explique, par le
      contournement administrateur relevé en 3.3bis.
- [x] 3.3quater Vérifier ce que fait Renovate, dont la configuration déclare `automerge: true`
      — **elle ne fait rien : Renovate n'a jamais ouvert une seule pull request sur ce
      dépôt**, aucun état confondu. L'app n'est pas installée. `renovate.json` rejoint donc
      `settings.yml`, la banque de mémoire et les badges morts : un quatrième document qui
      décrit sans contraindre. Les mises à jour de dépendances viennent toutes de
      Dependabot, qui n'a pas de fusion automatique configurée.
- [x] 3.6 Ouvrir une pull request de contrôle, sans y toucher, et vérifier que la fusion automatique se déclenche seule une fois la CI verte — c'est le seul test qui valide l'objet de ce changement
      — **fait sur la PR #105, et le test est concluant : la fusion ne se déclenche pas.**
      Conditions du relevé, toutes réunies : brouillon levé, fusion automatique armée en
      `squash`, **neuf checks terminés et tous en succès** — `Validation du code`,
      `Build et Push Docker`, `Trunk Check`, `Trunk Check Runner`, `CodeQL`,
      `Analyze (actions)`, `Analyze (javascript-typescript)`, `GitGuardian Security
      Checks`, plus `Créer alias de version` en `skipped`. Aucun `Trivy Scan`.
      `mergeable_state` reste `blocked`.
      — Il ne reste donc qu'une exigence non satisfaite, et **zéro revue** figure sur la
      pull request. L'explication qui tient sans rien supposer d'autre : le ruleset exige
      au moins une approbation. Sur un dépôt à un seul mainteneur, qui est aussi l'auteur
      de la pull request, GitHub interdit l'auto-approbation — l'exigence est donc
      **structurellement insatisfiable**, et le contournement administrateur est la seule
      issue. C'est exactement le comportement observé depuis le début.
      — Le diagnostic initial de ce changement avait vu juste sur ce point : il visait
      `required_approving_review_count: 1`, et le faisait passer à `0`. Mais il l'a corrigé
      dans `settings.yml`, qui ne pilote rien, au lieu du ruleset, qui gouverne. Le
      correctif était bon ; il a été appliqué au mauvais endroit.
      — Reste une seconde hypothèse, non écartée faute d'accès à l'API des rulesets : un
      contexte requis fantôme, `Build Docker` par exemple, qui ne remonterait jamais. Elle
      se départage d'un coup d'œil à 3.4bis.
- [x] 3.7 Vérifier que le badge « Security Scan » du README repasse au vert, son résultat étant figé au 11 avril 2026
      — le run #202 de « Security Checks », déclenché à la main sur `main`, a abouti.
      Le badge affiche la conclusion du dernier run sur la branche par défaut.
