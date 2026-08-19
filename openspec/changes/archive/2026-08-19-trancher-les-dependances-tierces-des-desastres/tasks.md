## 1. Vérifier les licences avant tout versement

- [x] 1.1 Établir, pour `gsap@3.13.0`, `SplitText` et `anime.js@3.2.2`, si la licence
      autorise la **redistribution** et non seulement l'usage. Auto-héberger, c'est
      redistribuer.
- [x] 1.2 Traiter `SplitText` comme le cas à instruire en priorité : ce greffon a longtemps
      été réservé aux adhérents GSAP, et les conditions ont changé selon les versions.
- [x] 1.3 Si une licence ne permet pas la redistribution, **s'arrêter pour ce fichier** et
      écrire l'exception avec sa raison. Ne pas contourner : une exception écrite atteint le
      but de ce change, un contournement le manque.

## 2. Verser les fichiers

- [x] 2.1 Verser chaque fichier autorisé sous
      `web/frontend/assets/javascripts/<bibliothèque>/`, la version dans le nom, en suivant
      le motif de Redoc plutôt qu'en inventant une convention.
- [x] 2.2 Verser la licence à côté de chaque fichier, comme `redoc-2.5.3.standalone.js.LICENSE.txt`.
- [x] 2.3 Vérifier que les fichiers versés sont bien ceux des versions employées
      aujourd'hui — `gsap@3.13.0`, `animejs@3.2.2` — et non une version plus récente
      ramassée au passage.

## 3. Basculer les recettes

- [x] 3.1 Remplacer les URL tierces par les chemins locaux dans les sept recettes :
      `amour`, `consonnard`, `light`, `musique`, `noir`,
      `splitouine_titles_matchduration`, `voyelliste`.
- [x] 3.2 Vérifier qu'aucune URL tierce ne subsiste dans l'ensemble des recettes.
      **Élargi à l'implémentation** : le YAML ne suffisait pas. Quatorze mentions
      subsistaient dans les ressources et les README, et le schéma JSON proposait encore
      des URL de CDN **en exemple** — un gabarit qui enseigne à réintroduire ce qu'on
      retire. Tout est basculé.

## 4. Vérifier par ce que le navigateur demande

- [x] 4.1 Pour chacune des sept recettes, forcer son désastre par son paramètre d'URL et
      constater qu'**aucune requête n'est faite vers `cdn.jsdelivr.net` ni
      `cdnjs.cloudflare.com`**. La vérification porte sur les requêtes réellement émises,
      pas sur ce que le YAML déclare : une feuille de style, une police ou un appel que la
      bibliothèque fait elle-même au chargement passeraient à travers une relecture.
- [x] 4.2 Constater que l'effet visuel de chaque recette est toujours produit. Un contrôle
      qui passerait au vert en n'ayant rien chargé du tout ne vaudrait rien.
- [x] 4.3 Ajouter la couverture automatique correspondante, et la vérifier par l'échec :
      remettre une URL tierce dans une recette doit faire échouer le contrôle en nommant la
      recette.
- [x] 4.4 Vérifier que le tirage, l'invariance et l'en-tête `X-Desastre` sont inchangés.

## 5. Écrire la décision

- [x] 5.1 Documenter l'inventaire. **Deux hôtes tiers vivants n'étaient dans AUCUN
      inventaire**, découverts en cherchant hors du YAML : `lottie.host` (un `<iframe>`
      dans `fish`) et `allocestmamie.partouze-cagoule.fr` (le jingle de `mamie`). Le second
      est un domaine du collectif, confirmé — pas un tiers. Le premier reste une exception
      écrite : sa licence n'est pas établissable, son adresse étant un identifiant anonyme,
      et la spécification interdit de verser sans vérifier. Documenter l'inventaire même
      vide — un inventaire vide omis ne distingue pas « aucun tiers »
      de « personne n'a regardé ».
- [x] 5.2 Nommer ce qu'un tiers reçoit : adresse IP, page consultée par le `Referer`,
      empreinte de navigateur. Pas « un appel externe ».
- [x] 5.3 Écrire la décision et son motif, en la reliant explicitement au précédent Redoc —
      c'est la contradiction que ce change existe pour lever.
- [x] 5.4 Rattacher la page à la navigation de la documentation.

## 6. Clore

- [x] 6.1 Cocher la story 31 dans `openspec/discovery.md` et y déclarer ce change.
- [x] 6.2 Consigner ce que la mesure a confirmé ou corrigé du packet.
