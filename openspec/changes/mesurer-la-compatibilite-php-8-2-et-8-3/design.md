## Context

La matrice de `tests.yml` porte `php: ["7.4", "8.1"]`. Le pas d'installation distingue
déjà 7.4 de tout le reste :

```
if [ "$php" = "7.4" ]; then composer install
else                        composer install --ignore-platform-req=php
fi
```

Ajouter des versions à la matrice ne demande donc **aucun changement au pas
d'installation** : elles tombent dans la branche `else`, exactement comme 8.1.

Ce qui n'est pas mécanique, et justifie cet artefact : `doctrine:insert-sql` échoue sous
PHP 8 et le contournement actuel vit dans le fichier de CI, pas dans le code ; et rien ne
dit ce qu'il faut faire si 8.2 et 8.3 ne rendent pas le même verdict.

Deux relevés du 2026-08-29 sur `panel.pastis-hosting.net` fondent tout ce change :
interpréteurs disponibles `7.4 / 8.2 / 8.3` (aucun 8.1, et `plesk-php81` n'est pas dans les
paquets installables), site servi par `plesk-php74-fpm-dedicated`.

## Goals / Non-Goals

**Goals**

- Savoir ce que 8.2 et 8.3 font à ce code, avec le même statut bloquant que les branches
  existantes.
- Rendre la page de verdict utilisable pour décider : elle doit dire ce qui est servable,
  pas seulement ce qui a été mesuré.
- Faire que `doctrine:insert-sql` cesse de mentir sur son code de sortie.

**Non-Goals**

- Basculer la production.
- Réparer ce que 8.2 ou 8.3 révéleront.
- Toucher au code applicatif servi aux visiteurs.

## Decisions

### D1 — La matrice passe à quatre versions, pas à deux

Retenu : `php: ["7.4", "8.2", "8.3"]` **plus** 8.1 conservée.

*Pourquoi garder 8.1 alors qu'elle est inatteignable en production.* Elle est le point de
comparaison qui rend les échecs lisibles. Si 8.2 casse et 8.1 passe, le défaut est daté :
il vient de 8.2, et très probablement des propriétés dynamiques. Sans 8.1, on ne saurait
que « PHP 8 casse », ce qui est exactement le genre de verdict que la capacité interdit —
« l'échec SHALL nommer le fichier et la ligne, faute de quoi il désigne PHP 8 et non le
défaut ». Le coût est une branche de CI de quarante secondes.

*Alternative écartée — remplacer 8.1 par 8.2.* Moins cher, et on perd le témoin. Le projet
a déjà payé une fois pour avoir mesuré sans témoin : le `xspf`, qui ne lit pas le
contributeur, est ce qui a confirmé le diagnostic du N+1 en ne bougeant pas.

### D2 — `doctrine:insert-sql` : une tâche du projet qui enveloppe celle de Doctrine

Le défaut est dans une dépendance vendue : `Doctrine_Export::exportClasses()` ouvre une
transaction, émet le DDL — que MySQL valide implicitement — puis appelle `commit()` sur une
transaction déjà refermée. PHP 7.4 laissait PDO muet ; PHP 8 lève.

Retenu : une tâche du projet sous `src/lib/task/`, qui exécute le travail et traite cette
`PDOException` précise comme le non-événement qu'elle est, en vérifiant que les tables
existent avant de rendre 0.

*Pourquoi pas corriger Doctrine.* `src/vendor` est ignoré par git et réinstallé à chaque
`composer install` : un correctif y serait perdu au premier déploiement. C'est aussi la
règle du dépôt — on ne modifie pas le code de quelqu'un d'autre.

*Pourquoi pas laisser le contournement dans la CI.* Il y est aujourd'hui, et il fonctionne.
Mais `make test-init` lance la même tâche et n'a pas ce garde : un développeur voit un
échec là où la CI voit un succès. Le contournement dans le fichier de CI répare un poste de
travail sur deux.

*Ce qui n'est PAS retenu : avaler l'exception.* Rendre 0 sans vérifier laisserait passer un
schéma réellement incomplet — c'est déjà l'argument écrit dans `tests.yml`, et il tient. La
vérification des tables est ce qui distingue une tolérance d'un aveuglement.

### D3 — L'élargissement de la contrainte suit la mesure, version par version

`"php": "^7.4"` ne s'élargit qu'aux versions que la matrice a rendues vertes, et seulement
après retrait du `--ignore-platform-req` les concernant.

Trois issues, toutes prévues :

| mesure | contrainte | drapeau |
| --- | --- | --- |
| 8.2 et 8.3 vertes | `^7.4 \|\| ^8.2` | retiré pour 8.2 et 8.3 |
| 8.2 verte, 8.3 rouge | `^7.4 \|\| ~8.2.0` | retiré pour 8.2 seulement |
| l'une ou l'autre rouge sans remède | inchangée | conservé |

Dans le troisième cas le change livre quand même : la mesure et le verdict écrit sont son
produit principal, l'élargissement n'en est qu'une conséquence possible.

### D4 — La page de verdict porte l'inventaire de l'hébergement, avec sa date

Un tableau « ce que l'hébergement propose / ce qui est servi / relevé le », en tête du
verdict. Sans la date il se lira comme permanent, alors qu'il dépend de l'hébergeur et
changera sans prévenir — c'est le mode de vieillissement que cette page a déjà connu quatre
fois pour ses chiffres de tests.

## Risks / Trade-offs

**R1 — 8.2 casse massivement sur les propriétés dynamiques de Doctrine 1.** C'est le risque
que l'audit annonce, et il est le plus probable des trois issues de D3.
→ *Mitigation* : aucune, et ce n'est pas le rôle de ce change. Le savoir est le livrable.
Si c'est le cas, la conclusion à écrire est que la montée exige de traiter Doctrine 1
d'abord — ce qui est une information de planification bien plus utile qu'un verdict vague.

**R2 — La CI s'allonge de deux branches.** Quatre exécutions complètes de la suite.
→ *Mitigation* : `fail-fast: false` est déjà posé, les branches sont parallèles, le cache
composer est déjà indexé par version (`...-php${{ matrix.php }}-composer-...`). Le coût est
du temps machine, pas du temps d'attente.

**R3 — La tâche enveloppante masque un jour une vraie erreur.** Si elle attrape trop large,
un échec réel de création de schéma passerait pour le défaut connu.
→ *Mitigation* : ne traiter que cette exception-là — message « There is no active
transaction » — et vérifier les tables avant de rendre 0. Le garde a été vérifié par
l'échec pour la matrice ; le même geste vaut ici.

## Migration Plan

Aucune. Rien de ce change n'atteint la production : CI, documentation, une tâche de
maintenance, et une contrainte de déclaration qui ne s'élargit qu'après preuve.

Retour en arrière : retirer les versions de la matrice. La tâche enveloppante peut rester,
elle est utile sous 7.4 aussi — elle y est simplement sans effet.

## Open Questions

1. **Si 8.2 est verte, faut-il basculer sur 8.2 ou attendre 8.3 ?** 8.2 est plus proche du
   mesuré, 8.3 dure plus longtemps. À trancher avec le chiffre, pas avant.
2. **Que fait-on de 8.1 dans la matrice une fois la bascule faite ?** Elle deviendra un
   témoin d'une version que personne ne sert. À reposer à ce moment-là.
