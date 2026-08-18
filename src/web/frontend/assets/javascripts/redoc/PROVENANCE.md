# Redoc 2.5.3 — dépendance versionnée

Fichier : `redoc-2.5.3.standalone.js`
Source : <https://cdn.redoc.ly/redoc/v2.5.3/bundles/redoc.standalone.js>
sha256 : `1320f442151c57c447d3b70c7ffc6c4f86d08464020fe34c8cc5d3164e9944f0`
Taille : 1 071 Kio
Licence : MIT — attributions complètes dans `redoc-2.5.3.standalone.js.LICENSE.txt`

## Pourquoi il est ici plutôt qu'appelé depuis un CDN

Le visiteur qui consulte le contrat n'a pas à être annoncé à un tiers. Le dépôt
n'a aucune dépendance externe à l'exécution hormis le service de glitch, qui est
maison ; celle-ci n'en introduit pas.

## Pourquoi la version est dans le nom du fichier

Pour que la mise à jour soit un geste délibéré et visible dans un diff, et non
un `latest` qui change sous les pieds. L'octet pour octet a été vérifié entre
l'URL épinglée et `latest` au moment du versement.

## Comment le remplacer

    curl -sL -o src/web/api/vendor/redoc-<version>.standalone.js \
      https://cdn.redoc.ly/redoc/v<version>/bundles/redoc.standalone.js
    curl -sL -o src/web/api/vendor/redoc-<version>.standalone.js.LICENSE.txt \
      https://cdn.redoc.ly/redoc/v<version>/bundles/redoc.standalone.js.LICENSE.txt

Puis mettre à jour le `<script src>` de `src/web/api.html`, ce fichier, et
l'exclusion de `.trunk/trunk.yaml`. Supprimer l'ancienne version.
