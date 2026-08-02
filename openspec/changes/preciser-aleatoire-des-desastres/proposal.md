## Why

Le corpus affirme, sous « Requirement: Part d'aléatoire » :

> Une règle SHALL pouvoir ne se déclencher qu'une fois sur plusieurs, afin que **deux
> consultations de la même page ne produisent pas nécessairement le même effet**.

C'est faux. Deux consultations de la même page produisent **toujours** le même effet, et ce
pendant vingt-quatre heures.

Mesuré le 2 août 2026 contre la production, sur `/post/eloi-soleil-mort`, dont le titre
« Soleil Mort » satisfait la règle `postillons_mort` déclarée à `probability: 0.7` :

| Requête | Déclenchements |
|---|---|
| URL nue, vingt fois | **0 / 20** |
| URL variée par un paramètre inerte (`?cb=1`, `?cb=2`…), vingt fois | **11 / 20** |

Obtenir zéro sur vingt à 0,7 a une probabilité de 3 × 10⁻¹¹. Ce n'est pas du hasard : le
tirage est **figé par URL**.

Ni le navigateur ni Cloudflare n'y sont pour quelque chose — les en-têtes de réponse
annoncent `no-store, no-cache, must-revalidate` et `cf-cache-status: DYNAMIC`. C'est le
cache de vues de Symfony, déclaré sans réserve dans `src/apps/frontend/config/cache.yml` :

```yaml
default:
  enabled:     true
  with_layout: true
  lifetime:    86400
```

Toutes les actions, avec le gabarit d'habillage, pour **86 400 secondes**. Un désastre
tiré une fois est donc servi à tous les visiteurs de cette URL pendant vingt-quatre heures,
puis retiré au hasard suivant.

**Un désastre à `probability: 0.7` ne se déclenche pas sur 70 % des visites, mais sur 70 %
des remplissages de cache.**

Ce n'est pas nécessairement un défaut. C'en est un pour le corpus, qui décrit autre chose
que ce qui se passe — et ce dépôt a déjà payé neuf fois le prix d'un document qui décrit
sans correspondre.

## What Changes

- L'exigence « Part d'aléatoire » est réécrite pour décrire ce qui a lieu : le tirage est
  fait au rendu, puis figé pour la durée du cache, et il porte sur une URL et non sur une
  visite.
- Une exigence est ajoutée sur la granularité du hasard, pour que la propriété soit
  énoncée plutôt que subie : ce qui varie, c'est la page et le moment, pas le visiteur.
- **Aucun changement de comportement.** Ce changement documente ; il ne touche ni au
  cache, ni au moteur de règles, ni à une seule recette.

### L'arbitrage : décrire, ou corriger

Deux voies s'offraient, et la seconde est écartée pour ce changement-ci.

**Corriger** supposerait de soustraire les désastres au cache — cache par fragment,
exclusion des actions concernées, ou injection côté client. Chacune de ces pistes coûte
cher sur un socle Symfony 1.5, et l'une d'elles au moins — désactiver le cache sur
`post/show` et `post/list` — dégraderait un site dont c'est l'essentiel du trafic. Le
rapport bénéfice/risque n'est pas favorable pour rétablir un aléa par visiteur dont
personne n'a établi qu'il était voulu.

**Décrire** coûte une exigence réécrite et rend la propriété visible. Si le comportement
figé déplaît, la discussion pourra s'ouvrir sur une base exacte plutôt que sur une
exigence qui prétend le contraire.

Le choix retenu est de décrire. **Il est réversible : rien n'empêche un changement
ultérieur de modifier le comportement**, et il partira alors d'un corpus qui dit vrai.

### Approche

La contrainte du socle qui oriente ce choix : sur Symfony 1.5, le cache d'action avec
gabarit court-circuite l'action entière. `apply_desastre()` n'est donc pas rappelée sur un
succès de cache, et le tirage ne peut pas être rejoué sans repenser l'endroit où les
désastres sont appliqués. Ce n'est pas un réglage, c'est une décision d'architecture.

L'ordre exact entre l'écriture du cache et le filtre d'injection `sfDesastreFilter` n'a pas
pu être établi de l'extérieur, et ce changement ne l'affirme pas. Ce qui est mesuré, et qui
suffit à l'exigence, c'est que le résultat servi est identique d'une requête à l'autre.

## Hors périmètre

- **Toute modification du cache** — `cache.yml`, `lifetime`, exclusion d'actions, cache par
  fragment. C'est l'objet d'un éventuel changement suivant, et il devra peser le coût sur
  la charge du site.
- Le moteur de règles, les probabilités déclarées, les recettes, les déclencheurs.
- La question de savoir si l'aléa par visiteur est souhaitable. Ce changement ne la tranche
  pas ; il la rend posable.
- Les autres capacités, dont aucune ne décrit un comportement dépendant du cache. Vérifié
  au passage.

## Capacités

### Nouvelles capacités

Aucune.

### Capacités modifiées

- `desastres` — l'exigence « Part d'aléatoire » est corrigée, et complétée d'une exigence
  sur la granularité du tirage.

## Impact

- `openspec/specs/desastres/spec.md` après synchronisation.
- **Aucun fichier de `src/` n'est touché.** Contrat public inchangé, comportement inchangé.
- Ce que le lecteur du corpus y gagne : il cesse d'attendre des désastres un aléa par
  visiteur, et sait que pour observer une règle probabiliste il lui faut faire varier
  l'URL — ce qui est exactement le geste que la campagne de vérification a dû inventer
  faute de le trouver écrit.
