## Why

Renovate n'a jamais ouvert une seule pull request sur ce dépôt. Le constat a été établi
par la tâche 3.3quater du changement `reparer-fusion-automatique` : l'app n'est pas
installée, et aucun « Dependency Dashboard » — que `renovate.json` réclame pourtant
explicitement — n'existe dans les issues du dépôt. Toutes les mises à jour de dépendances
viennent de Dependabot, configuré par `.github/dependabot.yml`.

Le fichier `renovate.json` déclare donc, dans le vide, un `automerge: true` sur les
mises à jour mineures et patches, une planification `at any time` et un fuseau
`Europe/Paris`. Aucune de ces règles n'a jamais été évaluée. La page
`docs/modules/ROOT/pages/cicd/renovate.adoc` va plus loin : elle décrit sur quatre-vingts
lignes un fonctionnement qui n'a jamais eu lieu — écosystèmes surveillés, dashboard,
auto-merge — au présent de l'indicatif.

C'est exactement le défaut qui a valu sa suppression à la banque de mémoire de
`docs/memory-bank/` : un document qui décrit sans contraindre, que rien ne valide, et
qu'on finit par croire. Sur ce dépôt, il a déjà coûté une enquête — celle de
`reparer-fusion-automatique`, partie chercher pourquoi la fusion automatique
n'aboutissait pas, avec un `automerge: true` inerte parmi les suspects.

## What Changes

- Suppression de `renovate.json`, dont plus rien ne lit le contenu.
- Suppression de `docs/modules/ROOT/pages/cicd/renovate.adoc`, la page qui le documentait.
- Retrait de la ligne `renovate.adoc` de l'arbre de la documentation dans
  `docs/modules/ROOT/pages/README.adoc`, qui pointerait vers un fichier inexistant.
- Retrait du linter `renovate@42.76.5` de `.trunk/trunk.yaml` : il ne sert qu'à valider
  `renovate.json`, et n'aura plus rien à analyser.
- **Dependabot devient la seule source déclarée de mises à jour de dépendances.**
  `.github/dependabot.yml` n'est pas touché : il décrivait déjà, seul, ce qui se passe
  réellement.
- Fermeture des pull requests Dependabot devenues sans objet — action côté GitHub, sans
  modification de fichier. Sept sont ouvertes ; deux ne peuvent aboutir :
  - **#84** (`aquasecurity/trivy-action` 0.33.1 → 0.35.0) modifie
    `.github/workflows/security.yml`, fichier supprimé par `retirer-scan-trivy`. Elle
    porte sur un fichier qui n'existe plus.
  - **#88** (`php` 7.4.33 → 8.5.5 dans le `Dockerfile`) ferait tourner Symfony 1.5 et
    Doctrine 1.3 sur PHP 8.5, alors que `src/composer.json` exige `"php": "^7.4"`.
    Fusionner cette pull request casserait la production.

  Les cinq autres — #73, #75, #83, #85, #86 — restent ouvertes : elles visent des fichiers
  qui existent et des montées qui se tiennent. Les instruire ne relève pas de ce
  changement.

### Approche

L'option inverse a été pesée : installer l'app Renovate et retirer Dependabot.
`renovate.json` est mieux réglé que `dependabot.yml` — groupement des mises à jour non
majeures, automerge, dashboard — et Renovate couvre en outre `.devcontainer/`, que
Dependabot ignore ici.

Elle a été écartée pour une raison de séquence, pas de préférence : l'installation d'une
GitHub App relève du propriétaire du dépôt et ne peut pas être portée par une pull
request. Fusionner un changement qui retire Dependabot laisserait le dépôt sans aucune
mise à jour de dépendances tant que l'app n'est pas installée — et c'est précisément ce
genre de contexte requis qui ne remonte jamais, ou de configuration qui ne s'exécute
jamais, que ce dépôt paie déjà deux fois.

Le retrait franc rétablit d'abord la correspondance entre ce que le dépôt déclare et ce
qu'il fait. Adopter Renovate reste possible ensuite, dans l'ordre inverse : installer
l'app, constater qu'elle ouvre des pull requests, puis retirer Dependabot.

### Ce que ce retrait ne décide pas

Ce changement ne se prononce pas sur les mérites de Renovate. Il constate qu'il n'est pas
en service et supprime ce qui prétend le contraire.

### Hors périmètre

- `.github/dependabot.yml` : ni son contenu, ni sa planification hebdomadaire, ni
  l'absence de règle `ignore` sur les montées majeures.
- La fusion automatique, traitée par `reparer-fusion-automatique`.
- La fin de vie de PHP 7.4, qui empêche par ailleurs toute montée majeure de l'image
  Docker.
- Le sort des trois workflows délibérément éteints — `scorecard.yml`, `nightly.yml`,
  `repomix.yml`.
- Le badge mort de `docs/modules/ROOT/pages/index.adoc` qui pointe vers `lint.yml`,
  relevé par `retirer-scan-trivy` et toujours en attente.

## Capacités

Aucune. Ce changement ne touche ni une route, ni un format de sortie, ni un comportement
observable du site : il ne concerne que l'outillage et la documentation. Le changement
pose donc `skip_specs: true`.

### Nouvelles capacités

Aucune.

### Capacités modifiées

Aucune.

## Impact

Fichiers supprimés :

- `renovate.json`
- `docs/modules/ROOT/pages/cicd/renovate.adoc`

Fichiers modifiés :

- `docs/modules/ROOT/pages/README.adoc` — une ligne de l'arbre de documentation
- `.trunk/trunk.yaml` — une ligne de la liste des linters

**Le contrat public n'est pas concerné.** Aucune route, aucun format de sortie, aucun
flux, aucune métadonnée. Le site en production n'est pas touché : `renovate.json` n'est
pas déployé, et la documentation Antora est publiée sur GitHub Pages, séparément.

La chaîne d'intégration continue n'est pas touchée non plus : aucun workflow ne référence
Renovate, et `Trunk Check` — l'un des trois contextes requis du ruleset `main` — perd un
linter sans en voir échouer aucun.
