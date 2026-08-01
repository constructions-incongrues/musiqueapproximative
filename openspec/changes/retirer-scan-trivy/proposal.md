## Why

Le scan Trivy analyse l'image Docker — socle Debian, nginx, PHP — et non le code du
projet. C'est le seul outil de la chaîne à regarder l'artefact déployé : CodeQL analyse
les sources, GitGuardian cherche des secrets, Dependabot et Renovate suivent les
dépendances déclarées.

Mais l'image est bâtie sur **PHP 7.4, en fin de vie depuis novembre 2022**. Le scan
remonte donc un mur de vulnérabilités `HIGH` et `CRITICAL` que rien ne peut corriger sans
migrer PHP, c'est-à-dire sans réécrire l'application. Du bruit permanent, non actionnable,
dans un onglet Security qu'on cesse de consulter.

Le rapport coût/bénéfice n'est pas favorable sur ce dépôt : un site de playlist musicale
sur un socle figé, avec trois autres outils de sécurité actifs.

Une option intermédiaire existait — `ignore-unfixed: true`, qui ne retient que les
vulnérabilités corrigeables. Elle a été écartée au profit du retrait franc.

## What Changes

- Suppression de `.github/workflows/security.yml`, dont le scan Trivy est l'unique objet.
- Retrait du badge « Security Scan » de `README.adoc` et de `docs/modules/ROOT/pages/index.adoc`,
  qui pointeraient vers un workflow inexistant.
- **BREAKING pour la protection de branche** : `Trivy Scan` ne doit surtout pas figurer
  parmi les contextes de vérification requis. Un contexte qui ne remonte jamais laisse la
  branche indéfiniment non fusionnable, sans message d'erreur — c'est exactement ce que
  `Build Docker` a provoqué pendant six mois sur ce dépôt.

### Ce que ce retrait annule

Deux changements récents perdent leur objet, et il faut le dire :

- `mettre-a-jour-trivy-action`, qui montait l'action de v0.33.1 à v0.36.0 pour réparer un
  scan qui échouait à l'installation ;
- `declencher-manuellement-scan-securite`, qui ajoutait `workflow_dispatch` pour pouvoir
  relancer le scan sans attendre un événement.

Les deux ont abouti, et leur travail est vérifié. Ils restent valides comme trace du
raisonnement : c'est en réparant ce scan qu'on a découvert qu'il était éteint depuis six
semaines, puis cassé en amont. Le retrait ne les invalide pas, il les rend sans objet —
nuance que l'archivage doit conserver.

### Hors périmètre

- L'entrée du `CHANGELOG.adoc` mentionnant l'ajout du scan : c'est un historique, il ne se
  réécrit pas.
- Les trois workflows délibérément laissés éteints — `scorecard.yml`, `nightly.yml`,
  `repomix.yml` — dont les fichiers subsistent. Leur sort reste à décider, et ce
  changement ne le préjuge pas.
- CodeQL, GitGuardian, Dependabot et Renovate, tous conservés.
- La restauration de la protection de `main`, qui incombe au mainteneur.

## Capacités

### Nouvelles capacités

Aucune.

### Capacités modifiées

Aucune. Le changement porte sur l'intégration continue, jamais sur le comportement du
site. Aucune exigence du corpus ne bouge, d'où `skip_specs: true`.

## Impact

- `.github/workflows/security.yml` : supprimé.
- `README.adoc` et `docs/modules/ROOT/pages/index.adoc` : un badge en moins.
- Contrat public **inchangé**. Aucun fichier de `src/` n'est touché.
- Le dépôt perd sa seule analyse de l'image déployée. Assumé : elle ne produisait rien
  d'actionnable tant que le socle reste figé sur PHP 7.4. À reconsidérer le jour où ce
  socle bougera — c'est alors que le scan reprendrait du sens.
