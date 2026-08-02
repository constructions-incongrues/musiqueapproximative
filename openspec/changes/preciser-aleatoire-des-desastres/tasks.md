## 1. Correction de l'exigence

- [x] 1.1 Réécrire « Requirement: Part d'aléatoire » pour décrire le tirage tel qu'il a lieu : au moment de la production de la page, puis figé pour la durée du cache
- [x] 1.2 Retirer l'affirmation « deux consultations de la même page ne produisent pas nécessairement le même effet », que la mesure contredit
- [x] 1.3 Ajouter un scénario disant comment observer une règle probabiliste — forcer par le déclencheur, ou faire varier l'adresse. C'est le geste que la campagne de vérification a dû inventer faute de le trouver écrit
- [x] 1.4 Ajouter une exigence « Granularité du tirage » : le hasard porte sur l'adresse et sur le moment, jamais sur le visiteur

## 2. Contrôle du reste du corpus

- [x] 2.1 Vérifier qu'aucune autre exigence ne suppose un aléa par visiteur ou par consultation
      — aucune. Les cinq autres capacités décrivent des réponses déterministes ; seule
      `desastres` introduit du hasard.
- [ ] 2.2 Relire les quatre scénarios de « Déclenchement conditionnel » à la lumière du cache — ils portent sur l'évaluation d'une règle, non sur sa fréquence, et devraient rester valides. À confirmer plutôt qu'à supposer

## 3. Vérification manuelle

> Ce changement ne modifie aucun comportement : il n'y a rien à vérifier sur le site. Les
> mesures qui fondent l'exigence ont été faites **avant** sa rédaction, et sont reproduites
> ci-dessous pour qu'un lecteur puisse les refaire.

- [x] 3.1 Mesurer une règle probabiliste sur une URL nue
      — `/post/eloi-soleil-mort`, règle `postillons_mort` à `probability: 0.7` :
      **0 déclenchement sur 20**. À 0,7, probabilité de 3 × 10⁻¹¹.
- [x] 3.2 Mesurer la même règle en faisant varier l'adresse
      — même page avec un paramètre inerte, `?cb=1` à `?cb=20` : **11 sur 20**, conforme à
      0,7. C'est le tirage qui est figé, pas la règle qui est cassée.
- [x] 3.3 Écarter le navigateur et l'intermédiaire de diffusion comme causes
      — en-têtes de la réponse : `cache-control: no-store, no-cache, must-revalidate`,
      `expires` daté de 1981, `cf-cache-status: DYNAMIC`. Rien n'est mis en cache en aval :
      c'est bien le cache de vues de l'application.
- [x] 3.4 Relever la configuration du cache
      — `src/apps/frontend/config/cache.yml` : `enabled: true`, `with_layout: true`,
      `lifetime: 86400`. Toutes les actions, avec habillage, pendant vingt-quatre heures.
- [ ] 3.5 Établir l'ordre exact entre l'écriture du cache et le filtre `sfDesastreFilter`, qui injecte après le rendu. Le résultat mesuré ne dépend pas de cette réponse, mais elle conditionne toute tentative future de rétablir un tirage par visiteur — et elle ne s'obtient pas de l'extérieur
- [ ] 3.6 Décider si le comportement doit changer. Ce changement ne le tranche pas : il rend la question posable sur une base exacte. Toute correction passerait par le cache — exclusion d'actions, cache par fragment, ou injection côté client — et devra peser le coût sur la charge d'un site dont `post/show` et `post/list` font l'essentiel du trafic
