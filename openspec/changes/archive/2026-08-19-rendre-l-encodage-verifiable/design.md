# Conception

## Contexte

Le paramètre à rendre observable est `encoding: utf8mb4` dans `databases.yml`. Il ne produit
aucun effet visible tant qu'aucun caractère hors cp1252 n'est saisi — et la story 20 a établi
qu'en dix-huit ans, **aucun ne l'a été**. Le défaut est donc invisible par construction
jusqu'au jour où il détruit quelque chose.

## Ce qu'on interroge, et pourquoi c'est suffisant

Doctrine 1 traduit `encoding` en un `SET NAMES` à l'ouverture de la connexion, via
`sfDoctrineConnectionListener`. Le résultat est lisible dans les variables de session de
MySQL :

```
  character_set_client       utf8mb4
  character_set_connection   utf8mb4
  character_set_results      utf8mb4
  collation_connection       utf8mb4_general_ci
```

Les tables ayant déjà été converties le 2026-08-18, **le jeu de caractères de la connexion
est la seule variable restante** entre un titre saisi et un titre stocké. L'interroger suffit
à répondre à la question posée ; il n'est pas nécessaire — ni prudent — d'écrire en base pour
la même réponse.

Une lecture, aucune écriture. C'est ce qui permet à cette vérification d'être exposée
publiquement sans qu'elle devienne une surface d'attaque.

## Ce que la réponse doit porter

**Un verdict et la valeur constatée.** Un verdict seul se lit à 8 h du matin et n'apprend
rien : il faut savoir si la connexion est retombée en `latin1`, en `utf8` — celui qui tient
sur trois octets et rejette les emoji — ou si la base est simplement injoignable.

**Trois états, pas deux.** « Conforme », « non conforme », et « je n'ai pas pu savoir ». Une
base injoignable n'est pas un encodage fautif, et les confondre produirait exactement le
bruit qui fait désactiver une alerte — c'est le raisonnement déjà retenu pour le contrôle du
contrat en production.

## Où la vérification est interrogée

Le rendez-vous nocturne `contrat-production.yml` existe déjà, tourne les jours ouvrés à 8 h,
et sait distinguer une production injoignable d'un contrat faux. Il gagne un contrôle, plutôt
qu'un second rendez-vous.

C'est l'inverse du choix fait la veille pour ce même contrôle — il avait été **sorti** de
`nightly.yml` parce que ce workflow échouait en continu et qu'une alerte logée dans un
rendez-vous rouge est éteinte. `contrat-production.yml`, lui, est vert et ne porte qu'une
chose. Y ajouter un contrôle du même horaire et de la même nature ne le rend pas illisible.

## Ce que cette conception écarte

**Une tâche symfony seule.** Le projet n'a aucune étape de mise en ligne où l'accrocher :
Plesk tire `main` à chaque poussée. Une commande sans moment ne s'exécute pas, et l'incident
du 2026-08-18 est arrivé à quelqu'un qui suivait un conseil pressé sans lire la
documentation.

**Écrire un caractère de quatre octets pour vérifier qu'il survit.** Ce serait la preuve la
plus directe, et elle demande d'écrire en production depuis une route publique. La suite de
tests fait déjà cette preuve sur une base de test ; ce qui manquait en ligne est le paramètre
de connexion.

**Exposer l'ensemble des variables de la connexion.** Ce serait publier une configuration
pour répondre à une question qui tient en un mot. On expose ce qui répond, pas ce qui est
disponible.
