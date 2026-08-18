> Pas de `design.md`. Son instruction est conditionnelle — « seulement si le changement est
> structurant » — et celui-ci retire deux déclarations d'un attribut. La décision et son
> tableau des modalités sont dans `openspec/discovery.md`, story 14.

## 1. Établir l'état de départ

- [x] 1.1 Relever la balise `viewport` telle qu'elle est servie : ouvrir une page et
  constater `minimum-scale=1, maximum-scale=1`.
- [x] 1.2 Confirmer que le frontend n'a qu'un seul champ de saisie — celui de la
  recherche — et que sa taille de police est de 16 px sous la media query étroite. C'est
  ce qui rend la balise inutile ; si c'était faux, ce changement réintroduirait le zoom au
  focus qu'il prétend ne pas causer.

## 2. Retirer la contrainte

- [x] 2.1 Dans `src/apps/frontend/templates/layout.php` ligne 7, retirer
  `, minimum-scale=1, maximum-scale=1`. Conserver `width=device-width, initial-scale=1`.
- [x] 2.2 Ne rien toucher d'autre — en particulier pas le CSS mort d'`input.mailing`, qui
  est hors périmètre.

## 3. Vérification

- [x] 3.1 Vider le cache, charger une page de morceau et une page de liste, et constater
  dans la source servie que la balise ne porte plus que
  `width=device-width, initial-scale=1`.
- [ ] 3.2 **Non menée, et c'est dit plutôt que coché.** Le geste de pincement n'est pas
  reproductible dans le navigateur de vérification : `visualViewport.scale` est en lecture
  seule et aucune API ne simule l'écartement de deux doigts. Ce qui a été vérifié est la
  **déclaration** — la balise ne porte plus de maximum (tâche 3.1) — et c'est elle seule qui
  empêchait le geste. Le geste lui-même demande un terminal réel. Voir 3.7.
- [x] 3.3 En émulation mobile à 360 px, mettre le champ de recherche au point et relever
  `visualViewport.scale` : il doit rester à 1. C'est la vérification qui compte — elle
  démontre que la garantie tenait sur la taille de police et non sur l'interdiction.
- [x] 3.4 Vérifier que la mise en page n'a pas bougé : la page de liste et la page de
  morceau se rendent comme avant à 360 px et à 1280 px, sans débordement horizontal.
- [x] 3.5 `docker-compose exec php php symfony test:all` — la suite passe. Aucun test ne
  couvre la balise ; ce point vérifie qu'on n'a rien cassé, pas qu'on a réussi.
- [x] 3.6 `openspec validate retirer-le-verrouillage-du-zoom --type change --strict`.

### Ce que cette vérification ne peut pas établir

- [x] 3.7 Consigner la limite : Safari iOS ignore `maximum-scale` depuis iOS 10, et Chrome
  Android l'ignore si le visiteur a activé « Forcer l'activation du zoom ». Sur ces
  navigateurs, **le changement ne modifie rien d'observable** — la règle y était déjà
  désarmée, par Apple et Google, contre l'auteur du site. Ce que ce changement corrige
  concerne les navigateurs qui l'appliquaient encore, et le fait que le site cesse de la
  déclarer. Vérifier sur un terminal réel n'est pas possible ici ; le dire plutôt que de
  laisser croire à une vérification plus large.

### Mesures relevées le 2026-08-18

Navigateur de vérification, émulation mobile 375 px puis 1280 px, cache vidé.

| | avant | après |
| --- | --- | --- |
| balise `viewport` servie | `width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1` | `width=device-width, initial-scale=1` |
| `visualViewport.scale` au chargement | 1 | 1 |
| `visualViewport.scale` après mise au point du champ | — | **1** (inchangé) |
| taille de police du champ à 375 px | 16 px | 16 px |
| hauteur de cible du champ | 46 px | 46 px |
| débordement horizontal à 375 px | non | non |
| débordement horizontal à 1280 px | non | non |

La ligne qui porte la démonstration est la troisième : la page ne se met pas à l'échelle à
la mise au point **alors que rien ne l'interdit plus**. C'est la taille de police du champ
qui tient cette garantie, comme `2026-08-18-fix-recherche-mobile` l'avait établi — et non
l'interdiction faite au visiteur, qui vient d'être retirée.

Suite de tests : 17 fichiers, 507 tests, inchangés. Aucun ne couvre la balise ; ce chiffre
dit qu'on n'a rien cassé, pas qu'on a réussi.
