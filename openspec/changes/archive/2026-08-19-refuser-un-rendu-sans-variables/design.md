# Conception

## Le mécanisme, reproduit avant d'être corrigé

```
src/Makefile:1   include .env
src/Makefile:11  VARS=`cat ./.env | cut -d '=' -f1 | sed 's/^/$$/' | tr "\n" " "`
src/Makefile:12  cat $DIST | envsubst "'$VARS'" > <rendu>
```

Deux gardes existent déjà, et une seule fonctionne :

- **`include .env` protège du fichier absent.** GNU make s'arrête, rien n'est écrit. C'est
  ce qui a fait croire au plan que l'absence était la cause — elle est au contraire la
  situation sûre.
- **Rien ne protège du fichier vide.** `include` d'un fichier vide réussit, `VARS` devient la
  chaîne vide, et `envsubst ''` ne substitue **aucune** variable : il recopie le gabarit
  verbatim. Le rendu porte alors `${DATABASE_HOST}`, et la connexion échoue.

Le trou est donc exactement là où le plan ne regardait pas.

## Décision : garder sur la liste, pas sur le fichier

La condition qui compte n'est pas « le fichier existe » — `include` s'en charge — mais
**« la liste blanche contient au moins une variable »**. C'est elle que `envsubst` consomme,
et c'est son vide qui produit le dégât.

Garder sur l'existence du fichier laisserait passer le cas vide, c'est-à-dire précisément
l'incident.

## Décision : vérifier une fois, avant d'écrire

La cible boucle sur les gabarits et écrit chacun au fil de la boucle. Un garde placé dans la
boucle échouerait au premier fichier, en ayant peut-être déjà écrit les précédents — une
configuration à moitié rendue est plus difficile à diagnostiquer qu'une configuration
entièrement fausse.

La vérification a donc lieu **avant** la boucle. Soit tout est rendu, soit rien ne l'est.

## Ce que le message doit dire

Trois choses, parce que la personne qui le lit vient d'être arrêtée au milieu d'un geste :

- **ce qui a été refusé**, et que rien n'a été touché ;
- **ce qui manque** — un `.env` porteur de variables, à cet endroit précis ;
- **où le lire** — le `.env` du profil, et le fait que cette commande se lance en
  développement, pas sur le serveur.

Un message qui dit seulement « erreur » renvoie à la documentation, et l'incident a
justement eu lieu parce que la documentation n'a pas été lue.

## Ce que cette conception écarte

**Rendre `make configure` inoffensif en toutes circonstances**, par exemple en écrivant dans
un répertoire temporaire puis en basculant. Plus sûr, et hors de proportion : le défaut tient
en une condition manquante.

**Supprimer la cible et rendre les fichiers autrement.** C'est la question du déploiement, et
elle dépasse cette story — le contrat OpenAPI l'a déjà quittée par un autre chemin.
