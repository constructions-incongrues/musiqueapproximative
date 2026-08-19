## Why

Le catalogue va du **10 juin 2008 au 18 août 2026** — 6 643 jours, 8 216 morceaux. Et
**l'ancienneté d'un morceau n'a jamais déclenché ni modulé quoi que ce soit** : un morceau
de 2008 est servi exactement comme celui d'hier.

La bande usée existe depuis aujourd'hui, à intensité fixe. Lui donner l'âge du morceau pour
intensité, c'est faire entendre l'archive : ce qui dort là depuis dix-huit ans sonne comme
ce qui a attendu longtemps, le neuf reste net.

**La courbe a été choisie sur les morceaux réels, pas sur son élégance.** Le packet
prévenait qu'une courbe linéaire « rendrait presque tout inaudible ou presque rien
perceptible ». Mesuré sur le catalogue entier :

| courbe | quasi nets | subtils | nets | usés |
| --- | --- | --- | --- | --- |
| linéaire | 575 | 2 000 | 2 017 | **3 507** |
| exposant 1,5 | 1 376 | 1 666 | 2 824 | 2 233 |
| **exposant 2** | 2 384 | 1 401 | 2 943 | **1 371** |

La linéaire use fortement 43 % du catalogue — le travers annoncé. L'exposant 2 réserve
l'usure aux morceaux réellement anciens.

**Un plancher est ajouté**, faute de quoi 29 % des morceaux recevraient un désastre
inaudible alors que l'en-tête `X-Desastre` l'annonce. Avec plancher 0,15 :

```
2026 → 4,4 cents      2016 → 12,2      2011 → 21,6      2008 → 28,6
```

## What Changes

- **Exposer la date de publication dans la page.** Elle n'y figure nulle part aujourd'hui,
  ce qui est notable pour une archive de dix-huit ans. Une balise `meta`, lisible par le
  désastre et par quiconque d'autre en aura besoin.

- **Calculer l'intensité depuis cet âge** : `0,15 + 0,85 × (âge / 18 ans)²`, plafonnée à 1.

- **Fixer la référence à dix-huit ans plutôt qu'à l'étendue du catalogue.** Cette dernière
  grandit chaque jour : l'usure d'un morceau donné changerait sans que personne ne l'ait
  décidé. Une constante se lit, se discute et se règle.

**Hors périmètre** : toute mémoire d'une visite à l'autre (story 36) ; modifier le tirage,
la règle ou la probabilité.

## Capabilities

### Modified Capabilities

- `desastre-sonore` : l'intensité de l'altération dépend de l'âge du morceau, et le site dit
  quel âge il lui donne.

## Impact

- `src/apps/frontend/modules/post/templates/` — la balise de date
- `src/web/desastres/bande-usee/javascript/bande-usee.js` — le calcul de l'intensité
- `src/apps/frontend/config/desastres/recettes/bande-usee.yml` — les paramètres de la courbe
- Aucun changement du tirage, de la règle, du cache ni du processeur
