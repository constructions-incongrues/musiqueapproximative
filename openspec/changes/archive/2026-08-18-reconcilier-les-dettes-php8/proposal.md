## Why

Le change `assainir-le-rendu-markdown` a laissé une dette écrite noir sur blanc : « un second
verrou PHP 8 subsiste, `getid3` porte la même syntaxe supprimée ». **C'est faux.**

L'occurrence est à la ligne 355 de `getid3.lib.php`, et elle est **dans un commentaire** :

```php
//$intvalue = $intvalue | (ord($byteword{$i}) & 0x7F) << ...
```

Je l'avais relevée au `grep` sans regarder si c'était du code vivant. PHP n'analyse pas les
commentaires ; ce verrou n'a jamais existé.

La mesure reprise **par analyse de jetons** — qui ignore commentaires et chaînes, ce qu'un
`grep` ne sait pas faire — donne autre chose :

| | accès de chaîne en accolades | constructeurs à la PHP 4 |
| --- | --- | --- |
| `src/apps`, `src/plugins`, `src/config`, `src/test` | 0 | 0 |
| `src/lib` | 0 | **3**, tous dans `lib/vendor/File/` |
| `src/vendor` (Composer) | 0 | 3 |

Les trois de `src/lib` sont dans une bibliothèque PEAR `File_XSPF` qui, vérification faite,
**n'est utilisée nulle part**. La seule mention dans tout le dépôt est le commentaire de
`_xspfPlaylist.xspf.php` qui explique qu'on l'a retirée : « absente de l'image et dont le
`require` échouait fatalement ». Le XSPF est produit par des gabarits.

Pire : `XSPF.php` **ne compile pas** sur le PHP 7.4 du projet. Il emploie `return throw new
…`, syntaxe valide seulement à partir de PHP 8.0. Un fichier mort, dans un répertoire
pourtant placé sur l'`include_path` PEAR, qui planterait s'il était chargé.

## What Changes

- Suppression de `src/lib/vendor/File/` — neuf fichiers, 128 Kio, présents depuis le commit
  « Legacy ».
- Correction de l'évaluation du verrou PHP 8 dans le plan de release : `getid3` n'en est
  pas un, et les trois constructeurs qu'on croyait bloquants partent avec le code mort.
- Consignation des dettes que du travail ultérieur a **fermées sans que les archives le
  sachent**.

Le contrat public n'est pas concerné : le XSPF continue d'être servi par ses gabarits.

## Hors périmètre

- Les trois constructeurs de `src/vendor`, tirés par Composer et gitignorés. Les corriger
  sur place serait effacé au prochain `composer install` ; ils relèvent d'une montée de
  dépendance, le jour de la montée en PHP 8.
- **Déclarer PHP 8 atteignable.** Ce change retire un obstacle et en dissipe un imaginaire ;
  il ne conduit pas l'audit de compatibilité, qui porte sur bien plus que deux syntaxes.
- Réécrire les `tasks.md` archivés. Ce sont des relevés de ce qui a été fait à une date ;
  la réconciliation va au plan, qui est le document vivant.
