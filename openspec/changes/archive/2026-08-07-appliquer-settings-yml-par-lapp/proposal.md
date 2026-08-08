## Why

> **Issue de ce changement : renoncement.** L'app n'a jamais créé la protection de
> branche, malgré trois alignements successifs sur sa documentation. La section `branches`
> a été retirée du fichier, et l'app désinstallée.
>
> **La raison n'a été comprise qu'après.** GitHub offre deux systèmes de protection : les
> règles *classiques*, seules pilotables par l'app, et les *rulesets*, qui ont leur propre
> API et lui échappent entièrement. `main` est gouvernée par un ruleset. La section
> `branches` visait donc, depuis l'origine, un mécanisme sans effet sur cette branche.
>
> Cette confusion a coûté toute l'enquête. Elle a fait conclure tour à tour que l'app était
> inerte, qu'elle détruisait la protection, puis que `main` n'était plus protégée du tout —
> trois conclusions fausses, tirées d'observations faites sur le mauvais écran. Ce qu'on
> voyait apparaître et disparaître sous Réglages → Branches était la règle classique que
> l'app n'arrivait pas à créer ; le ruleset, lui, n'a jamais bougé.
>
> Ce qui suit décrit l'intention initiale, conservée telle quelle pour mémoire.


`.github/settings.yml` décrit la configuration du dépôt — options de fusion, seize
libellés, protection de la branche `main` — mais rien ne l'applique : l'app GitHub
Settings n'est pas installée. Le fichier est purement décoratif, et les corrections de
protection fusionnées par la pull request #92 n'ont eu aucun effet.

La décision est prise de l'installer, pour que ce fichier devienne la source de vérité :
la configuration du dépôt sera alors versionnée, relue en pull request, et cessera de
diverger en silence.

Cette décision inverse la charge du risque. Aujourd'hui le fichier ment sans conséquence ;
demain il sera appliqué. **Toute erreur qu'il contient deviendra la configuration
réelle.** Il doit donc être exact avant l'installation, pas après.

## What Changes

- `Trivy Scan` réintègre les contextes de vérification requis. Il en avait été retiré
  parce que son workflow était désactivé et ne produisait aucun contexte ; le workflow est
  réactivé, le job renommé, et le check a été constaté vert sur la pull request #96.
- Les commentaires qui décrivaient un état transitoire — workflow désactivé, contexte à
  réintroduire — sont réécrits pour décrire la configuration voulue.
- `enforce_admins` passe de `true` à `false`. **C'est le seul arbitrage de ce changement**,
  détaillé ci-dessous.
- Aucune autre valeur ne bouge : options de fusion, libellés, historique linéaire,
  interdiction des poussées forcées et des suppressions.

### L'arbitrage sur `enforce_admins`

Le fichier déclare `enforce_admins: true`, qui interdit à quiconque, administrateurs
compris, de fusionner une branche ne satisfaisant pas la protection. Tant que rien
n'appliquait le fichier, cette ligne était sans effet — c'est d'ailleurs ce qui a permis
de fusionner sept pull requests à la main pendant que la protection était cassée.

L'appliquer telle quelle serait imprudent sur ce dépôt. La séquence qui précède a montré
qu'un contexte requis peut cesser de remonter sans prévenir : un job renommé, un workflow
désactivé pour inactivité, une action dont l'installation casse en amont. Chacun de ces
incidents a rendu toute pull request non fusionnable. Avec `enforce_admins: true` et un
seul mainteneur, il n'existerait alors **aucune issue** : ni approbation possible, ni
contournement administrateur. Le dépôt serait verrouillé jusqu'à modification manuelle de
la protection.

Le passer à `false` conserve la protection comme règle de fonctionnement normal, tout en
gardant une sortie de secours pour son mainteneur. C'est la configuration qui correspond à
la réalité observée, puisque c'est ainsi que le dépôt a fonctionné jusqu'ici.

### Hors périmètre

- **L'installation de l'app elle-même**, qui se fait depuis `github.com/apps/settings` et
  demande des droits d'administration sur le dépôt. Elle incombe au mainteneur, et une
  tâche la trace.
- La réintroduction du contexte `Trivy Scan` dans la protection **réelle**, qui n'a de
  sens qu'une fois l'app installée : c'est elle qui appliquera le fichier.
- Le sort de `scorecard.yml`, `nightly.yml` et `repomix.yml`, délibérément laissés éteints,
  dont les fichiers subsistent sans jamais s'exécuter.
- Les seize libellés déclarés, conservés tels quels — leur revue est un autre sujet.

## Capacités

### Nouvelles capacités

Aucune.

### Capacités modifiées

Aucune. Le changement porte sur la configuration du dépôt, jamais sur le comportement du
site. Aucune exigence du corpus ne bouge, d'où `skip_specs: true`.

## Impact

- `.github/settings.yml` : contextes requis, `enforce_admins`, commentaires.
- Contrat public **inchangé**. Aucun fichier de `src/` n'est touché.

**À savoir avant d'installer** : l'app applique **l'intégralité** du fichier, pas
seulement la protection de branche. Les seize libellés y sont déclarés, ainsi que les
options de fusion et les métadonnées du dépôt — description, page d'accueil, sujets. Tout
ce qui diffère aujourd'hui de ce que déclare le fichier sera aligné sur lui. Une tâche
prévoit de comparer les trois sections aux réglages réels avant de déclencher
l'installation.
