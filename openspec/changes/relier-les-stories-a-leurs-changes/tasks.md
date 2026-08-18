# Tâches

Pas de `specs/` : `skip_specs` est déclaré. Rien du comportement observable du site ne
change — c'est l'appareil de planification qui cesse de perdre ce qu'il a livré.

Pas de `design.md` non plus : ni module, ni schéma, ni dépendance. La seule décision qui en
aurait relevé — l'asymétrie du contrôle — est traitée dans la proposition, à l'endroit où
elle se lit.

## 1. Mesurer avant d'écrire le contrôle

- [x] 1.1 Relevé sur le plan tel qu'il est : **12** stories cochées dont le change résout,
  **9** sans ligne `Change`, **0** dont la ligne ne résout pas.
- [x] 1.2 **Corriger la supposition du packet** : le lien n'était pas à inventer, il existe
  déjà. Le défaut est que la convention n'est pas tenue une fois sur deux — y compris par
  l'assistant, sur des stories livrées le jour même.
- [x] 1.3 Mesurer la réciproque avant de l'exiger : **20 des 50 archives ne sont citées
  nulle part**, toutes antérieures au plan. L'exiger produirait vingt fausses alertes
  permanentes.

## 2. Réparer les neuf déclarations manquantes

- [x] 2.1 **La mesure du packet était imprécise, et la correction compte.** Quatre stories
  n'avaient effectivement aucune ligne `Change` — 23, 24, 25, 34. Les cinq autres — 9, 11,
  12, 17, 20 — en portaient une, **restée à sa valeur de gabarit `_pas encore proposé_`**
  des jours après la livraison.
  Le vrai défaut n'est donc pas une ligne absente mais **une ligne jamais mise à jour**, ce
  qui est plus difficile à voir : le packet a l'air complet.
- [x] 2.2 Chacune vérifiée contre l'archive réelle. Cinq noms diffèrent de leur slug, dont
  la story 25 — packet `rendre-l-encodage-de-connexion-verifiable`, change
  `rendre-l-encodage-verifiable`, écrite le jour même.

## 3. Le contrôle

- [x] 3.1 Échouer quand une story cochée ne déclare aucun change, ou en déclare un qui
  n'existe ni parmi les actifs ni parmi les archives.
- [x] 3.2 **Nommer la story** en cause, par une annotation qui pointe le fichier. Un écart
  global se contourne ; un écart nommé se corrige.
- [x] 3.3 **Ne pas exiger la réciproque.** Une archive sans story n'est pas une erreur : le
  plan est postérieur à vingt d'entre elles, et le travail hors plan est légitime.
- [x] 3.4 Le placer dans `Validation du code` (`ci.yml`), qui tourne sur chaque proposition
  et fait partie des contextes exigés par le ruleset `main` — au même endroit que le contrôle
  de navigation de la documentation, qui règle le même genre de dérive.

## 4. Vérification

- [x] 4.1 Passe sur l'état corrigé : « 21 story(s) livree(s), toutes reliees a un change
  existant », code de sortie 0.
- [x] 4.2 **Il mord dans les deux sens**, et nomme la story : ligne retirée → « story 9 …
  ne declare aucun change » ; ligne faussée → « declare change-qui-nexiste-pas, qui n existe
  pas ». **Code de sortie 1** dans les deux cas.
  *Vérifié séparément* : la première mesure affichait 0, mais c'était le code de `sed` dans
  le tuyau, pas celui du contrôle. Un contrôle qui signale sans échouer ne sert à rien.
- [x] 4.3 Vérifier qu'une story **non cochée** sans ligne `Change` ne déclenche rien — c'est
  l'état normal d'une story qui n'est pas encore partie.
- [x] 4.4 Vérifier qu'une archive sans story ne déclenche rien.

## 5. Ce que ce change ne ferme pas

- [ ] 5.1 **Un packet dont les chiffres ont vieilli reste indétectable.** Quatre packets se
  sont révélés faux sur leur cause dans la même journée — le diagnostic Markdown, le verrou
  `getid3`, le « 3,1 s » du XSPF, et le `.env` de `make configure`. Un chiffre périmé est
  syntaxiquement identique à un chiffre juste : aucun contrôle ne les distingue. Seule une
  convention le peut — reprendre la mesure avant de promouvoir une story — et elle n'est pas
  outillable.
- [ ] 5.2 Le contrôle ne dit rien de la **qualité** du rapprochement : il vérifie qu'un nom
  résout, pas que le change fasse ce que la story annonçait.
