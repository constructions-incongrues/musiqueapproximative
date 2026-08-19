# GSAP 3.13.0 et SplitText 3.13.0 — dépendances versionnées

| Fichier | Source | sha256 | Taille |
| --- | --- | --- | --- |
| `gsap-3.13.0.min.js` | <https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js> | `96c01b81f44a3290e2b4532f55e2c9534b2adc43273a19f3756b2cb41f0fd0b6` | 70 Kio |
| `SplitText-3.13.0.min.js` | <https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/SplitText.min.js> | `5e519ea2470faa15ca4d4f27e9263e5906c07c034a9b4771164dee108136563a` | 7 Kio |

Licence : conditions GreenSock — reproduites dans les fichiers `.LICENSE.txt` à côté.

## Pourquoi ils sont ici plutôt qu'appelés depuis un CDN

Sept recettes de désastre sur dix-neuf appelaient `cdn.jsdelivr.net` et
`cdnjs.cloudflare.com` à l'exécution, sur des pages publiques, au hasard d'un tirage
que le visiteur ne choisit pas. C'est la même raison qui avait fait verser Redoc le
2026-08-18 : le visiteur n'a pas à être annoncé à un tiers.

Voir `docs/modules/ROOT/pages/dependances-tierces.adoc`.

## Ce qui autorise ce versement

Auto-héberger, c'est redistribuer. La question a été instruite avant le versement, le
2026-08-19 :

> « All of GSAP including the plugins that were formerly "members-only" like SplitText
> and MorphSVG can be used in commercial projects at no charge. »
> — <https://gsap.com/standard-license>

La licence accorde « a non-exclusive, worldwide license to use, reproduce, display, and
implement GSAP Products », et GreenSock documente le téléchargement puis le chargement
par balise `script` depuis son propre site comme méthode d'installation supportée —
<https://gsap.com/docs/v3/Installation>.

La seule restriction vise les outils permettant de créer des animations visuelles sans
code, concurrents des capacités de Webflow. Ce site n'en est pas un.

## Pourquoi la version est dans le nom du fichier

Pour que la mise à jour soit un geste délibéré et visible dans un diff, et non un
`latest` qui change sous les pieds.

## Comment les remplacer

    V=<version>
    A=src/web/frontend/assets/javascripts/gsap
    curl -sL -o $A/gsap-$V.min.js      https://cdn.jsdelivr.net/npm/gsap@$V/dist/gsap.min.js
    curl -sL -o $A/SplitText-$V.min.js https://cdn.jsdelivr.net/npm/gsap@$V/dist/SplitText.min.js

Puis mettre à jour les recettes de `apps/frontend/config/desastres/recettes/`, les
exemples de `schemas/recettes.schema.json`, ce fichier et ses sommes de contrôle, et
l'exclusion de `.trunk/trunk.yaml`. Supprimer l'ancienne version. Vérifier de nouveau
la licence : les conditions de GreenSock ont déjà changé d'une version à l'autre.
