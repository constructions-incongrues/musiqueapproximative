---
id: melomane-fele
type: persona
title: "Le mélomane fêlé — le contributeur du collectif"
status: active
provenance: user-stated
sources: [layout-php-108, generator-yml-40, discovery-2026-08-18, pm-personas-q1-2026-08-20]
relates_to: [auditeur-du-jour, auditeur-de-plateforme]
last_updated: 2026-08-20
---

## Summary
Un membre du collectif Musique Approximative, qui poste ses obsessions musicales et
écoute celles des autres. Co-primaire avec l'auditeur du jour — l'auteur refuse de les
séparer, ce sont deux moments de la même personne. C'est le persona dont dépend
l'existence quotidienne du produit : sans lui, pas de morceau du jour.

## What we know

**Le collectif se nomme lui-même ainsi**
Le nom du persona vient du site, pas de l'analyse.
- "C'est l'exutoire anarchique d'une bande de mélomanes fêlé⋅e⋅s." — `src/apps/frontend/templates/layout.php:108`, 2026-08-20

**Son job-to-be-done tient en deux verbes : poster, retrouver**
L'auteur a corrigé une première lecture qui le faisait utiliser Radio Approximative.
- "ce qu'il veut est poster facilement et retrouver facilement" — `openspec/discovery.md`, seconde révision, 2026-08-18

**Poster fonctionne ; retrouver est cassé**
L'admin restreint bien la liste à ses propres morceaux et la trie par date décroissante,
mais les filtres sont désactivés au générateur.
- "filter: class: false" — `src/apps/admin/modules/post/config/generator.yml:40`, 2026-08-20
- "993 morceaux à parcourir vingt par vingt" chez le contributeur le plus prolifique — `openspec/discovery.md`, 2026-08-18

**La recherche publique ne couvre pas ce qu'il écrit**
`actAs: Searchable` n'indexe que `track_author` et `track_title` : ni le corps markdown
qu'il rédige sous le morceau, ni son propre nom de contributeur.
- "Searchable: fields: [track_author, track_title]" — `src/config/doctrine/schema.yml`, 2026-08-20

**Le risque n'est pas l'adoption, c'est le décrochage**
Il est déjà là, souvent depuis des années. Le jour où retrouver coûte plus que poster,
il poste moins.
- Inférence structurelle, non mesurée — `pm-personas`, 2026-08-20

**Aucun signal comportemental n'est disponible**
Le seul traceur du site est un `ga.js` mort (Universal Analytics arrêté en 2023).
- "google-analytics.com/ga.js" — `src/apps/frontend/templates/layout.php:486`, 2026-08-20

## Open / superseded

**Superseded — persona secondaire → co-primaire (2026-08-20)**
`.nanopm/wiki/docs/product.md` le plaçait derrière l'auditeur du jour, par inférence.
Remplacé le 2026-08-20 : l'auteur répond « les deux, indissociables » à la question du
persona principal.

**Ouvert — la santé du collectif n'est pas mesurée**
Rien ne suit le nombre de contributeurs distincts qui postent par trimestre. C'est le
test le moins cher du pari central, et personne n'a encore regardé cette courbe.
