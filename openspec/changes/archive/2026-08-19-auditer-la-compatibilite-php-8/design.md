## Context

La story 28 demandait un audit et supposait l'ignorance. La mesure, faite avant d'écrire
ce document, a renversé la supposition — et la façon dont elle l'a fait est la moitié
intéressante du résultat.

Trois passes, dans cet ordre :

1. **Syntaxe, sous PHP 8.4.** 289 fichiers du projet, **zéro refus** dans le code
   exécuté. Les 14 fichiers refusés — 7 dans `src/plugins`, 7 dans `src/vendor` — sont
   tous des gabarits de générateur sous `skeleton/`, porteurs de marqueurs `##CLASS##`,
   jamais évalués comme PHP.
2. **Suppressions que `php -l` ne peut pas voir.** `create_function()` subsiste à deux
   endroits : une tâche d'empaquetage de plugin jamais lancée par le site, et
   PHP-Markdown ligne 1645 — cette dernière derrière un `if (function_exists(...)) return;`
   sur `mb_strlen`, présent dans l'image. Branche morte. `split()` n'est que du
   JavaScript dans une chaîne PHP de la barre de débogage. `get_magic_quotes_gpc()` est
   dans `getid3`, dont le code du projet ne fait **aucune** référence.
3. **Exécution.** 269 tests unitaires verts sur 269 sous PHP 8.1. Suite fonctionnelle
   `admin` verte. Suite `frontend` : 64 échecs sur 408.

Le témoin sous PHP 7.4, lancé le même jour sur le même code, est entièrement vert. La
casse est donc réelle, et non préexistante.

**Deux fausses pistes ont été écartées avant de conclure**, et il faut les consigner
parce qu'elles auraient chacune produit un verdict faux :

- Le premier lancement a échoué sur un cache d'autoload portant les chemins absolus d'un
  autre montage. Ce n'était pas PHP 8 : c'était deux conteneurs partageant `src/cache/`.
  La mesure a été refaite avec un volume anonyme sur `cache/` et `log/`, et donne
  exactement les mêmes 64 échecs, les mêmes 6 scripts, les mêmes numéros de test.
- Les jeux d'extensions ont été comparés terme à terme entre le conteneur de référence
  et le conteneur jetable. Ils sont identiques à `pdo_mysql` près, installé dans la passe.
  Sans cette comparaison, un `mbstring` manquant aurait produit la même ampleur de casse
  et fait accuser PHP 8 à tort.

## Goals / Non-Goals

**Goals:**

- Retirer ce qui bloque la montée, et le nommer précisément
- Rendre le verdict reproductible plutôt que daté
- Écrire ce que l'audit ne prouve pas

**Non-Goals:**

- **Faire la migration.** Ni la version du conteneur, ni la contrainte de
  `composer.json` ne changent ici. Ce change établit que la porte s'ouvre ; la franchir
  se décide séparément, avec une production à surveiller.
- Traiter les avertissements de dépréciation de PHP 8.2 (propriétés dynamiques), qui
  n'empêchent rien aujourd'hui.

## Decisions

### Corriger dans `getDisplayName()`, et non dans la requête

Les 64 échecs ont une seule cause : `sfGuardUser::getDisplayName()` lit
`$this->UserProfile->display_name` alors que `UserProfile` vaut `null`. Les 210 comptes
de la base sont sans profil : la branche est prise à chaque rendu de contributeur.

L'origine du `null` n'est pas celle qu'on supposerait. Vérifiée par comparaison directe
des deux requêtes :

```
requete AVEC leftJoin UserProfile  : UserProfile = NULL
requete SANS leftJoin UserProfile  : UserProfile = objet UserProfile
```

C'est donc le `leftJoin('u.UserProfile pr')` **ajouté la veille par la story 34** pour
supprimer le N+1 qui a rendu la relation nulle. Auparavant, le chargement paresseux de
Doctrine 1 fabriquait un objet vide. PHP 7.4 masque intégralement le changement : lire
une propriété sur `null` n'y est qu'une notice, et la retombée sur `username` donne le
même affichage qu'un `display_name` vide. **Aucun test ne pouvait le voir sur
l'interpréteur du projet.**

Trois emplacements possibles, et pourquoi celui-ci :

- **Défaire la jointure** rendrait les 8 271 requêtes et les 7,17 s que la story 34 vient
  de supprimer. Écarté sans hésitation.
- **Faire hydrater un objet vide** par la jointure lutterait contre Doctrine 1 pour
  restaurer un comportement qui n'était lui-même qu'un artefact du chargement paresseux.
  L'objet vide n'a jamais été un choix ; c'était une conséquence.
- **Garder la lecture** est une ligne, à l'endroit exact où la question se pose, et
  fonctionne sur les deux formes d'absence. Retenu.

### Une matrice bloquante, pas une passe consultative

La passe PHP 8 fait échouer l'intégration continue au même titre que la 7.4. Une passe
tolérée aurait viré au rouge permanent, puis à l'ignorance — c'est le mode de défaillance
habituel des vérifications facultatives, et il vaut mieux ne pas l'ajouter que l'ajouter
sans dents.

Le coût est réel : chaque exécution est doublée. Il est accepté parce que sans lui ce
document n'est qu'un chiffre de plus destiné à vieillir.

## Risks / Trade-offs

**Ce que le verdict ne prouve pas**, et qui doit être écrit dans la documentation :

- La suite ne couvre pas tout le code exécuté. Un chemin sans test peut porter le même
  défaut sans que rien ne le signale.
- Les ruptures **silencieuses** de PHP 8 ne lèvent rien : la comparaison entre chaîne et
  nombre a changé de sémantique en 8.0 et se manifeste par un résultat faux, pas par une
  erreur. Aucune suite verte ne les exclut.
- La mesure porte sur PHP 8.1. Elle ne dit rien de 8.3 ni de 8.4, où les dépréciations de
  8.2 sur les propriétés dynamiques — que Doctrine 1 emploie massivement — deviendront un
  sujet.

**Le risque de méthode, déjà réalisé quatre fois dans cette release** : un packet écrit à
partir de ce qui est lisible plutôt que de ce qui est exécutable. Ce change en est
lui-même la démonstration inversée — la story annonçait un audit d'inventaire, et
l'exécution a fourni un verdict plus un défaut de la veille. Le contrôle de la story 27
ne peut pas rattraper cela : il vérifie qu'une story livrée cite un change, jamais que
ses chiffres sont encore vrais.
