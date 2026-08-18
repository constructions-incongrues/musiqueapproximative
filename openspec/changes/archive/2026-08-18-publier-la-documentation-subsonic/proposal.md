## Why

`deploiement.adoc:36` renvoie à `xref:API_SUBSONIC.adoc[]`. Cette page n'existe pas :
le contenu réel est `docs/API_SUBSONIC.md`, un fichier Markdown posé **hors** de
l'arborescence Antora (`docs/modules/ROOT/pages/`). Antora le signale à chaque
construction — `target of xref not found: API_SUBSONIC.adoc` — et le lecteur du site
n'obtient rien.

La surface Subsonic n'est pas un projet : elle est **livrée**. `src/apps/frontend/modules/rest/`
la sert, `routing.yml` porte `/rest/:method` et `/rest/:method.view`, `scan-tracks` alimente
ses durées. Sa seule documentation — comment brancher un client, ce que la bibliothèque
contient, quelles méthodes répondent — est donc de la documentation d'usage, et elle est
invisible depuis le site qui l'accompagne. C'est le mode de défaillance que le change
`reparer-la-navigation-de-la-documentation` vient de traiter pour seize pages : le travail
existe, personne ne peut y accéder.

Ce change y ajoute la dix-septième.

## What Changes

- `docs/API_SUBSONIC.md` devient `docs/modules/ROOT/pages/api-subsonic.adoc`, converti en
  AsciiDoc **sans réécriture** : mêmes sections, mêmes phrases, mêmes exemples.
- La page est inscrite à `docs/modules/ROOT/nav.adoc`, sous « Déployer et exploiter », comme
  l'exige le contrôle d'exhaustivité de la CI.
- `deploiement.adoc:36` renvoie désormais à `xref:api-subsonic.adoc[]`, cible qui existe.

Le contrat public n'est pas concerné : aucune route, aucune réponse, aucun en-tête ne change.
Seule la documentation bouge.

## Le second lien signalé n'en était pas un

Le change précédent relevait deux liens cassés. Le second — `README.adoc:112`,
`xref:autre-page.adoc[]` donné en exemple de syntaxe — **ne produit aucune erreur**.
Construction Antora 3.1.14 sur l'état actuel, seule erreur de référence croisée relevée :

```
ERROR | deploiement.adoc | target of xref not found: API_SUBSONIC.adoc
```

L'exemple est déjà enfermé dans un bloc `[source,asciidoc]` délimité par cinq tirets
(`README.adoc:83` à `115`), et le tiret supplémentaire est précisément ce qui lui permet de
contenir un bloc `----` sans se refermer. Antora ne l'interprète donc pas : il l'affiche.
Rien à corriger — le relevé initial était une supposition, la construction l'a démentie.

## Capabilities

### Modified Capabilities

- `documentation-publiee` gagne une exigence. L'atteignabilité portait sur les pages déjà
  publiées ; elle ne disait rien des renvois d'une page vers une autre, ni des documents
  d'usage que le site ne sert pas encore.

## Hors périmètre

- **Réécrire le contenu de la page Subsonic.** Sa section « Déploiement » décrit un
  `make deploy` par rsync que le dépôt tient pour déprécié — Plesk tire `main` à chaque
  poussée. Cet écart existe déjà dans le fichier Markdown ; le publier ne le crée pas, et
  le corriger est un autre travail. La conversion est mot pour mot.
- **`docs/API_JSON_API_TARGET.md`.** Lui reste hors du site, et c'est délibéré : `CLAUDE.md`
  le désigne comme une archive d'une cible mise de côté, pas comme de la documentation.
  Publier une cible abandonnée à côté de la documentation d'un service livré est
  exactement la dérive que `docs/memory-bank/` a coûté.
- **Faire échouer la construction sur une référence non résolue.** Antora sait le faire
  (`runtime.log.failure_level`), mais la construction actuelle porte trois autres défauts
  — deux tables mal fermées dans `migration-utf8mb4.adoc` (lignes 236 et 255) et un attribut
  `database_name_test` absent dans `developpement/environnement.adoc`. Poser le garde-fou
  sans les traiter rendrait le workflow `Documentation` rouge dès la fusion. C'est un change
  à part entière, qui a son propre relevé.
- **Le reste du contenu des pages.** Ce change ne touche qu'un lien et le document qu'il
  vise.

## Impact

- **Ajouté** : `docs/modules/ROOT/pages/api-subsonic.adoc`.
- **Retiré** : `docs/API_SUBSONIC.md`.
- **Modifié** : `docs/modules/ROOT/nav.adoc` (une entrée),
  `docs/modules/ROOT/pages/deploiement.adoc` (une ligne).
- **Non modifié** : `src/`. Aucun code applicatif n'est touché.
- **Dépendances** : aucune. Ce change se pose sur `reparer-la-navigation-de-la-documentation`,
  dont il lui faut `nav.adoc` et le contrôle d'exhaustivité.
