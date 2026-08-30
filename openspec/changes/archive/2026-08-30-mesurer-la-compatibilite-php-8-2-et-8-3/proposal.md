## Why

L'objectif du trimestre était « faire tourner le site sur un interpréteur encore soutenu »,
cible PHP 8.1 — la version que l'audit du 2026-08-19 a mesurée et déclarée atteignable.

**Cette cible n'existe pas.** Relevé sur le panel le 2026-08-29 : Plesk Obsidian 18.0.65 /
Ubuntu 24.04 n'offre que trois interpréteurs, et 8.1 n'en fait pas partie.

```
/opt/plesk/php/7.4/   /opt/plesk/php/8.2/   /opt/plesk/php/8.3/
apt-cache search ^plesk-php  ->  plesk-php74, plesk-php82, plesk-php83
```

Le site tourne sur `plesk-php74-fpm-dedicated`. Les deux seules montées possibles sont donc
**précisément celles que l'audit a exclues de son verdict** :

> « La mesure porte sur PHP 8.1. Elle ne dit rien de 8.3 ni de 8.4, où les dépréciations de
> 8.2 sur les propriétés dynamiques — que Doctrine 1 emploie massivement — deviendront un
> sujet à part entière. »

La matrice d'intégration continue exécute 7.4 et 8.1. Personne n'a jamais exécuté ce code
sous 8.2 ni sous 8.3. Basculer la production sur l'une des deux serait déclarer sans avoir
vérifié — l'inverse exact du principe que la capacité `compatibilite-php-8` établit : une
version est supportée quand la suite y passe, pas quand une déclaration l'annonce.

Ce change ne migre rien. Il va chercher le chiffre qui manque.

## What Changes

- La matrice de `tests.yml` gagne **8.2 et 8.3**, au même statut bloquant que les deux
  branches existantes. `fail-fast` reste désactivé pour que chaque version rende son
  verdict.
- La page de verdict enregistre que **8.1 est hors d'atteinte en production**, avec le
  relevé qui l'établit, et ce que 8.2 et 8.3 donnent réellement.
- `doctrine:insert-sql`, que l'audit nomme « le premier élément connu à corriger avant une
  migration réelle », est traité : la tâche rend un code 1 sous PHP 8 alors qu'elle a créé
  toutes les tables, parce que Doctrine valide une transaction que le DDL de MySQL a déjà
  refermée.
- **Conditionnellement, et seulement si la mesure est verte** : le drapeau
  `--ignore-platform-req=php` disparaît et la contrainte `"php": "^7.4"` de
  `src/composer.json` s'élargit aux versions que la matrice a prouvées — dans cet ordre,
  comme la documentation le prescrit. Si la mesure est rouge, ces deux gestes ne sont pas
  faits et le change dit pourquoi.

**Le contrat public n'est pas concerné.** Aucune route, aucun format, aucun comportement
servi au visiteur ne change. Rien n'est touché en production par ce change.

### Pourquoi l'élargissement de la contrainte est conditionnel

Élargir `"php"` avant de savoir, ce serait déclarer un support non vérifié — la faute même
que la capacité a été écrite pour empêcher, et qui avait déjà été commise : toute la chaîne
annonçait PHP 8 alors que 64 tests sur 408 y échouaient. La déclaration suit la preuve.

## Capabilities

### New Capabilities

Aucune.

### Modified Capabilities

- `compatibilite-php-8` : la liste des versions que l'intégration continue doit exercer
  cesse d'être implicite et se lie à ce que la production peut réellement servir. Le
  verdict publié doit porter les versions **atteignables**, et non seulement celles qu'on a
  eu envie de mesurer — c'est ce qui a manqué ici.

## Impact

- `.github/workflows/tests.yml` — la matrice. Le pas d'installation gère déjà toute version
  autre que 7.4 par son `if`, il n'a pas à changer.
- `docs/modules/ROOT/pages/developpement/compatibilite-php-8.adoc` — le verdict, sa date,
  les interpréteurs, et le fait neuf que 8.1 est inatteignable.
- `src/composer.json` — la contrainte `"php"`, **conditionnellement**.
- La tâche `doctrine:insert-sql` et le contournement que la CI porte aujourd'hui.
- `.nanopm/wiki/docs/objectives.md` — l'objectif 2 du T4 nommait 8.1 ; sa cible est fausse.

## Hors périmètre

- **Basculer la production.** Ce change mesure ; la bascule se décidera ensuite, avec le
  chiffre en main. C'est déjà la position de la page de verdict : « Cette page établit que
  la porte s'ouvre. La franchir se décide séparément, avec une production à surveiller. »
- **Corriger ce que la mesure trouvera.** Si 8.2 ou 8.3 révèlent des défauts, chacun est un
  travail à part. Ce change les fait apparaître et les consigne, il ne les répare pas —
  sauf `doctrine:insert-sql`, déjà connu et déjà nommé comme prérequis.
- **PHP 8.4 et au-delà.** Non disponible sur ce panel ; le mesurer serait mesurer ce qu'on
  ne peut pas servir.
- **Quitter Symfony 1 ou Doctrine 1.** Les propriétés dynamiques sont le risque de fond de
  cette montée ; ce change dit s'il se matérialise, il ne le traite pas.
