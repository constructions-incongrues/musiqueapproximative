---
id: auditeur-du-jour
type: persona
title: "L'auditeur du jour"
status: active
provenance: user-stated
sources: [routing-yml, layout-php-jplayer, metadonnees-partage-spec, pm-personas-q1-2026-08-20]
relates_to: [melomane-fele, auditeur-de-plateforme]
last_updated: 2026-08-20
---

## Summary
Quelqu'un qui ouvre musiqueapproximative.net pour écouter, sans compte, sans savoir qu'il
consomme une API. Souvent un membre du collectif dans son autre moment ; parfois un
extérieur arrivé par un lien partagé ou le flux RSS. Co-primaire avec le mélomane fêlé.

## What we know

**Le chemin par défaut du site est le sien**
`/` redirige vers le dernier morceau publié ; l'enchaînement passe par trois routes
dédiées et quatre points d'appel JavaScript.
- "homepage: url: / param: { module: post, action: home }" — `src/apps/frontend/config/routing.yml`, 2026-08-20
- Raccourcis clavier `espace` / `j` / `k` / `r` / `s` déclarés en pied de page — `src/apps/frontend/templates/layout.php`, 2026-08-20

**Sa porte d'entrée est le lien partagé**
Les métadonnées OpenGraph existent pour qu'un lien partagé restitue titre, illustration
et lecteur réellement jouable.
- "afin qu'un lien partagé restitue le titre, l'illustration et un lecteur audio réellement jouable" — `openspec/specs/metadonnees-partage/spec.md`, 2026-08-20

**Une angoisse propre à ce site : un désastre ressemble à une panne**
Le produit abîme volontairement ses pages. Un visiteur qui tombe sur une recette forte à
sa première visite peut croire le site cassé.
- Inférence, non testée auprès d'un visiteur réel — `pm-personas`, 2026-08-20

**Sa motivation n'est documentée nulle part**
Aucun document du dépôt ne rapporte un mot d'auditeur réel. Le « pourquoi » (être surpris
par un choix humain plutôt que servi par un calcul) est reconstruit depuis la seule
donnée que le schéma rend obligatoire : le texte du contributeur.
- "body: type: string, notnull: true" — `src/config/doctrine/schema.yml`, 2026-08-20

**Le DJ de soirée est son cas de charge, pas un autre persona**
Même job-to-be-done, conditions dégradées : mobile, réseau de salle, urgence. C'est là
que les défauts apparaissent.
- "/posts sert 3,7 Mo et 8 097 liens" contre "43 ko en 0,35 s" par la recherche — `openspec/discovery.md`, mesures du 2026-08-18
- Repli décidé par `pm-personas` contre `discovery.md`, qui en faisait un persona distinct — 2026-08-20

**Rien n'est compté**
Ni l'usage de `/posts/next|prev|random`, ni les abonnés du flux RSS.
- Traceur mort : "google-analytics.com/ga.js" — `src/apps/frontend/templates/layout.php:486`, 2026-08-20

## Open / superseded

**Ouvert — le DJ de soirée comme persona distinct**
`openspec/discovery.md` le traite comme un persona à part entière. `pm-personas` l'a
replié en cas de charge sans interroger l'auteur sur ce point précis. À rouvrir s'il y
tient.
