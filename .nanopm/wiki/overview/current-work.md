---
type: overview
section: plan
generated: 2026-08-29
sources: [objectives.md]
---

# Plan Brief
Generated 2026-08-29 · Project: musiqueapproximative · Sources: objectives.md

## Roadmap — NOW (une seule story à la fois)

**Solder les changes en suspens.** Zéro change dans `openspec/changes/` qui ne soit ni
archivé ni activement travaillé, `openspec validate --specs` vert, avant le 2026-09-15.
Concrètement : archiver `mesurer-la-compatibilite-php-8-2-et-8-3` avec sa story
rétroactive, et reprendre ou retirer `borner-les-representations-machine`, gelé depuis le
2026-08-18.

**NEXT** : trancher le sort de l'API Subsonic · dégeler les stories 2 et 3.
*Le compteur de rythme mensuel a été annulé le 2026-08-30* — avec lui disparaît le seul
déclencheur automatique des anti-goals « recruter » et « ouvrir la contribution ».
_More detail: `.nanopm/wiki/docs/roadmap.md`_

## Period
**T4 2026 (octobre — décembre).** Trois objectifs. Pas de stratégie ni de roadmap écrites
à ce jour — `/pm-strategy` est l'étape suivante.

## The objectives

1. ✅ **Arrêter la destruction des données des contributeurs — clos.** Le dernier KR, le
   compteur de rythme mensuel, est **annulé** (2026-08-30) et non reporté. La preuve Unicode existe : `/encodage` rapporte
   2 morceaux hors cp1252, publiés par un contributeur réel. Détail historique :
   ~~**Déjà livré** — les
   tables sont converties et `encoding: utf8mb4` est posé (`e61b38c`). Il reste deux
   choses : **faire la preuve** (aucun caractère hors cp1252 n'a encore été stocké — 0 sur
   73 174 chaînes de l'extrait du 2026-08-18 ; `/encodage` le rapportera dès qu'un titre
   non-latin sera publié). Le compteur de rythme mensuel, seule autre pièce, est annulé. Voir l'Errata dans `objectives.md`.
2. ✅ **ATTEINT — la production sert en PHP 8.3.33** depuis le 2026-08-29. Détail : La suite est verte sous
   PHP 8.1 depuis six mois, mais la production sert en `php:7.4.33`. Bascule avant le
   31 déc, zéro dépréciation dans le corps des réponses, verdict documenté.
3. **Cesser d'envoyer 3,7 Mo à qui ouvre le catalogue.** `/posts` n'est pas borné.
   Cible ≤ 200 ko par défaut, navigation au-delà, paramètres décrits ET vérifiés au
   contrat OpenAPI.

## Why this and not something else
Le pari central a été testé avant d'écrire ces objectifs : comptage mensuel sur 24 mois
d'un extrait de production — **médiane 30 morceaux et 6 contributeurs actifs par mois,
stable**. La courbe ne descend pas, donc rien ne brûle côté collectif et solder le socle
est légitime. Le trimestre réel se réduit donc à **deux** chantiers (PHP 8.1 en
production, bornage de `/posts`), plus une preuve à constater sur l'Unicode.

## Anti-goals this period
Annoncer ou retirer l'API Subsonic (après les deux bascules) · réparer « retrouver » pour
le contributeur, filtre admin compris — **décision explicite de l'auteur, pas un oubli**
(revisit : T1 2027, ou dès qu'un doublon est publié faute d'avoir retrouvé un morceau) ·
recruter des contributeurs (revisit : médiane sous 4 actifs deux mois de suite) · mesurer
l'audience (jamais par défaut — position assumée).

## The dependency nobody controls from the repo
Le passage en PHP 8.1 exige **un geste manuel du détenteur des accès production** : le
déploiement ne lance aucune migration, Plesk tire `main` et rien d'autre. La conversion
utf8mb4 relevait de la même contrainte — elle est faite, ce qui montre que le chemin
existe.

## Known risks
- Le renouvellement du collectif est mort (2 nouveaux/an depuis 2024, top 3 = 68 % des
  morceaux 2026). **Non traité, et désormais non surveillé** : le compteur qui devait le
  signaler est annulé.
- Les dégâts Unicode déjà faits sont **irrécupérables** — la migration empêche les pertes
  futures, elle ne rend pas les anciennes.
- Écart de comptage 81 / 56 non tranché, et ce document n'est pas la bonne source pour le
  faire : `/encodage` sert `titres_alteres_en_base` en direct depuis la production, avec la
  vraie requête. Le lire plutôt que le supposer.
