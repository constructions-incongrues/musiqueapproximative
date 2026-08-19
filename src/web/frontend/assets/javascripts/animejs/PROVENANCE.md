# anime.js 3.2.2 — dépendance versionnée

Fichier : `anime-3.2.2.min.js`
Source : <https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.2/anime.min.js>
sha256 : `bceef94f964481f7680d95e7fbbe5a8c20d3945a926a754874898a578db7c7ab`
Taille : 16 Kio
Licence : MIT — texte complet dans `anime-3.2.2.min.js.LICENSE.txt`

## Pourquoi il est ici plutôt qu'appelé depuis un CDN

Quatre recettes de désastre appelaient `cdnjs.cloudflare.com` à l'exécution, sur des
pages publiques. Même raison que pour Redoc le 2026-08-18 : le visiteur n'a pas à être
annoncé à un tiers.

Voir `docs/modules/ROOT/pages/dependances-tierces.adoc`.

## Ce qui autorise ce versement

La licence MIT autorise expressément la redistribution — « to deal in the Software
without restriction, including without limitation the rights to use, copy, modify,
merge, publish, distribute ». Vérifié le 2026-08-19 avant versement.

## Pourquoi la version est dans le nom du fichier

Pour que la mise à jour soit un geste délibéré et visible dans un diff, et non un
`latest` qui change sous les pieds.

## Comment le remplacer

    V=<version>
    curl -sL -o src/web/frontend/assets/javascripts/animejs/anime-$V.min.js \
      https://cdnjs.cloudflare.com/ajax/libs/animejs/$V/anime.min.js

Puis mettre à jour les recettes de `apps/frontend/config/desastres/recettes/`, les
exemples de `schemas/recettes.schema.json`, ce fichier et sa somme de contrôle, et
l'exclusion de `.trunk/trunk.yaml`. Supprimer l'ancienne version.
