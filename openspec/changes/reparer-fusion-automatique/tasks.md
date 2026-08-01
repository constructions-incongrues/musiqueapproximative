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
      — **fait sur la PR #105. Le test a deux temps, et le second renverse le premier.**

      **Premier temps — bloqué.** Conditions du relevé, toutes réunies : brouillon levé,
      fusion automatique armée en
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
      — Restait une seconde hypothèse, non écartée faute d'accès à l'API des rulesets : un
      contexte requis fantôme, `Build Docker` par exemple, qui ne remonterait jamais.

      **Second temps — la PR #105 a fusionné.** Une fois le blocage signalé et le ruleset
      corrigé, elle est partie. La chronologie du 1er août :

      | Heure (UTC) | Événement |
      |---|---|
      | 22:28 | fusion automatique armée en `squash`, `mergeable_state` : `blocked` |
      | 22:29:30 | poussée du commit `8a1ac19`, qui remplace la tête |
      | 22:30:13 | `ci.yml` abouti ; les autres workflows suivent vers 22:31 |
      | 22:32:48 | **fusionnée** |

      Deux lectures possibles, et il faut le dire plutôt que trancher au forceps :

      - **la fusion automatique s'est déclenchée.** C'est la lecture que la chronologie
        soutient. La tête précédente, `8075c60`, était verte dès 22:24:52 et n'a pas été
        fusionnée : elle est restée `blocked` près de quatre minutes. Rien n'a bougé tant
        que la fusion automatique n'était pas armée. Une fois armée, et une fois la CI de
        la nouvelle tête verte, la fusion suit d'environ une minute et demie — c'est le
        délai de propagation habituel de l'automatisme ;
      - **une fusion manuelle par contournement administrateur**, tombée par coïncidence
        dans cette fenêtre. L'API ne permet pas de les distinguer : dans les deux cas
        `merged_by` porte le nom du mainteneur, puisque la fusion automatique s'exécute
        au nom de qui l'a armée.

      Ce que le second temps établit sans ambiguïté, en revanche : **`main` était bien
      protégée**, et l'exigence qui bloquait a cédé dès qu'on a regardé le bon écran. La
      conclusion du premier temps — « la fusion ne se déclenche pas » — n'était vraie que
      du ruleset non corrigé.
- [x] 3.6bis Confirmer d'une pull request suivante que la fusion automatique se déclenche
      bien seule, en l'armant et en n'y touchant plus. C'est le seul geste qui départage
      les deux lectures ci-dessus, et il ne coûte rien : la prochaine pull request suffit
      — **fait sur la PR #106, et le résultat penche du côté opposé.** Chronologie :

      | Heure (UTC) | Événement |
      |---|---|
      | 22:36:10 | les neuf checks aboutissent, tous en succès |
      | 22:38:41 | brouillon levé, fusion automatique armée en `squash` ; `mergeable_state` : `blocked` |
      | 22:38 → 22:59 | **vingt minutes sans rien.** Aucune poussée, aucun check, aucun changement d'état |
      | 22:59:03 | fusionnée |

      Ces vingt minutes sont la donnée neuve. Une fusion automatique armée sur une pull
      request qui devient fusionnable part en une ou deux minutes — c'est d'ailleurs le
      délai observé sur la #105. Vingt minutes d'immobilité sur une pull request verte et
      armée disent l'inverse : **elle n'est jamais devenue fusionnable d'elle-même.**
      L'exigence du ruleset tenait toujours.

      L'explication la plus économique couvre alors les deux pull requests sans rien
      supposer de plus : **le mainteneur fusionne à la main, par contournement
      administrateur, quand il y vient.** Promptement sur la #105, une vingtaine de minutes
      plus tard sur la #106. Le délai d'une minute et demie de la #105, que j'avais lu
      comme la signature de l'automatisme, n'était qu'une coïncidence de rapidité.

      **Conséquence : la conclusion de 3.6, second temps, penche du mauvais côté.** Elle
      présentait deux lectures en donnant l'avantage à « la fusion automatique s'est
      déclenchée ». La #106 retire cet avantage. Sur les quelque dix-sept pull requests
      observées, **aucune n'a jamais fusionné autrement qu'à la main.**

      Ce qui reste vrai de 3.6 : `main` est protégée, et c'est bien un ruleset qui la
      gouverne. Ce qui tombe : l'idée que le blocage aurait cédé.
- [ ] 3.6ter **Lire la section « Rules » du ruleset `main`.** C'est la seule information
      qui manque encore, et aucun outil accessible d'ici ne la donne — l'API des rulesets
      n'est pas exposée. Deux réglages suffisent : *Required approvals*, à mettre à **0**,
      et la liste des contextes requis, qui ne doit contenir que `Validation du code`,
      `Build et Push Docker` et `Trunk Check`. Tant qu'elle n'est pas lue, l'enquête
      raisonne sur une hypothèse au lieu d'un fait — c'est précisément l'erreur qui lui a
      coûté trois conclusions fausses
- [x] 3.7 Vérifier que le badge « Security Scan » du README repasse au vert, son résultat étant figé au 11 avril 2026
      — le run #202 de « Security Checks », déclenché à la main sur `main`, a abouti.
      Le badge affiche la conclusion du dernier run sur la branche par défaut.
