## Why

Le scan de vulnérabilités échoue à chaque exécution. Le workflow se déclenche bien, le
build Docker aboutit, mais Trivy ne s'installe pas :

```
installing Trivy binary
aquasecurity/trivy info checking GitHub for tag 'v0.65.0'
aquasecurity/trivy info found version: 0.65.0 for v0.65.0/Linux/64bit
##[error]Process completed with exit code 1.
```

`aquasecurity/setup-trivy`, appelée par l'action, récupère `contrib/install.sh` en
faisant un checkout de `aquasecurity/trivy` **sur `main` HEAD**, puis l'exécute. C'est une
dépendance mouvante logée à l'intérieur d'une action pourtant épinglée par SHA : le script
identifie la version demandée, puis échoue à l'installer. L'action est figée, son script
d'installation ne l'est pas.

**Le dépôt n'a donc aucun scan de vulnérabilités effectif**, alors même que le workflow
paraît actif et se déclenche. C'est une panne plus insidieuse que la désactivation qui l'a
précédée : là où un workflow éteint ne produit rien, celui-ci produit un échec qu'on peut
prendre pour un simple problème d'intégration continue.

L'épinglage actuel, `b6643a29`, correspond à la version **v0.33.1**. La dernière version
publiée est **v0.36.0**.

### Pourquoi l'épinglage a vieilli

`renovate.json` déclare `automerge: true` pour les mises à jour mineures et de digest.
Renovate aurait donc dû proposer et fusionner cette montée de version tout seul.

Mais la fusion automatique ne fonctionnait pas : la protection de branche exigeait deux
contextes de vérification inexistants et une approbation impossible à obtenir sur un dépôt
à un seul mainteneur. Aucune pull request ne pouvait devenir fusionnable — **y compris
celles de Renovate**. L'épinglage a vieilli sans que rien ne le signale, jusqu'à devenir
incompatible avec le script en amont.

Cet enchaînement est cohérent avec tout ce qui a été observé, mais il n'est pas établi :
l'historique des pull requests de Renovate n'a pas été inspecté.

## What Changes

- `aquasecurity/trivy-action` passe de `v0.33.1` à `v0.36.0`, épinglé sur le commit
  `ed142fd0`, conformément à la convention du dépôt qui épingle les actions par SHA.
- Un commentaire en fin de ligne indique la version correspondante, comme le fait déjà
  `actions/checkout` dans les autres workflows.
- Aucun paramètre de l'action ne change : `image-ref`, `format`, `template`, `output` et
  `severity` sont conservés tels quels.

### Ce que ce changement ne garantit pas

Monter la version est la correction naturelle, et la seule disponible sans renoncer à
l'action. Elle n'est pas certaine pour autant : la panne vient d'un script récupéré en
amont, et rien ne dit que la version v0.36.0 s'en accommode mieux. Seule l'exécution en
intégration continue tranchera.

Le changement est donc délibérément minimal — une seule ligne — pour que le lien de cause
à effet soit lisible. Si le scan repasse au vert, c'est la version. S'il échoue autrement,
le nouveau message d'erreur désignera la suite.

Deux précautions à connaître sur cette montée de version : elle traverse **v0.35.0**, qui
a migré ses tags à la suite d'un incident de chaîne d'approvisionnement, et **v0.36.0**
embarque Trivy v0.70.0 au lieu de v0.65.0 — un moteur plus récent peut remonter des
vulnérabilités que l'ancien ignorait. Un premier scan plus sévère que prévu serait un
résultat, pas une régression.

### Hors périmètre

- Le remplacement de `template: '@/contrib/sarif.tpl'` par le format `sarif` natif de
  Trivy, qui supprimerait la dépendance au gabarit récupéré en amont. C'est une piste
  sérieuse de robustesse, mais elle ne corrige pas la panne actuelle, qui se produit avant
  que le format n'entre en jeu. À traiter séparément si le bump ne suffit pas.
- La question de savoir pourquoi Renovate n'a jamais proposé cette mise à jour, qui
  demande d'inspecter son tableau de bord et son historique.
- La coexistence de Renovate et de Dependabot, tous deux configurés sur ce dépôt.
- Les tâches encore ouvertes de `reparer-fusion-automatique` et de
  `declencher-manuellement-scan-securite`.

## Capacités

### Nouvelles capacités

Aucune.

### Capacités modifiées

Aucune. Le changement porte sur l'intégration continue, jamais sur le comportement du
site. Aucune exigence du corpus ne bouge, d'où `skip_specs: true`.

## Impact

- `.github/workflows/security.yml` : version de l'action de scan.
- Contrat public **inchangé**. Aucun fichier de `src/` n'est touché.
- Aucune dépendance applicative ajoutée, aucune migration.
