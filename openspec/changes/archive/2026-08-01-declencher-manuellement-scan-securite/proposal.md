## Why

`security.yml` a été réactivé après six semaines de désactivation pour inactivité, et
l'API GitHub le rapporte bien `active`. Pourtant il ne s'est toujours pas exécuté : sa
dernière exécution reste celle du 24 juin, en cron.

Trois événements auraient dû le déclencher depuis la réactivation — la fusion de la
pull request #92 sur `main`, l'ouverture de la #93, puis sa fusion. Aucun n'a produit
d'exécution, alors que le fichier déclare `push` et `pull_request` sur `main`, sans
filtre de chemins.

Deux hypothèses restent en présence, et rien dans le dépôt ne permet de les départager :
la réactivation met du temps à se propager côté GitHub, ou elle n'a pas réellement pris
malgré ce que rapporte l'API.

Le workflow ne déclare aucun déclencheur manuel. Il est donc impossible de le lancer pour
observer ce qui se passe : on ne peut qu'attendre un événement et constater son absence
d'effet. C'est précisément ce qui manque pour trancher.

## What Changes

- `security.yml` déclare `workflow_dispatch`, ce qui permet de le lancer à la demande
  depuis l'onglet Actions ou par l'API.
- Rien d'autre ne change : ni les déclencheurs existants, ni les étapes, ni les
  permissions, ni la périodicité du cron.

Ce changement **ne répare rien par lui-même**. Il rend observable un état qui ne l'est
pas : si un lancement manuel aboutit, la réactivation a pris et le problème est ailleurs,
du côté des déclencheurs d'événements ; s'il échoue ou reste impossible, la réactivation
n'a pas pris.

Quatre workflows du dépôt déclarent déjà `workflow_dispatch` — `documentation.yml`,
`nightly.yml`, `pr.yml` et `repomix.yml`. L'ajout suit une pratique établie, il
n'introduit pas de convention nouvelle.

### Hors périmètre

- Les autres workflows dépourvus de déclencheur manuel : `ci.yml`, `scorecard.yml`,
  `release-please.yml`, `auto-rebase.yml`, `cache_trunk.yaml`. Certains ne s'y prêtent
  pas, et aucun ne pose le problème de diagnostic qui motive ce changement.
- La réactivation de `scorecard.yml`, `nightly.yml` et `repomix.yml`, délibérément
  laissés éteints.
- Le sort des fichiers de ces trois workflows, présents au dépôt mais inertes.
- Le fond du scan lui-même : image analysée, seuils de sévérité, envoi SARIF.
- Les tâches encore ouvertes de `reparer-fusion-automatique`, qui suivent leur cours.

## Capacités

### Nouvelles capacités

Aucune.

### Capacités modifiées

Aucune. Le changement porte sur l'intégration continue, jamais sur le comportement du
site. Aucune exigence du corpus ne bouge, d'où `skip_specs: true`.

## Impact

- `.github/workflows/security.yml` : ajout d'un déclencheur.
- Contrat public **inchangé**. Aucun fichier de `src/` n'est touché.
- Aucune dépendance ajoutée, aucune migration, aucun changement de configuration du
  dépôt.
