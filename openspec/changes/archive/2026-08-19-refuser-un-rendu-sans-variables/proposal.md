## Why

Le 2026-08-18, `make configure` a mis **toutes les pages du site en 500**. La cible a
réécrit `databases.yml` avec `${DATABASE_HOST}` au lieu de l'hôte, la connexion a échoué, et
chaque page est tombée.

Le piège est documenté depuis, en encadré. **Documenter n'a pas suffi** : celui qui suit un
conseil pressé ne lit pas la documentation — c'est exactement ce qui s'est passé, et le
conseil venait de l'assistant.

## Ce que la reproduction a corrigé dans le diagnostic

Le plan attribuait l'incident à l'absence de `src/.env`. **C'est faux, et l'inverse est
vrai.** Reproduit dans un répertoire jetable, sous Linux :

| état de `.env` | `make configure` | résultat |
| --- | --- | --- |
| **absent** | **refuse** — `No rule to make target '.env'` | rien n'est écrit |
| **vide** | réussit | `${DATABASE_HOST}` écrit tel quel — **c'est l'incident** |
| correct | réussit | rendu correct |

Un `.env` absent est sans danger : `src/Makefile` l'inclut en ligne 1, et GNU make s'arrête.
C'est un `.env` **vide, ou sans variable exploitable**, qui détruit — la liste blanche est
alors vide, `envsubst` ne substitue rien, et le gabarit est recopié verbatim.

Le piège n'est pas non plus réservé au serveur : **`src/.env` fait 0 octet sur un poste de
développement**, où c'est un montage Docker qui fournit le vrai fichier au conteneur. Un
`make configure` lancé depuis l'hôte détruit donc la configuration locale de la même façon.

## What Changes

- `configure` **vérifie d'abord** que la liste blanche de variables n'est pas vide, et
  refuse d'écrire quoi que ce soit sinon, avec un message qui dit ce qui manque et où.
- La vérification a lieu **une fois, avant la boucle** : la cible écrit aujourd'hui fichier
  par fichier, et un échec en cours de route laisserait une configuration à moitié détruite.
- La documentation est corrigée : elle décrit aujourd'hui le mauvais cas.

Le contrat public n'est pas concerné.

## Hors périmètre

- **Rendre `make configure` utilisable sur le serveur.** Cela demande de décider où vit le
  `.env` de production, ce qui est une question de déploiement plus vaste. Une commande qui
  détruit en silence doit d'abord cesser de détruire ; la rendre utilisable ailleurs vient
  après.
- **Le `Makefile` racine**, qui inclut `etc/<profil>/.env` et n'a pas ce défaut : son include
  échoue proprement, et le profil versionné n'est jamais vide.
