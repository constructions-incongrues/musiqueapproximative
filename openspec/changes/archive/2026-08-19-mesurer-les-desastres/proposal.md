## Why

Dix-neuf recettes de désastre sont déclarées, dix-neuf règles les déclenchent, et
**personne ne sait laquelle sort.** Mesuré aujourd'hui : aucun compteur, aucun en-tête,
aucune trace — les deux seuls `error_log` du plugin signalent un fichier d'import
manquant, rien d'autre.

Les quatre autres stories de cet axe se décident donc au jugé. Réparer un déclencheur ou
auto-héberger un CDN n'a pas le même prix selon qu'il sert mille fois par jour ou jamais.

Ce que la mesure du jour a établi, et qui contraint la conception :

- **19 recettes, 19 règles, aucune référence en l'air** dans un sens ni dans l'autre,
  aucune recette désactivée. `shared` est le seul répertoire de `web/desastres/` qu'aucune
  recette ne nomme, et c'est normal.
- **Les probabilités s'étalent de 0,1 à 1** : `0,1` ×2, `0,3` ×1, `0,5` ×2, `0,7` ×8, `1` ×6.
  Six règles se déclenchent à coup sûr quand leur requête correspond.
- **Le tirage a lieu à la production de la page**, et le cache sert ensuite sans
  réexécuter l'action : mesuré sur `/post/patrick-catani-…`, 0,146 s puis 0,032 s. Le
  document est identique à l'octet près d'une requête à l'autre.
- **La recette appliquée est déjà lisible dans le corps HTML** — `splitouine` y apparaît
  cinq fois sur la page ci-dessus — mais uniquement par correspondance de chaînes, sans
  rien de structuré. Les en-têtes ne disent rien.

## What Changes

- **Compter les TIRAGES, et le dire dans le nom.** Le cache a une durée de vie de 24 h et
  englobe la mise en page ; un compteur posé au tirage compte donc des défauts de cache,
  pas des visiteurs. C'est une grandeur utile — « quelle recette a été choisie, combien de
  fois » — à condition qu'elle ne soit jamais présentée comme une audience.

- **Refuser de répondre aux deux questions avec un seul chiffre.** Compter les
  consultations exigerait que PHP s'exécute là où le cache l'évite précisément. La mesure
  ci-dessus le confirme. Ce change ne le fera pas, et la donnée produite portera la
  distinction pour que personne ne l'interprète de travers dans six mois.

- **Poser un en-tête de réponse nommant la recette appliquée.** C'est la seule trace
  structurée aujourd'hui absente, elle sert au diagnostic autant qu'à la mesure, et elle
  est vérifiable de l'extérieur sans lire le corps.

- **Rendre le relevé lisible** par un moyen simple, sans interface de visualisation.

**Hors périmètre** : une interface de visualisation ; mesurer autre chose que les
désastres ; modifier le tirage, les probabilités ou les règles.

**Ne pas casser l'invariance.** Deux bugs archivés de cette zone n'étaient que des
ruptures d'invariance, et `desastreInvarianceTest` les garde. La collecte ne doit ni la
rompre, ni désactiver le cache pour se simplifier la vie.

## Capabilities

### New Capabilities

- `mesure-des-desastres` : ce que le site enregistre du tirage d'un désastre, et ce que
  cet enregistrement dit — ou refuse de dire.

### Modified Capabilities

- `desastres` : la réponse porte désormais la recette appliquée, sans que le tirage ni son
  invariance changent.

## Impact

- `src/plugins/sfDesastrePlugin/lib/sfDesastreManager.class.php` — le tirage, ligne ~252
- `src/plugins/sfDesastrePlugin/lib/filter/sfDesastreFilter.class.php` — la pose de l'en-tête
- `src/test/functional/frontend/desastreInvarianceTest.php` — l'invariance à préserver
- `src/apps/frontend/config/cache.yml` — à ne pas toucher, et c'est le point délicat
