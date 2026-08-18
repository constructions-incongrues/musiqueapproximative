## Context

Dix-huit ans de publications, 8 097 morceaux, une base de 3,3 Mo dont 3 pour la table `post`.
La conversion elle-même se compte en secondes : **le risque n'est pas la durée, c'est la
justesse et le retour arrière.**

Deux contraintes de ce dépôt décident de la forme :

1. **Le déploiement n'exécute aucune migration.** Plesk tire `main` à chaque poussée, un
   point c'est tout. Aucun `workflow`, aucune cible de `Makefile` n'appelle `doctrine:migrate`.
2. **Seul le détenteur des accès peut lancer la conversion.** Ce n'est pas une remarque
   d'organisation, c'est ce qui rend l'ordre des gestes contraignant.

## Goals / Non-Goals

**Goals :** que la production cesse de détruire ; que la conversion soit vérifiable avant,
pendant et après ; qu'un retour arrière existe et soit écrit avant d'en avoir besoin.

**Non-Goals :** les 81 morceaux détruits, le garde-fou de saisie, toute autre correction de
schéma rencontrée en chemin.

## Decisions

### Un script SQL, et non une migration Doctrine

Le dépôt porte quatorze migrations Doctrine et la base est en version 14 — la dernière date
de **mai 2010**. Le mécanisme n'a pas tourné depuis quinze ans, et rien ne dit qu'il tourne
encore : il faudrait le vérifier sur la production avant de s'y fier, ce qui revient à
prendre un risque pour en éviter un moindre.

Un script SQL est inspectable ligne à ligne, exécutable par n'importe quel client, et son
effet se lit dans `information_schema`. Le vérifier ne demande pas de faire confiance à un
outil de 2010.

### Deux livraisons, pas une — et c'est la décision la plus importante

Le piège est là, et il faut le voir avant de le rencontrer :

```
  fusion sur main  ──►  Plesk déploie le code  ──►  RIEN ne convertit la base
```

Si `encoding: utf8mb4` part en production **avant** que les tables soient converties,
l'application enverra de l'utf8mb4 vers des colonnes latin1 — c'est-à-dire le mécanisme qui
détruit aujourd'hui, en pire, puisque la conversion portera sur quatre octets au lieu de
trois.

D'où deux livraisons distinctes :

| | contenu | effet en production |
| --- | --- | --- |
| **1** | le script SQL, sa documentation, le contrôle préalable | aucun — rien ne s'exécute |
| — | *l'auteur lance le script* | les tables passent en `utf8mb4` |
| **2** | `encoding: utf8mb4` sur le bloc `all` | la connexion cesse de convertir |

Entre les deux, l'application lit des tables `utf8mb4` sur une connexion `utf8`. MySQL
reconvertit, et `utf8` couvre tout le plan multilingue de base : **rien de l'existant ne se
perd**. Seuls les emoji restent refusés, et bruyamment — c'est un état sûr, dans lequel on
peut rester des jours.

L'inverse n'a pas d'état sûr. C'est pourquoi ce n'est pas une préférence de séquence mais
une contrainte.

### Le contrôle préalable refuse plutôt qu'il n'avertit

Le script commence par vérifier que la base est dans l'état qu'il attend — tables en
`latin1`, connexion en `utf8`, aucune séquence doublement encodée. Si l'un des trois est
faux, **il s'arrête**.

Un avertissement qu'on peut ignorer sur une conversion irréversible n'est pas un garde-fou.
La vérification la plus importante est la troisième : `CONVERT TO CHARACTER SET` réinterprète
les octets comme du latin1 authentique. Si le corpus portait de l'UTF-8 rangé dans des
colonnes latin1 — le fameux double encodage — la conversion produirait du mojibake définitif.
Mesuré à ce jour : **zéro occurrence**. Mais mesuré sur un dump vieux de cinq ans, donc à
refaire sur la production avant de lancer.

### Le retour arrière est un dump, pas une conversion inverse

`CONVERT TO CHARACTER SET latin1` ne rend rien : il re-détruirait ce que la conversion vient
de sauver. Le seul retour arrière réel est un dump pris juste avant, et son existence doit
être vérifiée **avant** la première commande, non après.

## Risks / Trade-offs

- **Le corpus porte du double encodage non détecté** → la conversion produirait du mojibake
  définitif. Atténué par le contrôle préalable, qui s'arrête. C'est le risque qui justifie
  à lui seul le contrôle.
- **Les deux livraisons sont inversées, ou la seconde part avant que le script tourne** →
  état pire que l'actuel. Atténué en ne mettant `encoding` dans aucune des deux livraisons
  avant confirmation que la conversion a eu lieu.
- **`post_index` change de collation** → l'index de recherche est reconstruit après la
  conversion. Le bootstrap de test sait déjà le faire ; en production c'est une commande de
  plus, à ne pas oublier.
- **La conversion échoue à mi-parcours** → `CONVERT TO CHARACTER SET` est atomique par
  table, non sur l'ensemble. Le script converti table par table, dans un ordre écrit, de
  sorte qu'une reprise sache où elle en est.

## Migration Plan

1. Dump de la base, vérifié lisible.
2. Contrôle préalable ; il s'arrête si l'état n'est pas celui attendu.
3. Conversion table par table.
4. Reconstruction de l'index de recherche.
5. Vérification : `information_schema`, puis un aller-retour réel sur un caractère hors
   cp1252.
6. Seulement ensuite, la seconde livraison qui pose `encoding: utf8mb4`.

Retour arrière : restauration du dump de l'étape 1.

## Open Questions

**Qui lance, et quand.** La réponse technique est écrite ci-dessus ; la réponse
opérationnelle appartient à l'auteur, seul détenteur des accès. Ce change ne peut pas la
prendre à sa place, et il ne doit pas prétendre le contraire.
