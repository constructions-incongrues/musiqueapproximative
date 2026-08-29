---
id: auditeur-de-plateforme
type: persona
title: "L'auditeur de plateforme — anti-persona"
status: active
provenance: user-stated
sources: [pm-personas-q3-2026-08-20, routing-yml, desastres-spec]
relates_to: [melomane-fele, auditeur-du-jour]
last_updated: 2026-08-20
---

## Summary
L'anti-persona : celui qui veut un compte, un catalogue à parcourir, ses favoris, une
playlist personnelle, des recommandations. Choisi explicitement par l'auteur parmi trois
candidats. Le servir transformerait une émission quotidienne en service de streaming.

## What we know

**Il est tentant parce que l'inventaire existe déjà**
8 097 morceaux ressemblent à une bibliothèque ; il ne manquerait « que » l'interface.
Chacune de ses demandes est individuellement raisonnable et améliorerait une métrique.
- Choisi comme anti-persona par l'auteur, contre « le contributeur hors collectif » et « le visiteur qui veut que ça marche » — `pm-personas`, 2026-08-20

**Le refus est inscrit dans le routage**
`/` ouvre sur *le* morceau du jour, jamais sur l'index. Un produit-bibliothèque ouvrirait
sur l'index.
- "homepage → action: home" qui redirige vers le dernier morceau publié — `src/apps/frontend/config/routing.yml`, 2026-08-20

**Sa première demande serait d'éteindre les désastres**
Un service dont on attend qu'il fonctionne est incompatible avec un produit qui abîme
volontairement ses pages.
- "C'est un parti pris éditorial du site, pas un défaut." — `openspec/specs/desastres/spec.md`, 2026-08-20

**Le test de frontière n'est pas « est-ce que ça touche au catalogue »**
Borner `/posts` et étendre la recherche au corps et au contributeur *ressemblent* à ses
demandes mais servent le mélomane fêlé et le DJ. Le vrai test : **est-ce que ça remplace
le choix de quelqu'un par un choix de l'utilisateur ?**
- Avertissement ajouté par `pm-personas` pour éviter que l'anti-persona serve d'argument contre des correctifs légitimes — 2026-08-20

**Revisit when**
Aucune demande isolée ne rouvre la question. La condition serait structurelle : si le
collectif cessait de poster quotidiennement, l'archive deviendrait le produit — mais ce
serait alors un autre produit.

## Open / superseded

**Superseded — l'intégrateur comme anti-persona (2026-08-20)**
`pm-personas` avait proposé l'intégrateur (consommateur des formats machine, jamais
observé, 43 % de l'effort de test). Rejeté le jour même par l'auteur : « c'est un pari,
pas une erreur » — l'ouverture des formats est un principe (AGPLv3, aucun silo), pas une
tentative de servir un marché. Un principe ne se mesure pas à son audience.
