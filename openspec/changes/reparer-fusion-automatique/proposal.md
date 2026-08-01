## Why

Trois pull requests successives — #89, #90, #91 — ont été fusionnées à la main alors que
la CI était intégralement verte et que la fusion automatique avait été armée. Elle ne se
déclenche jamais, et `mergeable_state` reste indéfiniment à `blocked`.

L'enquête révèle trois causes, dont une dépasse largement la question de la fusion.

**Deux contextes requis ne remontent jamais.** La protection déclarée exige quatre
contextes ; deux ne correspondent à rien :

- `Build Docker` : le job s'appelle en réalité `Build et Push Docker`.
- `Trivy Scan` : aucun job de ce nom. Le scan Trivy tourne dans un job nommé `Build`.

Un contexte requis qui ne remonte pas reste en « Expected — Waiting for status to be
reported », et la branche ne devient jamais fusionnable.

**Le scan de sécurité ne tourne plus depuis six semaines.** C'est la découverte la plus
sérieuse, et elle est indépendante de la fusion automatique. GitHub a désactivé quatre
workflows pour inactivité du dépôt :

| Workflow | Désactivé depuis | Conséquence |
|---|---|---|
| `security.yml` | 2026-06-24 | **Aucun scan Trivy, aucun envoi SARIF, sur aucune PR ni aucun push** |
| `scorecard.yml` | 2026-06-27 | Plus d'analyse OpenSSF Scorecard |
| `nightly.yml` | 2026-06-22 | Plus de contrôle Trunk nocturne |
| `repomix.yml` | 2026-06-22 | Plus de génération de `repomix-output.xml` |

La désactivation porte sur le workflow entier, pas seulement sur son déclencheur
planifié : `security.yml` ne s'est donc exécuté sur aucune des trois pull requests
récentes, alors qu'il déclare bien `pull_request`. Le badge « Security Scan » du README
affiche un résultat figé au 11 avril.

**La revue obligatoire est insatisfiable.** `required_approving_review_count: 1`, et
GitHub interdit d'approuver sa propre pull request. Sur un dépôt tenu par une seule
personne, aucune pull request ne peut donc jamais réunir son approbation.

## What Changes

- `Build Docker` devient `Build et Push Docker` dans les contextes requis, pour
  correspondre au nom réel du job.
- `Trivy Scan` est **retiré** des contextes requis. Exiger un contexte produit par un
  workflow désactivé garantit un blocage permanent. Une tâche prévoit sa réintroduction
  une fois le workflow réactivé et son exécution constatée.
- Le job `build` de `security.yml`, jusqu'ici nommé `Build`, est renommé `Trivy Scan` :
  c'est ce qu'il fait, et c'est le nom que la protection attendait.
- `required_approving_review_count` passe de `1` à `0`. **C'est le seul arbitrage de
  sécurité de ce changement**, détaillé ci-dessous.
- Aucun comportement du site n'est touché. Le changement pose `skip_specs: true`.

### L'arbitrage sur la revue

Passer l'exigence à `0` retire le garde-fou humain avant fusion. Sur un dépôt à un seul
mainteneur, ce garde-fou n'existait déjà pas : il ne produisait qu'un blocage contourné
à la main à chaque fusion, ce qui est la pire des situations — la règle donne l'illusion
d'un contrôle tout en étant systématiquement neutralisée.

Ce qui subsiste comme protection : les contextes requis, l'historique linéaire,
l'interdiction des poussées forcées et des suppressions de branche. Le contrôle passe de
« quelqu'un a relu » à « la CI est verte », ce qui correspond à la réalité du dépôt.

L'alternative, si tu préfères conserver l'exigence : accepter que chaque fusion reste
manuelle, et ne plus armer la fusion automatique. C'est cohérent aussi, mais il faut
alors retirer l'attente.

### Hors périmètre

- **La réactivation des workflows désactivés.** Elle ne peut pas se faire par un commit :
  c'est une action manuelle depuis l'onglet Actions, ou un appel à l'API
  `PUT /actions/workflows/{id}/enable`. Une tâche la trace, mais elle incombe au
  mainteneur.

  Décision prise depuis : seul `security.yml` a été réactivé. `scorecard.yml`,
  `nightly.yml` et `repomix.yml` restent délibérément éteints, le scan de sécurité étant
  le seul qui importait. Leurs fichiers subsistent au dépôt sans jamais s'exécuter — un
  changement dédié devrait décider de leur retrait, car un workflow présent mais inerte
  induit en erreur qui le lit.
- `strict: true` sur les contextes requis, qui impose en plus que la branche soit à jour
  avec `main`. Ce n'est pas ce qui bloque aujourd'hui.
- `enforce_admins: true`, que la réalité contredit déjà — trois fusions manuelles ont
  abouti, ce qui serait impossible si la règle était appliquée telle que déclarée.
- Le fond des workflows eux-mêmes : périodicité, étapes, versions d'actions.

## Capacités

### Nouvelles capacités

Aucune.

### Capacités modifiées

Aucune. Le changement porte sur la configuration du dépôt et sur l'intégration continue,
jamais sur le comportement du site. Aucune exigence du corpus ne bouge, d'où
`skip_specs: true`.

## Impact

- `.github/settings.yml` : contextes requis et nombre d'approbations exigées.
- `.github/workflows/security.yml` : nom du job.
- Contrat public **inchangé**. Aucun fichier de `src/` n'est touché.

**Réserve importante, depuis confirmée** : `.github/settings.yml` n'est qu'une
déclaration, appliquée uniquement si l'app GitHub Settings est installée sur le dépôt.
Un indice donnait à penser qu'elle ne l'était pas — `enforce_admins: true` y figure,
alors que trois fusions manuelles ont abouti sur des branches non fusionnables.

Vérification faite : **l'app n'est pas installée**. Éditer ce fichier n'a donc rien changé
à la protection réelle. Les corrections des sections 1 et 2 sont restées lettre morte, et
la fusion automatique demeure cassée pour les raisons décrites plus haut. Elles doivent
être reportées à la main dans Réglages → Branches, ce que trace la tâche 3.2.

Ce constat désigne un problème plus large que ce changement : `settings.yml` décrit une
configuration qui n'est pas celle du dépôt. C'est le troisième artefact de ce genre
rencontré ici, après `docs/memory-bank/README.adoc` — supprimé pour avoir dérivé — et les
workflows désactivés dont les fichiers subsistent. Décider du sort de `settings.yml`
— l'appliquer en installant l'app, le supprimer, ou le marquer explicitement comme
indicatif — dépasse le périmètre de ce changement et mérite le sien.
